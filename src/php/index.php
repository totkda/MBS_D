<?php
// index.php
// 統計情報検索ページ
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
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('DB接続失敗: ' . $e->getMessage());
}

// 検索条件の取得
$customer_name = $_GET['customer_name'] ?? '';
$branch_name = $_GET['branch_name'] ?? '';
$sort = $_GET['sort'] ?? 'total_sales';
$order = $_GET['order'] ?? 'desc';

// // データ取得SQL（例: 顧客統計情報）データが空だとエラーになるため、初期化
// $sql = "SELECT 
//             b.branch_name, 
//             c.customer_id, 
//             c.customer_name, 
//             COALESCE(SUM(o.amount), 0) AS total_sales, 
//             COALESCE(AVG(DATEDIFF(o.delivery_date, o.order_date)), 0) AS avg_lead_time
//         FROM customers c
//         LEFT JOIN branches b ON c.branch_id = b.branch_id
//         LEFT JOIN orders o ON c.customer_id = o.customer_id
//         WHERE (:customer_name = '' OR c.customer_name LIKE :customer_name_like)
//         AND (:branch_name = '' OR b.branch_name LIKE :branch_name_like)
//         GROUP BY b.branch_name, c.customer_id, c.customer_name
//         ORDER BY $sort $order
//         LIMIT 20";
// $stmt = $pdo->prepare($sql);
// // バインド値を条件に応じてセット
// $stmt->bindValue(':customer_name', $customer_name);
// $stmt->bindValue(':customer_name_like', $customer_name === '' ? '%' : "%$customer_name%", PDO::PARAM_STR);
// $stmt->bindValue(':branch_name', $branch_name);
// $stmt->bindValue(':branch_name_like', $branch_name === '' ? '%' : "%$branch_name%", PDO::PARAM_STR);
// $stmt->execute();
// $rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>MBSアプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <!-- ナビゲーションバー -->
    <header class="container text-center">
        <?php include 'navbar.php'; ?>
        <!-- ショートカットキーをナビバー下に配置 -->
        <div class="text-center my-2">
            <a href="/src/php/order_register.php"><input type="button" class="btn btn-success" value="注文登録"></a>
            <a href="./納品登録.php"><input type="button" class="btn btn-success" value="納品登録"></a>
        </div>
    </header>
    <main class="container d-flex">
        <!-- 検索フォーム -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">統計情報検索</h5>
                <form method="get" action="">
                    <div class="mt-4">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">顧客名</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="顧客名を入力" value="<?= htmlspecialchars($customer_name) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="branch_name" class="form-label">支店名</label>
                            <input type="text" id="branch_name" name="branch_name" class="form-control" placeholder="支店名を入力" value="<?= htmlspecialchars($branch_name) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="sort_select" class="form-label">並び順</label>
                            <select id="sort_select" name="sort" class="form-select">
                                <option value="total_sales" <?= $sort == 'total_sales' ? 'selected' : '' ?>>累計売上額</option>
                                <option value="avg_lead_time" <?= $sort == 'avg_lead_time' ? 'selected' : '' ?>>平均リードタイム</option>
                                <option value="customer_name" <?= $sort == 'customer_name' ? 'selected' : '' ?>>顧客名</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <select name="order" class="form-select">
                                <option value="desc" <?= $order == 'desc' ? 'selected' : '' ?>>降順</option>
                                <option value="asc" <?= $order == 'asc' ? 'selected' : '' ?>>昇順</option>
                            </select>
                        </div>
                        <input type="hidden" name="page" value="1">
                        <input type="submit" value="検索" class="btn btn-primary w-100">
                    </div>
                </form>
            </div>
        </div>
        <div>
            <!-- 統計表 -->
            <div>
                <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle">
                    <thead class="table-dark table-bordered border-light sticky-top">
                        <tr>
                            <th>支店名</th>
                            <th>顧客ID</th>
                            <th>顧客名</th>
                            <th>累計売上額</th>
                            <th>平均リードタイム</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['branch_name']) ?></td>
                                <td><?= htmlspecialchars($row['customer_id']) ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['total_sales']) ?></td>
                                <td><?= htmlspecialchars($row['avg_lead_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="5">該当データがありません</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- ページング（省略） -->
                <div class="text-center" id="pagination-area">
                    <input type="button" value="←">
                    <span></span>
                    <input type="button" value="→">
                </div>
            </div>
        </div>
    </main>
    <script src="./js/pagination.js"></script>
</body>

</html>