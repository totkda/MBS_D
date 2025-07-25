<?php
require_once 'db_connect.php'; // DB接続ファイルを読み込み

// 検索条件の取得
$deliveryDateSince = $_GET['deliveryDateSince'] ?? null;
$deliveryDateUntil = $_GET['deliveryDateUntil'] ?? null;
$customerName = $_GET['customerName'] ?? null;
$status = $_GET['status'] ?? null;
$branchName = $_GET['branchName'] ?? null;

try {

    // SQLクエリの作成
    $sql = "
        SELECT 
            d.delivery_id AS delivery_no,
            c.customer_name,
            d.delivery_date,
            d.delivery_status_name
        FROM deliveries d
        LEFT JOIN customers c ON d.customer_id = c.customer_id
        LEFT JOIN branches b ON c.branch_id = b.branch_id
        WHERE 1=1
    ";

    $params = [];

    // 検索条件の追加
    if (!empty($deliveryDateSince)) {
        $sql .= " AND d.delivery_date >= ?";
        $params[] = $deliveryDateSince;
    }
    if (!empty($deliveryDateUntil)) {
        $sql .= " AND d.delivery_date <= ?";
        $params[] = $deliveryDateUntil;
    }
    if (!empty($customerName)) {
        $sql .= " AND c.customer_name LIKE ?";
        $params[] = '%' . $customerName . '%';
    }
    if (!empty($status) && $status !== 'すべて') {
        $sql .= " AND d.delivery_status_name = ?";
        $params[] = $status;
    }
    if (!empty($branchName)) {
        $sql .= " AND b.branch_name LIKE ?";
        $params[] = '%' . $branchName . '%';
    }

    $sql .= " ORDER BY d.delivery_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>MBSアプリ</title>
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

        /* ナビゲーションボタンの基本スタイル */
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

        /* ▼▼▼ この部分でカーソルが重なった時の色を指定 ▼▼▼ */
        .main-nav a:hover {
            background-color: #007bff;
            /* 背景色を青に */
            color: #ffffff;
            /* 文字色を白に */
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
                <li><a href="./納品管理.html">納品管理</a></li>
                <li><a href="./顧客取込.html">顧客登録</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mt-5 d-flex">
        <!--  検索フォーム  -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">納品書検索</h5>
                <form method="GET" action="">
                    <div class="mt-4">
                        <div class="mb-3">
                            <div>納品日</div>
                            <input type="date" name="deliveryDateSince" class="form-control" value="<?= htmlspecialchars($deliveryDateSince) ?>">
                            <label class="form-label">から</label><br>
                            <input type="date" name="deliveryDateUntil" class="form-control" value="<?= htmlspecialchars($deliveryDateUntil) ?>">
                            <label class="form-label">まで</label><br>
                        </div>

                        <div class="mb-3">
                            <label for="customer_name" class="form-label">顧客名</label>
                            <input type="text" name="customerName" class="form-control" placeholder="顧客名を入力" value="<?= htmlspecialchars($customerName) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status-select" class="form-label">ステータス</label>
                            <select name="status" class="form-select">
                                <option>すべて</option>
                                <option <?= $status === '未納品' ? 'selected' : '' ?>>未納品</option>
                                <option <?= $status === '納品済' ? 'selected' : '' ?>>納品済</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="branch_name" class="form-label">支店名</label>
                            <input type="text" name="branchName" class="form-control" placeholder="支店名を入力" value="<?= htmlspecialchars($branchName) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">検索</button>
                    </div>
                </form>
            </div>
        </div>

        <div>

            <!--  注文表  -->
            <div>

                <div class="text-end">
                    <a href="./納品登録.html"><input type="button" class="btn btn-success" value="新規登録"></a>
                </div>

                <!--  表  -->
                <div style="height: 300px; overflow-y: auto;">
                    <table class="table table-bordered  border-dark  table-striped table-hover table-sm align-middle">
                        <caption align="top">納品書一覧</caption>
                        <thead class="table-dark table-bordered  border-light sticky-top">
                            <tr>
                                <th>No.</th>
                                <th>顧客名</th>
                                <th>納品日</th>
                                <th>ステータス</th>
                                <th>詳細</th>
                                <th>削除</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deliveries)): ?>
                                <?php foreach ($deliveries as $delivery): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($delivery['delivery_no']) ?></td>
                                        <td><?= htmlspecialchars($delivery['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($delivery['delivery_date']) ?></td>
                                        <td><?= htmlspecialchars($delivery['delivery_status_name']) ?></td>
                                        <td><a href="./納品詳細.php?delivery_id=<?= $delivery['delivery_no'] ?>"><input type="button" class="btn btn-primary" value="詳細"></a></td>
                                        <td><a href="php/delete_delivery.php?id=<?= htmlspecialchars($delivery['delivery_no']) ?>" onclick="return confirm('本当に削除しますか？');"><input type="button" class="btn btn-danger" value="削除"></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">データがありません</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <div id="pagination-area"></div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PHPからページ情報をJSへ
        const totalPages = <?= isset($totalPages) ? (int)$totalPages : 1 ?>;
        let currentPage = <?= isset($page) ? (int)$page : 1 ?>;

        // ページ切り替え時の処理
        function onPageChange(newPage) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', newPage);
            window.location.search = params.toString();
        }
    </script>
    <script src="./js/pagination.js"></script>
</body>

</html>