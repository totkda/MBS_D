<?php
// 納品管理.php

// db_connect.php を利用
require_once(__DIR__ . '/db_connect.php'); // ここで$pdoが使える

// GETリクエストで検索条件を受信
$deliveryDateSince = $_GET['deliveryDateSince'] ?? ''; // 納品日期間の開始
$deliveryDateUntil = $_GET['deliveryDateUntil'] ?? ''; // 納品日期間の終了
$customerName      = $_GET['customerName'] ?? '';
$status            = $_GET['status'] ?? 'すべて'; // delivery_status_nameに対応
$branchName        = $_GET['branchName'] ?? '';
$page              = intval($_GET['page'] ?? 1); // ページング用
$limit             = 10; // 1ページあたりの表示件数 (任意)
$offset            = ($page - 1) * $limit;

// SQLクエリの構築
$sql_base = "FROM
    deliveries d
    LEFT JOIN customers c ON d.customer_id = c.customer_id
    LEFT JOIN branches b ON c.branch_id = b.branch_id
    LEFT JOIN delivery_details dd ON d.delivery_id = dd.delivery_id
    LEFT JOIN order_delivery_map odm ON d.delivery_id = odm.delivery_id
    LEFT JOIN order_details od ON odm.order_id = od.order_id AND dd.product_id = od.product_id
    WHERE 1=1";

$where = "";
$params = [];

// 納品日期間
if (!empty($deliveryDateSince)) {
    $where .= " AND d.delivery_date >= ?";
    $params[] = $deliveryDateSince;
}
if (!empty($deliveryDateUntil)) {
    $where .= " AND d.delivery_date <= ?";
    $params[] = $deliveryDateUntil;
}

// 顧客名 (部分一致検索)
if (!empty($customerName)) {
    $where .= " AND c.customer_name LIKE ?";
    $params[] = '%' . $customerName . '%';
}

// ステータス
if ($status !== 'すべて') {
    $where .= " AND d.delivery_status_name = ?";
    $params[] = $status;
}

// 支店名
if (!empty($branchName)) {
    $where .= " AND b.branch_name LIKE ?";
    $params[] = '%' . $branchName . '%';
}

// 件数取得用SQL
$countSql = "SELECT COUNT(*) FROM deliveries d WHERE 1=1";
$countParams = [];
if (!empty($deliveryDateSince)) {
    $countSql .= " AND d.delivery_date >= ?";
    $countParams[] = $deliveryDateSince;
}
if (!empty($deliveryDateUntil)) {
    $countSql .= " AND d.delivery_date <= ?";
    $countParams[] = $deliveryDateUntil;
}
if (!empty($customerName)) {
    $countSql .= " AND d.customer_id IN (SELECT customer_id FROM customers WHERE customer_name LIKE ?)";
    $countParams[] = '%' . $customerName . '%';
}
if ($status !== 'すべて') {
    $countSql .= " AND d.delivery_status_name = ?";
    $countParams[] = $status;
}
if (!empty($branchName)) {
    $countSql .= " AND d.customer_id IN (SELECT customer_id FROM customers WHERE branch_id IN (SELECT branch_id FROM branches WHERE branch_name LIKE ?))";
    $countParams[] = '%' . $branchName . '%';
}

// データ取得用SQL
$dataSql = "SELECT d.* FROM deliveries d WHERE 1=1";
$dataParams = [];
if (!empty($deliveryDateSince)) {
    $dataSql .= " AND d.delivery_date >= ?";
    $dataParams[] = $deliveryDateSince;
}
if (!empty($deliveryDateUntil)) {
    $dataSql .= " AND d.delivery_date <= ?";
    $dataParams[] = $deliveryDateUntil;
}
if (!empty($customerName)) {
    $dataSql .= " AND d.customer_id IN (SELECT customer_id FROM customers WHERE customer_name LIKE ?)";
    $dataParams[] = '%' . $customerName . '%';
}
if ($status !== 'すべて') {
    $dataSql .= " AND d.delivery_status_name = ?";
    $dataParams[] = $status;
}
if (!empty($branchName)) {
    $dataSql .= " AND d.customer_id IN (SELECT customer_id FROM customers WHERE branch_id IN (SELECT branch_id FROM branches WHERE branch_name LIKE ?))";
    $dataParams[] = '%' . $branchName . '%';
}
$dataSql .= " ORDER BY d.delivery_date DESC, d.delivery_id DESC LIMIT ? OFFSET ?";
$dataParams[] = $limit;
$dataParams[] = $offset;

