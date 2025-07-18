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
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>MBSアプリ - 注文管理</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            /* ===============
               ナビゲーションバー
               =============== */
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
                background-color: #007bff; /* 背景色を青に */
                color: #ffffff;            /* 文字色を白に */
                border-color: #0069d9;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            /* 検索フォームとテーブルのレイアウト調整 */
            main.container.d-flex {
                gap: 20px; /* 検索フォームとテーブルの間にスペースを追加 */
                align-items: flex-start; /* 上端を揃える */
            }
            .card {
                flex: 0 0 300px; /* 検索フォームの幅を固定 */
            }
            .order-table-container {
                flex-grow: 1; /* 残りのスペースをテーブルが占める */
            }
        </style>
    </head>
    <body>
        <header class="container text-center">
            <nav class="main-nav">
                <ul>
                    <li><a href="./index.html"><input type="button" value="ホーム"></a></li>
                    <li><a href="./注文管理.php"><input type="button" value="注文管理"></a></li> <li><a href="./納品管理.html"><input type="button" value="納品管理"></a></li>
                    <li><a href="./顧客取込.html"><input type="button" value="顧客登録"></a></li>
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
                            <input type="date" id="order-date-since" class="form-control" style="width: 80%; display: inline;" value="<?php echo htmlspecialchars($orderDateSince); ?>">
                            <label for="order-date-since" class="form-label">から</label><br>
                            <input type="date" id="order-date-until" class="form-control" style="width: 80%; display: inline;" value="<?php echo htmlspecialchars($orderDateUntil); ?>">
                            <label for="order-date-until" class="form-label">まで</label><br>
                        </div>

                        <div class="mb-3">
                            <label for="customer_name" class="form-label">顧客名</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="顧客名を入力" value="<?php echo htmlspecialchars($customerName); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status-select" class="form-label">ステータス</label>
                            <select id="status-select" class="form-select">
                                <option value="すべて" <?php if($status === 'すべて') echo 'selected'; ?>>すべて</option>
                                <option value="未納品" <?php if($status === '未納品') echo 'selected'; ?>>未納品</option>
                                <option value="納品済" <?php if($status === '納品済') echo 'selected'; ?>>納品済</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="branch_name" class="form-label">支店名</label>
                            <input type="text" id="branch_name" name="branch_name" class="form-control" placeholder="支店名を入力" value="<?php echo htmlspecialchars($branchName); ?>">
                        </div>
                        
                        <button type="button" class="btn btn-primary w-100" id="search-button">検索</button> 
                    </div>
                </div>
            </div>

            <div class="order-table-container"> 

                <div>
                    <div class="text-end">
                        <a href="./注文登録.php"><button type="button" class="btn btn-success">新規登録</button></a> </div>

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
                                        <td><a href="./注文詳細.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>"><button type="button" class="btn btn-primary btn-sm">詳細</button></a></td>
                                        <td><button type="button" class="btn btn-danger btn-sm delete-order-btn" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">削除</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">該当する注文がありません。</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center mt-3" id="pagination-area">
                        <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="#" data-page="<?php echo $page - 1; ?>">前へ</a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php if($i === $page) echo 'active'; ?>">
                                    <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php if($page >= $totalPages) echo 'disabled'; ?>">
                                    <a class="page-link" href="#" data-page="<?php echo $page + 1; ?>">次へ</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchButton = document.getElementById('search-button');
                const paginationArea = document.getElementById('pagination-area');
                const orderTableBody = document.getElementById('order-list-tbody'); // 削除ボタンのイベントリスナー用

                // 検索フォームの入力値を取得し、URLを構築してページを再読み込みする関数
                function performSearch(newPage = 1) {
                    const orderDateSince = document.getElementById('order-date-since').value;
                    const orderDateUntil = document.getElementById('order-date-until').value;
                    const customerName = document.getElementById('customer_name').value;
                    const status = document.getElementById('status-select').value;
                    const branchName = document.getElementById('branch_name').value;

                    const params = new URLSearchParams();
                    if (orderDateSince) params.append('orderDateSince', orderDateSince);
                    if (orderDateUntil) params.append('orderDateUntil', orderDateUntil);
                    if (customerName) params.append('customerName', customerName);
                    if (status !== 'すべて') params.append('status', status);
                    if (branchName) params.append('branchName', branchName);
                    params.append('page', newPage); // 新しいページ番号をセット

                    // 現在のURLのパス部分を取得し、クエリパラメータを付加してページを再読み込み
                    window.location.href = window.location.pathname + '?' + params.toString();
                }

                // 検索ボタンクリック時のイベント
                searchButton?.addEventListener('click', function() {
                    performSearch(1); // 検索時は1ページ目から表示
                });

                // ページネーションリンクのクリックイベント
                paginationArea.querySelectorAll('.page-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault(); // デフォルトのリンク動作をキャンセル
                        const pageNum = parseInt(this.dataset.page);
                        if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= <?php echo $totalPages; ?>) { // $totalPagesをJSに渡す
                            performSearch(pageNum); // 新しいページ番号で検索を実行
                        }
                    });
                });

                // 削除ボタンのイベントリスナー
                orderTableBody.querySelectorAll('.delete-order-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const orderIdToDelete = this.dataset.orderId;
                        if (confirm(`注文ID: ${orderIdToDelete} を本当に削除しますか？\n（この機能は未実装です。別途delete_order.phpを作成してください。）`)) {
                            // ここに削除処理のfetchリクエストを記述します。
                            // fetch('delete_order.php', {
                            //     method: 'POST',
                            //     headers: { 'Content-Type': 'application/json' },
                            //     body: JSON.stringify({ orderId: orderIdToDelete })
                            // })
                            // .then(response => response.json())
                            // .then(data => {
                            //     if (data.success) {
                            //         alert('注文が削除されました。');
                            //         performSearch(<?php echo $page; ?>); // 現在のページを再読み込みして更新
                            //     } else {
                            //         alert('削除に失敗しました: ' + (data.message || ''));
                            //     }
                            // })
                            // .catch(error => {
                            //     alert('通信エラー: ' + error);
                            // });
                            console.log(`注文ID: ${orderIdToDelete} の削除処理を実行します。（未実装）`);
                        }
                    });
                });
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>

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
        }
    }
</script>