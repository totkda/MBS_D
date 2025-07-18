<?php
// DB接続設定
$pdo = new PDO('mysql:host=localhost;dbname=mbs;charset=utf8mb4', 'root', '');

// 検索条件処理
$where = [];
$params = [];

if (!empty($_GET['customer_id'])) {
    $where[] = 'customers.customer_id LIKE :customer_id';
    $params[':customer_id'] = '%' . $_GET['customer_id'] . '%';
}
if (!empty($_GET['customer_name'])) {
    $where[] = 'customers.customer_name LIKE :customer_name';
    $params[':customer_name'] = '%' . $_GET['customer_name'] . '%';
}
if (!empty($_GET['branch_name'])) {
    $where[] = 'branches.branch_name LIKE :branch_name';
    $params[':branch_name'] = '%' . $_GET['branch_name'] . '%';
}

// SQL文組み立て
$sql = 'SELECT orders.*, customers.customer_name, branches.branch_name
        FROM orders
        LEFT JOIN customers ON orders.customer_id = customers.customer_id
        LEFT JOIN branches ON customers.branch_id = branches.branch_id';

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY order_date DESC';

// 実行と取得
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
