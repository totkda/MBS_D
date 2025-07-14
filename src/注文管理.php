<?php
// 注文管理画面（DB削除機能付き）
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
            background-color: #007bff; /* 背景色を青に */
            color: #ffffff;           /* 文字色を白に */
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
                    <li><a href="./注文管理.php">注文管理</a></li>
                    <li><a href="./納品管理.php">納品管理</a></li>
                    <li><a href="./顧客取込.html">顧客登録</a></li>
                </ul>
            </nav>
        </header>
        <main class="container mt-5 d-flex">

            <!--  検索フォーム  -->
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

                    <div class="text-end mb-2">
                        <a href="./注文登録.php"><input type="button" class="btn btn-success" value="新規登録"></a>
                    </div>

                    <!--  表  -->
                    <div style="height: 500px; overflow-y: auto;">
                        <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle">
                            <caption align="top">注文書一覧</caption>
                            <thead class="table-dark table-bordered  border-light sticky-top">
                                <tr>
                                    <th>No.</th>
                                    <th>顧客名</th>
                                    <th>注文日</th>
                                    <th>ステータス</th>
                                    <th>詳細</th>
                                    <th>削除</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // DB接続
                                $host = 'localhost';
                                $db   = 'mbs'; // ←ご自身のDB名に変更
                                $user = 'root'; // ←ご自身のDBユーザー名に変更
                                $pass = ''; // ←ご自身のDBパスワードに変更
                                $charset = 'utf8mb4';
                                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                                $options = [
                                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                    PDO::ATTR_EMULATE_PREPARES => false,
                                ];
                                try {
                                    $pdo = new PDO($dsn, $user, $pass, $options);
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="6">DB接続失敗: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                    exit;
                                }
                                // 削除処理
                                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
                                    $delete_order_id = intval($_POST['delete_order_id']);
                                    // order_details, order_delivery_mapも削除
                                    $pdo->prepare('DELETE FROM order_details WHERE order_id = ?')->execute([$delete_order_id]);
                                    $pdo->prepare('DELETE FROM order_delivery_map WHERE order_id = ?')->execute([$delete_order_id]);
                                    $pdo->prepare('DELETE FROM orders WHERE order_id = ?')->execute([$delete_order_id]);
                                }
                                // 注文書一覧取得
                                $sql = 'SELECT o.order_id, o.customer_id, c.customer_name, o.order_date FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id ORDER BY o.order_id DESC';
                                $stmt = $pdo->query($sql);
                                foreach ($stmt as $row) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['order_id'] ?? '') . '</td>';
                                    echo '<td>' . htmlspecialchars($row['customer_name'] ?? '') . '</td>';
                                    echo '<td>' . htmlspecialchars($row['order_date'] ?? '') . '</td>';
                                    // ステータス（納品済みかどうか）
                                    $status_sql = 'SELECT COUNT(*) FROM deliveries WHERE deliveries.customer_id = ? AND deliveries.delivery_status_name = "納品済"';
                                    $status_stmt = $pdo->prepare($status_sql);
                                    $status_stmt->execute([$row['customer_id'] ?? 0]);
                                    $is_delivered = $status_stmt->fetchColumn() > 0 ? '納品済' : '未納品';
                                    echo '<td>' . $is_delivered . '</td>';
                                    echo '<td><a href="./注文詳細.php?order_id=' . urlencode($row['order_id'] ?? '') . '"><input type="button" class="btn btn-primary" value="詳細"></a></td>';
                                    echo '<td><form method="post" style="display:inline;"><input type="hidden" name="delete_order_id" value="' . htmlspecialchars($row['order_id'] ?? '') . '"><input type="submit" class="btn btn-danger" value="削除" onclick="return confirm(\'本当に削除しますか？\');"></form></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!--  ページング(jsで生成)  -->
                    <div class="text-center" id="pagination-area"></div>
                </div>
            </div>
        </main>
        <!-- 必要なJSの読み込み -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="./js/pagination.js"></script>
        <script src="js/注文管理検索.js"></script>
    </body>
</html>