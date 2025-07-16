<?php
// 注文管理.php

// DB接続情報
$host = 'localhost';
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // DB接続失敗時はエラーメッセージを表示して終了
    die('DB接続失敗: ' . $e->getMessage());
}

// GETリクエストで検索条件を受信
$orderDateSince = $_GET['orderDateSince'] ?? '';
$orderDateUntil = $_GET['orderDateUntil'] ?? '';
$customerName   = $_GET['customerName'] ?? '';
$status         = $_GET['status'] ?? 'すべて'; // デフォルトは「すべて」
$branchName     = $_GET['branchName'] ?? '';
$page           = intval($_GET['page'] ?? 1); // ページング用
$limit          = 10; // 1ページあたりの表示件数 (任意)
$offset         = ($page - 1) * $limit;

// SQLクエリの構築
$sql = "SELECT
            o.order_id,
            o.order_date,
            c.customer_name,
            -- 納品ステータスは関連する納品があれば取得、なければNULL
            MAX(d.delivery_status_name) AS delivery_status_name,
            o.note AS order_note
        FROM
            orders o
        LEFT JOIN
            customers c ON o.customer_id = c.customer_id
        LEFT JOIN
            order_delivery_map odm ON o.order_id = odm.order_id
        LEFT JOIN
            deliveries d ON odm.delivery_id = d.delivery_id
        LEFT JOIN
            branches b ON c.branch_id = b.branch_id
        WHERE 1=1";

$params = [];

// 注文日期間
if (!empty($orderDateSince)) {
    $sql .= " AND o.order_date >= ?";
    $params[] = $orderDateSince;
}
if (!empty($orderDateUntil)) {
    $sql .= " AND o.order_date <= ?";
    $params[] = $orderDateUntil;
}

// 顧客名 (部分一致検索)
if (!empty($customerName)) {
    $sql .= " AND c.customer_name LIKE ?";
    $params[] = '%' . $customerName . '%';
}

// ステータス
if ($status !== 'すべて') {
    if ($status === '未納品') {
        // 関連する納品が存在しない、または、関連する納品が「納品済」ではない
        $sql .= " AND (d.delivery_id IS NULL OR d.delivery_status_name != '納品済')";
    } elseif ($status === '納品済') {
        // 関連する納品が存在し、かつ「納品済」である
        $sql .= " AND d.delivery_id IS NOT NULL AND d.delivery_status_name = '納品済'";
    }
}

// 支店名 (部分一致検索)
if (!empty($branchName)) {
    $sql .= " AND b.branch_name LIKE ?";
    $params[] = '%' . $branchName . '%';
}

// グループ化と並び替え
$sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC, o.order_id DESC";

// ページングのための合計件数取得
// サブクエリとして利用するため、LIMIT/OFFSETは含まない
$countSql = "SELECT COUNT(DISTINCT o.order_id) FROM (" . $sql . ") AS subquery_for_count";
$countParams = $params; // クエリパラメータは共通

try {
    $countStmt = $pdo->prepare($countSql);
    // LIMIT/OFFSETパラメータを削除した配列を渡す
    // $sqlの最後にLIMIT/OFFSETを付与するため、$paramsに既に含まれている
    // そのため、countStmtのexecuteにはそれらを含まない$countParamsを渡す
    $countStmt->execute(array_slice($countParams, 0, count($countParams) - 2)); 

    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // データの取得 (LIMITとOFFSETを適用)
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // 表示用のステータス調整 (未納品の場合)
    foreach ($orders as &$order) {
        if (empty($order['delivery_status_name'])) {
            $order['delivery_status_name'] = '未納品';
        }
    }
    unset($order); // 参照を解除

} catch (PDOException $e) {
    // データ取得エラー
    $orders = []; // データがないことを示す
    $totalRecords = 0;
    $totalPages = 0;
    error_log('データ取得エラー: ' . $e->getMessage() . ' (SQLSTATE: ' . $e->getCode() . ')');
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
                                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                        <td><?php echo htmlspecialchars($order['delivery_status_name'] ?? '未納品'); ?></td>
                                        <td><a href="./注文詳細.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>"><button type="button" class="btn btn-primary btn-sm">詳細</button></a></td>
                                        <td><button type="button" class="btn btn-danger btn-sm delete-order-btn" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">削除</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">該当する注文がありません。</td></tr>
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