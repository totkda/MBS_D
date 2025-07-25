<?php
// delivery_register_api.php
header('Content-Type: application/json; charset=UTF-8');

// DB接続
require_once __DIR__ . '/db_connect.php';

// POSTデータ取得
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['result' => 'error', 'message' => 'データがありません']);
    exit;
}

// 顧客名→顧客ID取得
$customer_name = $data['customer_name'] ?? '';
$stmt = $pdo->prepare('SELECT ID FROM customers WHERE 顧客名 = ?');
$stmt->execute([$customer_name]);
$customer = $stmt->fetch();
if (!$customer) {
    http_response_code(400);
    echo json_encode(['result' => 'error', 'message' => '顧客が見つかりません']);
    exit;
}
$customer_id = $customer['ID'];

$delivery_date = $data['delivery_date'] ?? '';
$items = $data['items'] ?? [];

try {
    // 納品書Noの自動採番（最大値+1）
    $stmt = $pdo->query('SELECT IFNULL(MAX(No),0)+1 AS next_no FROM deliveries');
    $row = $stmt->fetch();
    $delivery_no = $row['next_no'];

    // deliveries 登録
    $stmt = $pdo->prepare('INSERT INTO deliveries (No, 納品日, 顧客ID, 納品ステータス名) VALUES (?, ?, ?, ?)');
    $stmt->execute([$delivery_no, $delivery_date, $customer_id, '納品済']);

    // delivery_details 登録
    $stmt_detail = $pdo->prepare('INSERT INTO delivery_details (納品書No, 品ID, 数量) VALUES (?, ?, ?)');
    foreach ($items as $item) {
        // 品名→品ID取得
        $stmt2 = $pdo->prepare('SELECT ID FROM products WHERE 品名 = ?');
        $stmt2->execute([$item['item_name']]);
        $product = $stmt2->fetch();
        if (!$product) continue; // 品名がDBにない場合はスキップ
        $product_id = $product['ID'];
        $stmt_detail->execute([$delivery_no, $product_id, $item['quantity']]);
    }

    echo json_encode(['result' => 'ok', 'delivery_no' => $delivery_no]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['result' => 'error', 'message' => '登録失敗: ' . $e->getMessage()]);
}
