<?php
// -----------------------------------------
// データベース接続設定
// -----------------------------------------
$host = 'localhost';                  // ホスト名（通常は "localhost"）
$dbname = 'your_database_name';      // データベース名
$user = 'your_username';             // データベースユーザー名
$pass = 'your_password';             // データベースパスワード

try {
    // PDOを使ってMySQLに接続（UTF-8で文字化け防止）
    $pdo = new PDO('mysql:host=localhost;dbname=mbs;charset=utf8', 'root', '');
} catch (PDOException $e) {
    // 接続に失敗した場合はエラーメッセージを表示して終了
    die("DB接続エラー: " . $e->getMessage());
}

// -----------------------------------------
// GETパラメータで検索条件を取得
// -----------------------------------------
$order_date_since = $_GET['order_date_since'] ?? null; // 開始日（例：2024-01-01）
$order_date_until = $_GET['order_date_until'] ?? null; // 終了日（例：2024-12-31）
$customer_name     = $_GET['customer_name'] ?? null;   // 顧客名（部分一致）
$status            = $_GET['status'] ?? null;          // 注文ステータス
$branch_name       = $_GET['branch_name'] ?? null;     // 支店名（部分一致）

// -----------------------------------------
// SQLクエリ構築（条件に応じてWHERE句を追加）
// -----------------------------------------
$sql = "SELECT * FROM orders WHERE 1=1";  // ベースのSQL（1=1は条件を後付けしやすくするため）
$params = [];  // プレースホルダーに渡す値の配列

// 開始日フィルタ（order_date >= 指定日）
if (!empty($order_date_since)) {
    $sql .= " AND order_date >= :since";
    $params[':since'] = $order_date_since;
}

// 終了日フィルタ（order_date <= 指定日）
if (!empty($order_date_until)) {
    $sql .= " AND order_date <= :until";
    $params[':until'] = $order_date_until;
}

// 顧客名フィルタ（部分一致検索）
if (!empty($customer_name)) {
    $sql .= " AND customer_name LIKE :customer_name";
    $params[':customer_name'] = "%" . $customer_name . "%";
}

// ステータスフィルタ（"すべて" 以外の場合のみ）
if (!empty($status) && $status !== 'すべて') {
    $sql .= " AND status = :status";
    $params[':status'] = $status;
}

// 支店名フィルタ（部分一致検索）
if (!empty($branch_name)) {
    $sql .= " AND branch_name LIKE :branch_name";
    $params[':branch_name'] = "%" . $branch_name . "%";
}

// 注文日で降順に並べ替え（新しい順に表示）
$sql .= " ORDER BY order_date DESC";

// -----------------------------------------
// クエリ実行
// -----------------------------------------
$stmt = $pdo->prepare($sql);     // SQLを準備
$stmt->execute($params);         // パラメータをバインドして実行
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);  // 結果を連想配列で取得
?>

// -----------------------------------------
// 結果の表示処理
// -----------------------------------------
<!-- ▼検索結果の表示▼ -->
<div class="mt-4">
    <h5>検索結果</h5>
    <?php if (count($results) === 0): ?>
        <p>注文は見つかりませんでした。</p>
    <?php else: ?>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>顧客名</th>
                    <th>注文日</th>
                    <th>ステータス</th>
                    <th>支店</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars($row['order_date']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['branch_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
