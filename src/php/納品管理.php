<?php
// DB接続
$pdo = new PDO('mysql:host=localhost;dbname=mbs;charset=utf8', 'root', '');
// 検索条件処理
$where = [];
$params = [];

if (!empty($_GET['order_date_since'])) {
    $where[] = 'delivery_date >= :since';
    $params[':since'] = $_GET['order_date_since'];
}
if (!empty($_GET['order_date_until'])) {
    $where[] = 'delivery_date <= :until';
    $params[':until'] = $_GET['order_date_until'];
}
if (!empty($_GET['customer_name'])) {
    $where[] = 'customer_name LIKE :customer_name';
    $params[':customer_name'] = '%' . $_GET['customer_name'] . '%';
}
if (!empty($_GET['status']) && $_GET['status'] != 'すべて') {
    $where[] = 'status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['branch_name'])) {
    $where[] = 'branch_name LIKE :branch_name';
    $params[':branch_name'] = '%' . $_GET['branch_name'] . '%';
}

// SQL組み立て
$sql = 'SELECT * FROM deliveries';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY delivery_date DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MBSアプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        nav ul { list-style-type: none; }
        nav ul li { display: inline; }
        .container { padding: 20px 0; }
        .main-nav ul { display: flex; justify-content: center; gap: 15px; padding: 0; }
        .main-nav a {
            padding: 10px 24px;
            font-size: 16px;
            color: #333;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .main-nav a:hover {
            background-color: #007bff;
            color: #fff;
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
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">納品書検索</h5>
            <form method="GET" action="">
                <div class="mb-3">
                    <div>納品日</div>
                    <input type="date" name="order_date_since" class="form-control" style="width: 80%; display: inline;" value="<?= htmlspecialchars($_GET['order_date_since'] ?? '') ?>">
                    <label class="form-label">から</label><br>
                    <input type="date" name="order_date_until" class="form-control" style="width: 80%; display: inline;" value="<?= htmlspecialchars($_GET['order_date_until'] ?? '') ?>">
                    <label class="form-label">まで</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">顧客名</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="顧客名を入力" value="<?= htmlspecialchars($_GET['customer_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">ステータス</label>
                    <select name="status" class="form-select">
                        <option <?= (!isset($_GET['status']) || $_GET['status'] == 'すべて') ? 'selected' : '' ?>>すべて</option>
                        <option <?= ($_GET['status'] ?? '') == '未納品' ? 'selected' : '' ?>>未納品</option>
                        <option <?= ($_GET['status'] ?? '') == '納品済' ? 'selected' : '' ?>>納品済</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">支店名</label>
                    <input type="text" name="branch_name" class="form-control" placeholder="支店名を入力" value="<?= htmlspecialchars($_GET['branch_name'] ?? '') ?>">
                </div>
                <input type="submit" value="検索" class="btn btn-primary w-100">
            </form>
        </div>
    </div>

    <!-- 納品一覧 -->
    <div class="ms-4 w-100">
        <div class="text-end mb-2">
            <a href="./納品登録.html"><input type="button" class="btn btn-success" value="新規登録"></a>
        </div>

        <div style="height: 300px; overflow-y: auto;">
            <table class="table table-bordered table-striped table-hover table-sm align-middle">
                <caption>納品書一覧</caption>
                <thead class="table-dark sticky-top">
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
                    <?php foreach ($results as $i => $row): ?>
                        <tr>
                            <td><?= sprintf('%05d', $i + 1) ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['delivery_date']) ?></td>
                            <td>&yen;<?= number_format($row['amount']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><a href="納品詳細.php?id=<?= $row['id'] ?>"><button class="btn btn-primary">詳細</button></a></td>
                            <td><button class="btn btn-danger" onclick="confirmDelete(<?= $row['id'] ?>)">削除</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ページング（ここでは未実装） -->
        <div class="text-center" id="pagination-area"></div>
    </div>
</main>

<!-- モーダル -->
<div class="modal fade" id="delivery-delete" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">削除しますか</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">本当に削除しますか？</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <a id="delete-link"><button type="button" class="btn btn-danger">削除する</button></a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    const modal = new bootstrap.Modal(document.getElementById('delivery-delete'));
    document.getElementById('delete-link').href = 'delete_delivery.php?id=' + id;
    modal.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
