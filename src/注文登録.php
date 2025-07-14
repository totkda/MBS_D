<?php
// データベース接続情報
define('DB_HOST', 'localhost');
define('DB_NAME', 'mbs');
define('DB_USER', 'root'); // XAMPPのデフォルトユーザー名
define('DB_PASS', '');     // XAMPPのデフォルトパスワード

$message = '';
$pdo = null;

// 登録ボタンが押されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // --- デバッグ用: テーブルとカラムの存在確認 ---
        // customersテーブルのIDカラム存在確認
        try {
            $check_stmt = $pdo->query("DESCRIBE `customers`"); // バッククォートを追加
            $columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('ID', $columns)) {
                error_log("DEBUG: 'ID' column not found in 'customers' table.");
            } else {
                error_log("DEBUG: 'ID' column found in 'customers' table.");
            }
        } catch (PDOException $e) {
            error_log("DEBUG: Error describing 'customers' table: " . $e->getMessage());
        }

        // productsテーブルのIDカラム存在確認
        try {
            $check_stmt = $pdo->query("DESCRIBE `products`"); // バッククォートを追加
            $columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('ID', $columns)) {
                error_log("DEBUG: 'ID' column not found in 'products' table.");
            } else {
                error_log("DEBUG: 'ID' column found in 'products' table.");
            }
        } catch (PDOException $e) {
            error_log("DEBUG: Error describing 'products' table: " . $e->getMessage());
        }
        // --- デバッグ用終了 ---


        // トランザクションを開始
        $pdo->beginTransaction();

        // フォームからデータを受け取る
        $order_date = $_POST['order_date'] ?? null;
        $customer_name_input = $_POST['customer_name'] ?? '';
        $customer_id = null;

        // 顧客ID取得
        if (!empty($customer_name_input)) {
            $sql_customer_id = "SELECT customer_id FROM customers WHERE customer_name = :customer_name";
            $stmt_customer_id = $pdo->prepare($sql_customer_id);
            $stmt_customer_id->bindParam(':customer_name', $customer_name_input);
            $stmt_customer_id->execute();
            $fetched_customer = $stmt_customer_id->fetch(PDO::FETCH_ASSOC);
            if ($fetched_customer) {
                $customer_id = $fetched_customer['customer_id'];
            } else {
                throw new Exception("指定された顧客名 '{$customer_name_input}' が見つかりません。");
            }
        } else {
            throw new Exception("顧客名が入力されていません。");
        }

        // 注文書の備考
        $order_note = $_POST['overall_remarks'] ?? null;

        // ordersのorder_idを最大値+1で発番
        $order_id = 1;
        $sql_max_order = "SELECT MAX(order_id) AS max_id FROM orders";
        $stmt_max_order = $pdo->query($sql_max_order);
        $row_max_order = $stmt_max_order->fetch(PDO::FETCH_ASSOC);
        if ($row_max_order && $row_max_order['max_id'] !== null) {
            $order_id = $row_max_order['max_id'] + 1;
        }

        // orders テーブルへの挿入
        $sql_order = "INSERT INTO orders (order_id, order_date, customer_id, note) VALUES (?, ?, ?, ?)";
        $stmt_order = $pdo->prepare($sql_order);
        $stmt_order->execute([
            (int)$order_id,
            $order_date !== null ? $order_date : date('Y-m-d'),
            (int)$customer_id,
            $order_note !== null ? $order_note : ''
        ]);

        // order_details の挿入
        $product_names = $_POST['product_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $abstracts = $_POST['abstract'] ?? [];

        $used_product_ids = [];
        for ($i = 0; $i < count($product_names); $i++) {
            $current_product_name = trim($product_names[$i]);
            $current_quantity = $quantities[$i] ?? null;
            $current_unit_price = $unit_prices[$i] ?? null;
            $current_abstract = $abstracts[$i] ?? null;

            if (!empty($current_product_name)) {
                // product_id を product_name から検索
                $sql_product_id = "SELECT product_id FROM products WHERE product_name = :product_name OR short_name = :product_name";
                $stmt_product_id = $pdo->prepare($sql_product_id);
                $stmt_product_id->bindParam(':product_name', $current_product_name);
                $stmt_product_id->execute();
                $fetched_product = $stmt_product_id->fetch(PDO::FETCH_ASSOC);

                if ($fetched_product) {
                    $product_id = $fetched_product['product_id'];
                } else {
                    $message .= '<div class="alert alert-warning" role="alert">警告: 品名 "' . htmlspecialchars($current_product_name) . '" が商品マスタに見つかりません。この商品は登録されません。</div>';
                    continue;
                }

                // product_idの重複チェック
                if (in_array($product_id, $used_product_ids, true)) {
                    $message .= '<div class="alert alert-warning" role="alert">警告: 商品ID ' . htmlspecialchars($product_id) . ' は既にこの注文で登録済みのためスキップされました。</div>';
                    continue;
                }
                $used_product_ids[] = $product_id;

                // order_details テーブルへの挿入
                $sql_detail = "INSERT INTO order_details (order_id, product_id, quantity, unit_price, note) VALUES (?, ?, ?, ?, ?)";
                $stmt_detail = $pdo->prepare($sql_detail);
                $stmt_detail->execute([
                    (int)$order_id,
                    (int)$product_id,
                    is_numeric($current_quantity) ? (int)$current_quantity : 0,
                    is_numeric($current_unit_price) ? (float)$current_unit_price : 0,
                    $current_abstract !== null ? $current_abstract : ''
                ]);
            }
        }

        $pdo->commit();
        $message .= '<div class="alert alert-success" role="alert">データが正常に登録されました。</div>';

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = '<div class="alert alert-danger" role="alert">登録エラー: ' . $e->getMessage() . '</div>';
    }
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
            color: #ffffff; /* 文字色を白に */
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
                <!-- ナビゲーションボタンの修正: input type="button" を削除し、aタグ内にテキストを直接記述 -->
                <li><a href="./index.html">ホーム</a></li>
                <li><a href="./注文管理.html">注文管理</a></li>
                <li><a href="./納品管理.html">納品管理</a></li>
                <li><a href="./顧客取込.html">顧客登録</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mt-5">
        <?php echo $message; // 登録成功/失敗メッセージを表示 ?>
        <form method="POST" action="">
            <div>
                <div class="d-flex justify-content-between">
                    <span>注文書</span>
                    <input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>">
                    <span>
                        <label for="customer-order-no">No.</label>
                        <input type="text" id="customer-order-no" name="customer_order_no" size="4" readonly value="2">
                    </span>
                </div>
                <div>
                    <input type="text" id="customer-name" name="customer_name" placeholder="顧客名を入力">
                    <label for="customer-name">様</label>
                </div>
                <div>
                    下記のとおり御注文申し上げます
                </div>
            </div>
            <div style="height: 300px; overflow-y: auto;">
                <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle">
                    <colgroup>
                        <col style="width: 2%;">
                        <col style="width: 30%;">
                        <col style="width: 8%;">
                        <col style="width: 8%;">
                        <col style="width: 24%;">
                        <col style="width: 24%;">
                    </colgroup>
                    <thead class="table-dark table-bordered border-light sticky-top">
                        <tr>
                            <th colspan="2">品名</th>
                            <th>数量</th>
                            <th>単価</th>
                            <th>摘要</th>
                            <th>備考</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><input type="text" name="product_name[]" value="週刊BCN 10/17"></td>
                            <td><input type="text" name="quantity[]" value="1"></td>
                            <td>&yen;<input type="text" name="unit_price[]" style="width: 90%;" value="363"></td>
                            <td><input type="text" name="abstract[]"></td>
                            <td rowspan="15"><textarea rows="5" name="overall_remarks"></textarea></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><input type="text" name="product_name[]"></td>
                            <td><input type="text" name="quantity[]"></td>
                            <td>&yen;<input type="text" name="unit_price[]" style="width: 90%;"></td>
                            <td><input type="text" name="abstract[]"></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><input type="text" name="product_name[]"></td>
                            <td><input type="text" name="quantity[]"></td>
                            <td>&yen;<input type="text" name="unit_price[]" style="width: 90%;"></td>
                            <td><input type="text" name="abstract[]"></td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td><input type="text" name="product_name[]"></td>
                            <!-- HTML修正: quantity[] の &yen; を削除 -->
                            <td><input type="text" name="quantity[]"></td>
                            <td>&yen;<input type="text" name="unit_price[]" style="width: 90%;"></td>
                            <td><input type="text" name="abstract[]"></td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td><input type="text" name="product_name[]"></td>
                            <td><input type="text" name="quantity[]"></td>
                            <td>&yen;<input type="text" name="unit_price[]" style="width: 90%;"></td>
                            <td><input type="text" name="abstract[]"></td>
                        </tr>
                    </tbody>
                </table>
                <div class="text-end">
                    <input type="button" value="+">
                    <input type="button" value="-">
                </div>
            </div>
            <div class="text-center">
                <a href="./注文管理.html"><input type="button" class="btn btn-danger" value="戻る"></a>
                <button type="submit" name="register" class="btn btn-success">登録</button>
            </div>
        </form>
    </main>
</body>

</html>
