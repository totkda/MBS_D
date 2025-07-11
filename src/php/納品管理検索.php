<?php
// DB接続
$host = 'localhost';
$dbname = 'your_database_name';
$user = 'your_username';
$pass = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("DB接続エラー: " . $e->getMessage());
}

// 検索条件を取得（GET）
$dateSince = $_GET['order_date_since'] ?? '';
$dateUntil = $_GET['order_date_until'] ?? '';
$customerName = $_GET['customer_name'] ?? '';
$branchName = $_GET['branch_name'] ?? '';
$status = $_GET['status'] ?? 'すべて';

// SQL組み立て
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if (!empty($dateSince)) {
    $sql .= " AND order_date >= :dateSince";
    $params[':dateSince'] = $dateSince;
}
if (!empty($dateUntil)) {
    $sql .= " AND order_date <= :dateUntil";
    $params[':dateUntil'] = $dateUntil;
}
if (!empty($customerName)) {
    $sql .= " AND customer_name LIKE :customerName";
    $params[':customerName'] = "%$customerName%";
}
if (!empty($branchName)) {
    $sql .= " AND branch_name LIKE :branchName";
    $params[':branchName'] = "%$branchName%";
}
if ($status !== 'すべて') {
    $sql .= " AND status = :status";
    $params[':status'] = $status;
}

// SQL実行
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- HTMLで結果を表示 -->
<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>顧客名</th>
            <th>注文日</th>
            <th>支店名</th>
            <th>ステータス</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['order_date']) ?></td>
                <td><?= htmlspecialchars($row['branch_name']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
