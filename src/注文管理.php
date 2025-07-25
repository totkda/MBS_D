<?php
// 注文管理.php

// db_connect.php を利用
require_once(__DIR__ . '/db_connect.php'); // ここで$pdoが使える

// ページング用
$page  = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// データ取得（orders, customers, deliveries, branchesを結合して一覧表示）
$sql = "SELECT
    o.order_id,
    o.order_date,
    c.customer_name,
    b.branch_name,
    COALESCE(MAX(d.delivery_status_name), '未納品') AS delivery_status_name,
    o.note AS order_note
FROM
    orders o
LEFT JOIN customers c ON o.customer_id = c.customer_id
LEFT JOIN branches b ON c.branch_id = b.branch_id
LEFT JOIN order_delivery_map odm ON o.order_id = odm.order_id
LEFT JOIN deliveries d ON odm.delivery_id = d.delivery_id
GROUP BY o.order_id
ORDER BY o.order_date DESC, o.order_id DESC
LIMIT ? OFFSET ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ページング用の総件数取得
    $countSql = "SELECT COUNT(*) FROM orders";
    $totalRecords = $pdo->query($countSql)->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
} catch (PDOException $e) {
    error_log("データ取得エラー: " . $e->getMessage());
    $orders = [];
    $totalRecords = 0;
    $totalPages = 1;
}
// 注文一覧取得（顧客名もJOIN）
$sql = "SELECT o.order_id, o.order_date, c.customer_name, o.note
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        ORDER BY o.order_id DESC";
$stmt = $pdo->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <li><a href="./index.php">ホーム</a></li>
                <li><a href="./注文管理.php">注文管理</a></li>
                <li><a href="./納品管理.php">納品管理</a></li>
                <li><a href="./顧客取込.php">顧客登録</a></li>
            </ul>
        </nav>
    </header>
    <main class="container mt-5 d-flex">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">注文書検索</h5>
                <div class="mt-4">
                    <div class="mb-3">
                        <div>注文日</div>
                        <input type="date" id="order-date-since" class="form-control" style="width: 80%; display: inline;">
                        <label for="order-date-since" class="form-label">から</label><br>
                        <input type="date" id="order-date-until" class="form-control" style="width: 80%; display: inline;">
                        <label for="order-date-until" class="form-label">まで</label><br>
                    </div>
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">顧客名</label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="顧客名を入力" value="">
                    </div>
                    <div class="mb-3">
                        <label for="status-select" class="form-label">ステータス</label>
                        <select id="status-select" class="form-select">
                            <option>すべて</option>
                            <option>未納品</option>
                            <option>納品済</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="branch_name" class="form-label">支店名</label>
                        <input type="text" id="branch_name" name="branch_name" class="form-control" placeholder="支店名を入力" value="">
                    </div>
                    <input type="hidden" name="page" value="1">
                    <input type="button" value="検索" class="btn btn-primary w-100"></button>
                </div>
            </div>
        </div>
        <div>
            <!--  注文表  -->
            <div>
                <div class="text-end">
                    <a href="./注文登録.php"><input type="button" class="btn btn-success" value="新規登録"></a>
                </div>
                <!--  表  -->
                <div style="height: 500px; overflow-y: auto;">
                    <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle">
                        <caption align="top">注文書一覧</caption>
                        <thead class="table-dark table-bordered border-light sticky-top">
                            <tr>
                                <th>No.</th>
                                <th>顧客名</th>
                                <th>支店名</th>
                                <th>注文日</th>
                                <th>ステータス</th>
                                <th>備考</th>
                                <th>詳細</th>
                                <th>削除</th>
                            </tr>
                        </thead>
                        <tbody id="order-list-tbody">
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? '未登録顧客'); ?></td>
                                        <td><?php echo htmlspecialchars($order['branch_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                        <td><?php echo htmlspecialchars($order['delivery_status_name'] ?? '未納品'); ?></td>
                                        <td><?php echo htmlspecialchars($order['order_note'] ?? ''); ?></td>
                                        <td><a href="./注文詳細.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>"><button type="button" class="btn btn-primary btn-sm">詳細</button></a></td>
                                        <td><button type="button" class="btn btn-danger btn-sm delete-order-btn" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">削除</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">該当する注文がありません。</td>
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
        const totalPages = <?= (int)$totalPages ?>;
        let currentPage = <?= (int)$page ?>;

        // ページ切り替え時の処理
        function onPageChange(newPage) {
            // 現在のクエリパラメータを維持しつつpageだけ変更
            const params = new URLSearchParams(window.location.search);
            params.set('page', newPage);
            window.location.search = params.toString();
        }
    </script>
    <script src="./js/pagination.js"></script>
    <script src="./js/注文管理検索.js"></script>
    <!-- 編集用モーダル (例) -->
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderModalLabel">注文情報編集</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- 編集フォーム (例) -->
                    <form id="edit-order-form">
                        <div class="mb-3">
                            <label for="edit-customer-name" class="form-label">顧客名</label>
                            <input type="text" class="form-control" id="edit-customer-name" name="customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-order-date" class="form-label">注文日</label>
                            <input type="date" class="form-control" id="edit-order-date" name="order_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-delivery-status" class="form-label">納品ステータス</label>
                            <select class="form-select" id="edit-delivery-status" name="delivery_status">
                                <option value="未納品">未納品</option>
                                <option value="納品済">納品済</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-order-note" class="form-label">備考</label>
                            <textarea class="form-control" id="edit-order-note" name="order_note"></textarea>
                        </div>
                        <input type="hidden" id="edit-order-id" name="order_id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./注文管理.php">
                            <button type="button" class="btn btn-success" onclick="hideForm()">編集完了する</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // 編集モーダル表示
        function showEditModal(order) {
            document.getElementById('edit-order-id').value = order.order_id;
            document.getElementById('edit-customer-name').value = order.customer_name;
            document.getElementById('edit-order-date').value = order.order_date;
            document.getElementById('edit-delivery-status').value = order.delivery_status_name === '納品済' ? '納品済' : '未納品';
            document.getElementById('edit-order-note').value = order.order_note;
            const myModal = new bootstrap.Modal(document.getElementById('editOrderModal'));
            myModal.show();
        }
        // 編集完了時の処理
        function hideForm() {
            const myModalEl = document.getElementById('editOrderModal');
            const modal = bootstrap.Modal.getInstance(myModalEl);
            if (modal) {
                modal.hide();
            };
        }
    </script>
</body>

</html>