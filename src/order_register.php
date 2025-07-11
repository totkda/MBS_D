<?php
// order_register.php
// 注文登録画面（新規注文登録・バリデーション付き）

// データベース接続情報
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

$errors = [];
$success = false;

// POST時：登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $order_date = trim($_POST['order_date'] ?? '');
    $order_no = trim($_POST['order_no'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $items = $_POST['items'] ?? [];

    // バリデーション
    if ($customer_name === '') {
        $errors[] = '顧客名は必須です。';
    }
    if ($order_date === '') {
        $errors[] = '注文日を入力してください。';
    }
    if (!preg_match('/^\\d+$/', $order_no)) {
        $errors[] = '注文No.は数字で入力してください。';
    }
    $has_item = false;
    foreach ($items as $idx => $item) {
        if (trim($item['product_name']) === '' && trim($item['quantity']) === '' && trim($item['unit_price']) === '') {
            continue; // 空行はスキップ
        }
        $has_item = true;
        if (trim($item['product_name']) === '') {
            $errors[] = ($idx + 1) . '行目の品名は必須です。';
        }
        if (!preg_match('/^\\d+$/', $item['quantity'])) {
            $errors[] = ($idx + 1) . '行目の数量は数字で入力してください。';
        }
        if (!preg_match('/^\\d+(\\.\\d+)?$/', $item['unit_price'])) {
            $errors[] = ($idx + 1) . '行目の単価は数値で入力してください。';
        }
    }
    if (!$has_item) {
        $errors[] = '1件以上の商品を入力してください。';
    }

    if (!$errors) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            $pdo->beginTransaction();
            // 注文テーブル登録
            $stmt = $pdo->prepare('INSERT INTO orders (customer_name, order_date, order_no, remarks) VALUES (?, ?, ?, ?)');
            $stmt->execute([$customer_name, $order_date, $order_no, $remarks]);
            $order_id = $pdo->lastInsertId();
            // 明細登録
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_name, quantity, unit_price, note) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                if (trim($item['product_name']) === '' && trim($item['quantity']) === '' && trim($item['unit_price']) === '') continue;
                $stmt->execute([
                    $order_id,
                    trim($item['product_name']),
                    intval($item['quantity']),
                    floatval($item['unit_price']),
                    trim($item['note'])
                ]);
            }
            $pdo->commit();
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'DBエラー: ' . $e->getMessage();
        }
    }
}
// 初期値
if (!isset($customer_name)) $customer_name = '';
if (!isset($order_date)) $order_date = date('Y-m-d');
if (!isset($order_no)) $order_no = '';
if (!isset($remarks)) $remarks = '';
if (!isset($items) || !is_array($items) || count($items) === 0) {
    $items = [
        ['product_name' => '', 'quantity' => '', 'unit_price' => '', 'note' => ''],
        ['product_name' => '', 'quantity' => '', 'unit_price' => '', 'note' => ''],
        ['product_name' => '', 'quantity' => '', 'unit_price' => '', 'note' => ''],
        ['product_name' => '', 'quantity' => '', 'unit_price' => '', 'note' => ''],
        ['product_name' => '', 'quantity' => '', 'unit_price' => '', 'note' => ''],
    ];
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>注文登録 | MBSアプリ</title>
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
                <li><a href="./納品管理.html">納品管理</a></li>
                <li><a href="./顧客取込.html">顧客登録</a></li>
            </ul>
        </nav>
    </header>
    <main class="container mt-5">
        <!-- エラー表示 -->
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div><?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success): ?>
            <div class="alert alert-success">注文を登録しました。</div>
        <?php endif; ?>
        <!-- 注文登録フォーム -->
        <form method="post" id="order-register-form">
            <div class="d-flex justify-content-between">
                <span>注文書</span>
                <input type="date" name="order_date" value="<?php echo htmlspecialchars($order_date); ?>">
                <span>
                    <label for="customer-order-no">No.</label>
                    <input type="text" name="order_no" id="customer-order-no" size="4" value="<?php echo htmlspecialchars($order_no); ?>">
                </span>
            </div>
            <div>
                <input type="text" name="customer_name" id="customer-name" value="<?php echo htmlspecialchars($customer_name); ?>">
                <label for="customer-name">様</label>
            </div>
            <div>下記のとおり御注文申し上げます</div>
            <!-- 商品明細テーブル -->
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
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><input type="text" name="items[<?php echo $i; ?>][product_name]" value="<?php echo htmlspecialchars($item['product_name']); ?>"></td>
                                <td><input type="text" name="items[<?php echo $i; ?>][quantity]" value="<?php echo htmlspecialchars($item['quantity']); ?>"></td>
                                <td>&yen;<input type="text" style="width: 90%;" name="items[<?php echo $i; ?>][unit_price]" value="<?php echo htmlspecialchars($item['unit_price']); ?>"></td>
                                <td><input type="text" name="items[<?php echo $i; ?>][note]" value="<?php echo htmlspecialchars($item['note']); ?>"></td>
                                <?php if ($i === 0): ?>
                                    <td rowspan="<?php echo count($items); ?>"><textarea rows="5" name="remarks"><?php echo htmlspecialchars($remarks); ?></textarea></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- 明細行追加・削除はJSで実装推奨（ここでは省略） -->
            </div>
            <div class="text-center">
                <input type="button" id="order-cansel-button" class="btn btn-danger" value="戻る">
                <input type="submit" id="order-insert-button" class="btn btn-success" value="登録">
            </div>
        </form>
    </main>
    <!-- モーダル等は必要に応じて追加可能 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // モーダルや明細行追加・削除のJSは必要に応じて追加してください
    </script>
</body>

</html>