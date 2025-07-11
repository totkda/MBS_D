<?php
// delivery_management.php
// 納品管理画面（納品書一覧・検索・削除）
// 検索条件で納品データを抽出
// 削除ボタンで該当データを削除（確認ダイアログ付き）
// エラー時やデータなし時の表示も考慮
// レイアウトやナビゲーションは元のHTMLを踏襲


// DB接続情報
$host = '127.0.0.1';
$db   = 'mbs';
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
$delivery_date_since = $_GET['delivery_date_since'] ?? '';
$delivery_date_until = $_GET['delivery_date_until'] ?? '';
$customer_name = $_GET['customer_name'] ?? '';
$status = $_GET['status'] ?? '';
$branch_name = $_GET['branch_name'] ?? '';

// SQL生成
$where = [];
$params = [];
if ($delivery_date_since !== '') {
    $where[] = 'delivery_date >= :since';
    $params[':since'] = $delivery_date_since;
}
if ($delivery_date_until !== '') {
    $where[] = 'delivery_date <= :until';
    $params[':until'] = $delivery_date_until;
}
if ($customer_name !== '') {
    $where[] = 'customer_name LIKE :customer_name';
    $params[':customer_name'] = "%$customer_name%";
}
if ($status !== '' && $status !== 'すべて') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}
if ($branch_name !== '') {
    $where[] = 'branch_name LIKE :branch_name';
    $params[':branch_name'] = "%$branch_name%";
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// データ取得
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $sql = "SELECT id, customer_name, delivery_date, amount, status FROM deliveries $where_sql ORDER BY delivery_date DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $deliveries = [];
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>納品管理 | MBSアプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ナビゲーションバー */
        nav ul {
            list-style-type: none;
        }

        nav ul li {
            display: inline;
        }

        .container {
            padding: 20px 0;
        }

        .main-nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 15px;
        }

        .main-nav a {
            display: inline-block;
            padding: 10px 24px;
            font-family: "Helvetica", "Arial", sans-serif;
            font-size: 16px;
            color: #333;
            background-color: #f4f4f4;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .main-nav a:hover {
            background-color: #007bff;
            color: #fff;
            border-color: #0069d9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <header class="container text-center">
        <nav class="main-nav">
            <ul>
                <li><a href="./index.html">ホーム</a></li>
                <li><a href="./注文管理.html">注文管理</a></li>
                <li><a href="./delivery_management.php">納品管理</a></li>
                <li><a href="./顧客取込.html">顧客登録</a></li>
            </ul>
        </nav>
    </header>
    <main class="container mt-5 d-flex">
        <!-- 検索フォーム -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">納品書検索</h5>
                <form method="get" action="delivery_management.php">
                    <div class="mt-4">
                        <div class="mb-3">
                            <div>納品日</div>
                            <input type="date" name="delivery_date_since" class="form-control" style="width: 80%; display: inline;" value="<?= htmlspecialchars($delivery_date_since) ?>">
                            <label class="form-label">から</label><br>
                            <input type="date" name="delivery_date_until" class="form-control" style="width: 80%; display: inline;" value="<?= htmlspecialchars($delivery_date_until) ?>">
                            <label class="form-label">まで</label><br>
                        </div>
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">顧客名</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="顧客名を入力" value="<?= htmlspecialchars($customer_name) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">ステータス</label>
                            <select id="status" name="status" class="form-select">
                                <option <?= $status == '' || $status == 'すべて' ? 'selected' : '' ?>>すべて</option>
                                <option <?= $status == '未納品' ? 'selected' : '' ?>>未納品</option>
                                <option <?= $status == '納品済' ? 'selected' : '' ?>>納品済</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="branch_name" class="form-label">支店名</label>
                            <input type="text" id="branch_name" name="branch_name" class="form-control" placeholder="支店名を入力" value="<?= htmlspecialchars($branch_name) ?>">
                        </div>
                        <input type="submit" value="検索" class="btn btn-primary w-100">
                    </div>
                </form>
            </div>
        </div>
        <div class="ms-4 flex-grow-1">
            <!-- 新規登録ボタン -->
            <div class="text-end mb-2">
                <a href="./納品登録.html"><input type="button" class="btn btn-success" value="新規登録"></a>
            </div>
            <!-- 納品書一覧テーブル -->
            <div style="height: 300px; overflow-y: auto;">
                <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle">
                    <caption align="top">納品書一覧</caption>
                    <thead class="table-dark table-bordered border-light sticky-top">
                        <tr>
                            <th>No.</th>
                            <th>顧客名</th>
                            <th>納品日</th>
                            <th>金額</th>
                            <th>ステータス</th>
                            <th>詳細</th>
                            <th>削除</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($error)): ?>
                            <tr>
                                <td colspan="7" class="text-danger">エラー: <?= htmlspecialchars($error) ?></td>
                            </tr>
                        <?php elseif (empty($deliveries)): ?>
                            <tr>
                                <td colspan="7">該当する納品書がありません。</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deliveries as $i => $row): ?>
                                <tr>
                                    <td><?= sprintf('%05d', $row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($row['delivery_date']) ?></td>
                                    <td>&yen;<?= number_format($row['amount']) ?></td>
                                    <td><?= htmlspecialchars($row['status']) ?></td>
                                    <td><a href="./納品詳細.html?id=<?= $row['id'] ?>"><input type="button" class="btn btn-primary" value="詳細"></a></td>
                                    <td>
                                        <form method="post" action="delivery_management.php" onsubmit="return confirm('本当に削除しますか？');" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                            <input type="submit" class="btn btn-danger" value="削除">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- ページング（必要に応じて実装） -->
            <div class="text-center" id="pagination-area"></div>
        </div>
    </main>
    <script src="./js/pagination.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// 削除処理（POSTリクエスト時）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $stmt = $pdo->prepare('DELETE FROM deliveries WHERE id = :id');
        $stmt->execute([':id' => $delete_id]);
        // 削除後、GETでリダイレクト
        header('Location: delivery_management.php');
        exit;
    } catch (PDOException $e) {
        echo '<script>alert("削除に失敗しました: ' . htmlspecialchars($e->getMessage()) . '");</script>';
    }
}
?>