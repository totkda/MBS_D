<?php
//検索テスト
// DB接続設定
$pdo = new PDO('mysql:host=localhost;dbname=mbs;charset=utf8', 'root', '');

// ▼▼▼ ここに追加 ▼▼▼


// 検索条件の初期化
$where = [];
$params = [];

// 注文日（開始）
if (!empty($_GET['order_date_since'])) {
    $where[] = "orders.order_date >= :since";
    $params[':since'] = $_GET['order_date_since'];
}

// 注文日（終了）
if (!empty($_GET['order_date_until'])) {
    $where[] = "orders.order_date <= :until";
    $params[':until'] = $_GET['order_date_until'];
}

// 顧客名
if (!empty($_GET['customer_name'])) {
    $where[] = "customers.customer_name LIKE :customer_name";
    $params[':customer_name'] = "%" . $_GET['customer_name'] . "%";
}

// ステータス（「すべて」以外の場合のみ）
if (!empty($_GET['status']) && $_GET['status'] !== 'すべて') {
    $where[] = "orders.status = :status";
    $params[':status'] = $_GET['status'];
}

// 支店名
if (!empty($_GET['branch_name'])) {
    $where[] = "branches.branch_name LIKE :branch_name";
    $params[':branch_name'] = "%" . $_GET['branch_name'] . "%";
}

// SQL組み立て
$sql = "
    SELECT orders.order_id, customers.customer_name, orders.order_date, orders.status
    FROM orders
    JOIN customers ON orders.customer_id = customers.customer_id
    JOIN branches ON customers.branch_id = branches.branch_id
";


// WHERE句を追加
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// 実行
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>MBSアプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
            gap: 15px;
        }

        .main-nav a {
            display: inline-block;
            padding: 10px 24px;
            font-size: 16px;
            color: #333;
            background-color: #f4f4f4;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .main-nav a:hover {
            background-color: #007bff;
            color: #ffffff;
            border-color: #0069d9;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <header class="container text-center">
        <nav class="main-nav">
            <ul>
                <li><a href="./index.php">ホーム</a></li>
                <li><a href="./注文管理.php">注文管理</a></li>
                <li><a href="./納品管理.php">納品管理</a></li>
                <li><a href="./顧客取込.php">顧客登録</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mt-5 d-flex">

        <!-- 検索フォーム -->
        <div class="card me-4">
            <div class="card-body">
                <h5 class="card-title">注文書検索</h5>
                <form method="GET" action="">
                    <div class="mb-3">
                        <div>注文日</div>
                        <input type="date" name="order_date_since" class="form-control mb-2"
                            value="<?= htmlspecialchars($_GET['order_date_since'] ?? '') ?>">
                        <label class="form-label">から</label>
                        <input type="date" name="order_date_until" class="form-control"
                            value="<?= htmlspecialchars($_GET['order_date_until'] ?? '') ?>">
                        <label class="form-label">まで</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">顧客名</label>
                        <input type="text" name="customer_name" class="form-control"
                            value="<?= htmlspecialchars($_GET['customer_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ステータス</label>
                        <select name="status" class="form-select">
                            <?php
                            $statuses = ['すべて', '未納品', '納品済'];
                            foreach ($statuses as $status) {
                                $selected = ($_GET['status'] ?? '') === $status ? 'selected' : '';
                                echo "<option $selected>$status</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">支店名</label>
                        <input type="text" name="branch_name" class="form-control"
                            value="<?= htmlspecialchars($_GET['branch_name'] ?? '') ?>">
                    </div>

                    <input type="submit" value="検索" class="btn btn-primary w-100">
                </form>
            </div>
        </div>

        <!-- 注文表 -->
        <div style="flex-grow: 1;">
            <div class="text-end mb-3">
                <a href="./注文登録.html"><input type="button" class="btn btn-success" value="新規登録"></a>
            </div>

            <div style="height: 500px; overflow-y: auto;">
                <table class="table table-bordered table-striped table-hover table-sm align-middle">
                    <caption align="top">注文書一覧</caption>
                    <thead class="table-dark sticky-top">
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
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['order_date']) ?></td>
                                    <td><?= htmlspecialchars($order['status']) ?></td>
                                    <td><a href="詳細画面.php?id=<?= htmlspecialchars($order['order_id']) ?>">詳細</a></td>
                                    <td><button class="btn btn-danger">削除</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">該当する注文はありません。</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center" id="pagination-area"></div>
        </div>
    </main>

    <!-- 削除確認モーダル -->
    <div class="modal fade" id="order-delete" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">削除しますか</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">本当に削除しますか？</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <form id="delete-form" method="POST" action="注文削除.php">
                        <input type="hidden" name="order_id" id="delete-order-id">
                        <button type="submit" class="btn btn-danger">削除する</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(orderId) {
            $('#delete-order-id').val(orderId);
            const modal = new bootstrap.Modal(document.getElementById('order-delete'));
            modal.show();
        }
    </script>
</body>

</html>