// 件数取得
try {
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRecords / $limit)); // 0件でも1ページにする

    // データ取得
    $stmt = $pdo->prepare($dataSql);
    // LIMIT, OFFSETはint型でバインドする
    $stmt->bindValue(count($dataParams)-1, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(count($dataParams), (int)$offset, PDO::PARAM_INT);
    for ($i = 0; $i < count($dataParams)-2; $i++) {
        $stmt->bindValue($i+1, $dataParams[$i]);
    }
    $stmt->execute();
    $deliveries = $stmt->fetchAll();

    // デバッグ用: 取得件数と1件目の内容を画面に表示
    // echo '<pre style="color:red;">';
    // echo 'delivery取得件数: ' . count($deliveries) . "\n";
    // if (!empty($deliveries)) {
    //     print_r($deliveries[0]);
    // }
    // echo '</pre>';

    // 追加: SQLとパラメータも表示
    // echo '<pre style="color:blue;">';
    // echo "countSql: " . $countSql . "\n";
    // echo "countParams: "; print_r($countParams);
    // echo "dataSql: " . $dataSql . "\n";
    // echo "dataParams: "; print_r($dataParams);
    // echo '</pre>';

    // デバッグ: DBの接続状態
    // try {
    //     $pdo->query('SELECT 1');
    //     echo '<pre style="color:purple;">DB接続: OK</pre>';
    // } catch (Exception $ex) {
    //     echo '<pre style="color:purple;">DB接続: NG - ' . $ex->getMessage() . '</pre>';
    // }

} catch (PDOException $e) {
    $deliveries = [];
    $totalRecords = 0;
    $totalPages = 1;
    error_log('データ取得エラー: ' . $e->getMessage() . ' (SQLSTATE: ' . $e->getCode() . ')');
    echo '<pre style="color:orange;">データ取得エラー: ' . $e->getMessage() . '</pre>';
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>MBSアプリ</title>
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
            .delivery-table-container { /* 納品テーブル用のコンテナクラス名に変更 */
                flex-grow: 1; /* 残りのスペースをテーブルが占める */
            }
        </style>
    </head>
    <body>
        <header class="container text-center">
            <nav>
                <ul>
                    <li><a href="./index.html"><input type="button" value="ホーム"></a></li>
                    <li><a href="./注文管理.php"><input type="button" value="注文管理"></a></li> <li><a href="./納品管理.php"><input type="button" value="納品管理"></a></li> <li><a href="./顧客取込.html"><input type="button" value="顧客登録"></a></li>
                </ul>
            </nav>
        </header>

        <main class="container mt-5 d-flex">

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">納品書検索</h5>

                    <div class="mt-4">
                        <div class="mb-3">
                            <div>納品日</div>
                            <input type="date" id="delivery-date-since" class="form-control" style="width: 80%; display: inline;" value="<?php echo htmlspecialchars($deliveryDateSince); ?>">
                            <label for="delivery-date-since" class="form-label">から</label><br>
                            <input type="date" id="delivery-date-until" class="form-control" style="width: 80%; display: inline;" value="<?php echo htmlspecialchars($deliveryDateUntil); ?>">
                            <label for="delivery-date-until" class="form-label">まで</label><br>
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

            <div class="delivery-table-container"> <div>
                    <div class="text-end">
                        <a href="./納品登録.html"><input type="button" class="btn btn-success" value="新規登録"></a> </div>

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
                            <tbody id="delivery-list-tbody"> <?php if (!empty($deliveries)): ?>
                                    <?php foreach ($deliveries as $delivery): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($delivery['delivery_id']); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['customer_name'] ?? '未登録顧客'); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['delivery_date']); ?></td>
                                        <td>&yen;<?php echo number_format(htmlspecialchars($delivery['total_amount'] ?? 0)); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['delivery_status_name'] ?? '不明'); ?></td>
                                        <td><a href="./納品詳細.html?delivery_id=<?php echo htmlspecialchars($delivery['delivery_id']); ?>"><input type="button" class="btn btn-primary" value="詳細"></a></td>
                                        <td><input type="button" class="btn btn-danger delete-delivery-btn" data-delivery-id="<?php echo htmlspecialchars($delivery['delivery_id']); ?>" value="削除"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">該当する納品がありません。</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center" id="pagination-area">
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
                const deliveryTableBody = document.getElementById('delivery-list-tbody'); // 削除ボタンイベントリスナー用

                // 検索フォームの入力値を取得し、URLを構築してページを再読み込みする関数
                function performSearch(newPage = 1) {
                    const deliveryDateSince = document.getElementById('delivery-date-since').value;
                    const deliveryDateUntil = document.getElementById('delivery-date-until').value;
                    const customerName = document.getElementById('customer_name').value;
                    const status = document.getElementById('status-select').value;
                    const branchName = document.getElementById('branch_name').value;

                    const params = new URLSearchParams();
                    if (deliveryDateSince) params.append('deliveryDateSince', deliveryDateSince);
                    if (deliveryDateUntil) params.append('deliveryDateUntil', deliveryDateUntil);
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
                        // PHPから取得した総ページ数をJSに渡してバリデーション
                        if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= <?php echo $totalPages; ?>) {
                            performSearch(pageNum); // 新しいページ番号で検索を実行
                        }
                    });
                });

                // 削除ボタンのイベントリスナー
                deliveryTableBody.querySelectorAll('.delete-delivery-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const deliveryIdToDelete = this.dataset.deliveryId;
                        if (confirm(`納品ID: ${deliveryIdToDelete} を本当に削除しますか？\n（この機能は未実装です。別途delete_delivery.phpを作成してください。）`)) {
                            // ここに削除処理のfetchリクエストを記述します。
                            // 例: fetch('delete_delivery.php', {
                            //     method: 'POST',
                            //     headers: { 'Content-Type': 'application/json' },
                            //     body: JSON.stringify({ deliveryId: deliveryIdToDelete })
                            // })
                            // .then(response => response.json())
                            // .then(data => {
                            //     if (data.success) {
                            //         alert('納品が削除されました。');
                            //         performSearch(<?php echo $page; ?>); // 現在のページを再読み込みして更新
                            //     } else {
                            //         alert('削除に失敗しました: ' + (data.message || ''));
                            //     }
                            // })
                            // .catch(error => {
                            //     alert('通信エラー: ' + error);
                            // });
                            console.log(`納品ID: ${deliveryIdToDelete} の削除処理を実行します。（未実装）`);
                        }
                    });
                });
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>