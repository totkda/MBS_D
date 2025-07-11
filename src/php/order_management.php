<?php
// order_management.php
// 注文管理画面：注文の検索・一覧表示・削除


// DB接続設定
$host = '127.0.0.1';
$db = 'mbs';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 検索条件取得
$order_date_since = $_GET['order_date_since'] ?? '';
$order_date_until = $_GET['order_date_until'] ?? '';
$customer_name = $_GET['customer_name'] ?? '';
$status = $_GET['status'] ?? '';
$branch_name = $_GET['branch_name'] ?? '';
$message = '';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // 削除処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
        $delete_order_id = $_POST['delete_order_id'];
        $stmt = $pdo->prepare('DELETE FROM orders WHERE order_id = ?');
        $stmt->execute([$delete_order_id]);
        $message = "注文ID:{$delete_order_id} を削除しました。";
    }
    // 検索SQL組み立て
    $sql = "SELECT o.order_id, c.customer_name, o.order_date, o.status FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id LEFT JOIN branches b ON c.branch_id = b.branch_id WHERE 1=1";
    $params = [];
    if ($order_date_since) {
        $sql .= " AND o.order_date >= ?";
        $params[] = $order_date_since;
    }
    if ($order_date_until) {
        $sql .= " AND o.order_date <= ?";
        $params[] = $order_date_until;
    }
    if ($customer_name) {
        $sql .= " AND c.customer_name LIKE ?";
        $params[] = "%$customer_name%";
    }
    if ($status && $status !== 'すべて') {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }
    if ($branch_name) {
        $sql .= " AND b.branch_name LIKE ?";
        $params[] = "%$branch_name%";
    }
    $sql .= " ORDER BY o.order_id DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $message = 'DBエラー: ' . $e->getMessage();
    $orders = [];
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>MBSアプリ - 注文管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ...既存のCSS... */
    </style>
</head>

<body>
    <header class="container text-center">
        <nav class="main-nav">
            <ul>
                <li><a href="./index.php">ホーム</a></li>
                <li><a href="./order_management.php">注文管理</a></li>
                <li><a href="./delivery_management.php">納品管理</a></li>
                <li><a href="./customer_registor.php">顧客登録</a></li>
            </ul>
        </nav>
    </header>
    <main class="container mt-5 d-flex">
        <!-- 検索フォーム -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">注文書検索</h5>
                <form method="get" action="">
                    <div class="mt-4">
                        <div class="mb-3">
                            <div>注文日</div>
                            <input type="date" name="order_date_since" class="form-control" style="width: 80%; display: inline;" value="<?= htmlspecialchars($order_date_since) ?>">
                            <label class="form-label">から</label><br>
                            <input type="date" name="order_date_until" class="form-control" style="width: 80%; display: inline;" value="<?= htmlspecialchars($order_date_until) ?>">
                            <label class="form-label">まで</label><br>
                        </div>
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">顧客名</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="顧客名を入力" value="<?= htmlspecialchars($customer_name) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="status-select" class="form-label">ステータス</label>
                            <select id="status-select" name="status" class="form-select">
                                <option <?= $status == 'すべて' ? 'selected' : '' ?>>すべて</option>
                                <option <?= $status == '未納品' ? 'selected' : '' ?>>未納品</option>
                                <option <?= $status == '納品済' ? 'selected' : '' ?>>納品済</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="branch_name" class="form-label">支店名</label>
                            <input type="text" id="branch_name" name="branch_name" class="form-control" placeholder="支店名を入力" value="<?= htmlspecialchars($branch_name) ?>">
                        </div>
                        <input type="hidden" name="page" value="1">
                        <input type="submit" value="検索" class="btn btn-primary w-100">
                    </div>
                </form>
            </div>
        </div>
        <div>
            <!-- 注文表 -->
            <div>
                <div class="text-end">
                    <a href="./order_register.php"><input type="button" class="btn btn-success" value="新規登録"></a>
                </div>
                <!-- メッセージ表示 -->
                <?php if ($message): ?>
                    <div class="alert alert-info"> <?= htmlspecialchars($message) ?> </div>
                <?php endif; ?>
                <!-- 表 -->
                <div style="height: 500px; overflow-y: auto;">
                    <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle">
                        <caption align="top">注文書一覧</caption>
                        <thead class="table-dark table-bordered border-light sticky-top">
                            <tr>
                                <th>No.</th>
                                <th>顧客名</th>
                                <th>注文日</th>
                                <th>ステータス</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['order_date']) ?></td>
                                    <td><?= htmlspecialchars($order['status']) ?></td>
                                    <td><a href="./order_detail.php?order_id=<?= urlencode($order['order_id']) ?>"><input type="button" class="btn btn-primary" value="詳細"></a></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="delete_order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                            <input type="submit" class="btn btn-danger" value="削除" onclick="return confirm('本当に削除しますか？');">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6">該当データがありません</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- ページング（省略） -->
                <div class="text-center" id="pagination-area"></div>
            </div>
        </div>
    </main>
    <script src="./js/pagination.js"></script>
    <!-- 必要なJSの読み込み -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>