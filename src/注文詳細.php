<?php
// db_connect.phpを利用
require_once(__DIR__ . '/db_connect.php');

// URLパラメータから注文IDを取得
$order_id = 0;
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
} elseif (isset($_REQUEST['order_id'])) {
    $order_id = intval($_REQUEST['order_id']);
}

$order = null;
$details = [];

// 表示用変数の初期化
$order_date = '';
$order_no = '';
$customer_name = '';
$order_note = '';

if ($order_id > 0) {
    try {
        // 注文基本情報を取得
        $sql = "SELECT o.order_id, o.order_date, o.note, c.customer_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $order_date = $order['order_date'] ?? '';
            $order_no = $order['order_id'] ?? '';
            $customer_name = $order['customer_name'] ?? '';
            $order_note = $order['note'] ?? '';
        }

        // 注文明細情報を取得
        $detail_sql = "SELECT od.product_id, od.quantity, od.unit_price, od.note, p.product_name
                       FROM order_details od
                       LEFT JOIN products p ON od.product_id = p.product_id
                       WHERE od.order_id = ?";
        $detail_stmt = $pdo->prepare($detail_sql);
        $detail_stmt->execute([$order_id]);
        $details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
        // 並び替え（ORDER BY）は不要なら削除

    } catch (PDOException $e) {
        error_log('データ取得エラー: ' . $e->getMessage());
        $order = null;
        $details = [];
        $order_date = '';
        $order_no = '';
        $customer_name = '';
        $order_note = '';
        echo '<pre style="color:red;">データ取得エラー: ' . $e->getMessage() . '</pre>';
    }
} else {
    // GETパラメータ名が正しいか確認
    echo '<pre style="color:red;">order_idが指定されていません。URL例: 注文詳細.php?order_id=1</pre>';
    echo '<pre style="color:blue;">$_GET: '; print_r($_GET); echo '</pre>';
    echo '<pre style="color:blue;">$_REQUEST: '; print_r($_REQUEST); echo '</pre>';
}

// 更新処理
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    try {
        $pdo->beginTransaction();

        // 注文基本情報の更新
        $order_id = intval($_POST['order_id']);
        $order_date = $_POST['order_date'] ?? '';
        $customer_name = $_POST['customer_name'] ?? '';
        $order_note = $_POST['order_note'] ?? '';

        // 顧客ID取得
        $customer_id = null;
        if ($customer_name !== '') {
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE customer_name = ?");
            $stmt->execute([$customer_name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $customer_id = $row['customer_id'];
            } else {
                throw new Exception('顧客名が見つかりません');
            }
        }

        $stmt = $pdo->prepare("UPDATE orders SET order_date = ?, customer_id = ?, note = ? WHERE order_id = ?");
        $stmt->execute([$order_date, $customer_id, $order_note, $order_id]);

        // 注文明細の更新（全削除→再登録）
        $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([$order_id]);

        $product_names = $_POST['product_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $abstracts = $_POST['abstract'] ?? [];

        for ($i = 0; $i < count($product_names); $i++) {
            $product_name = trim($product_names[$i]);
            if ($product_name === '') continue;

            // 商品ID取得
            $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_name = ? OR short_name = ?");
            $stmt->execute([$product_name, $product_name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $product_id = $row['product_id'];
            } else {
                // 商品がなければ新規登録
                $stmt = $pdo->query("SELECT MAX(product_id) AS max_id FROM products");
                $max_id = $stmt->fetchColumn();
                $product_id = $max_id ? $max_id + 1 : 1;
                $stmt = $pdo->prepare("INSERT INTO products (product_id, short_name, product_name) VALUES (?, ?, ?)");
                $stmt->execute([$product_id, '', $product_name]);
            }

            $quantity = is_numeric($quantities[$i]) ? intval($quantities[$i]) : 0;
            $unit_price = is_numeric($unit_prices[$i]) ? floatval($unit_prices[$i]) : 0;
            $note = $abstracts[$i] ?? '';

            $stmt = $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity, unit_price, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $quantity, $unit_price, $note]);
        }

        $pdo->commit();
        $update_message = '<div class="alert alert-success">編集内容を保存しました。</div>';

        // ここで最新データを再取得してフォームに反映
        $sql = "SELECT o.order_id, o.order_date, o.note, c.customer_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                WHERE o.order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $order_date = $order['order_date'] ?? '';
            $order_no = $order['order_id'] ?? '';
            $customer_name = $order['customer_name'] ?? '';
            $order_note = $order['note'] ?? '';
        }

        $detail_sql = "SELECT od.product_id, od.quantity, od.unit_price, od.note, p.product_name
                       FROM order_details od
                       LEFT JOIN products p ON od.product_id = p.product_id
                       WHERE od.order_id = ?";
        $detail_stmt = $pdo->prepare($detail_sql);
        $detail_stmt->execute([$order_id]);
        $details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
        // 並び替え（ORDER BY）は不要なら削除

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $update_message = '<div class="alert alert-danger">更新エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
                <li><a href="./注文管理.html">注文管理</a></li>
                <li><a href="./納品管理.html">納品管理</a></li>
                <li><a href="./顧客取込.html">顧客登録</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mt-5">
        <?php echo $update_message; ?>
        <form method="post" id="order-edit-form">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_no); ?>">
            <div>
                <div class="d-flex justify-content-between">
                    <span>注文書</span>
                    <input type="date" name="order_date" value="<?php echo htmlspecialchars($order_date); ?>">
                    <span>
                        <label for="customer-order-no">No.</label>
                        <input type="text" id="customer-order-no" size="4" readonly value="<?php echo htmlspecialchars($order_no); ?>">
                    </span>
                </div>
                <div>
                    <input type="text" id="customer-name" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>">
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
                        <?php for ($i = 0; $i < 15; $i++): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><input type="text" name="product_name[]" value="<?php echo htmlspecialchars($details[$i]['product_name'] ?? ''); ?>"></td>
                            <td><input type="text" name="quantity[]" value="<?php echo htmlspecialchars($details[$i]['quantity'] ?? ''); ?>"></td>
                            <td>&yen;<input type="text" name="unit_price[]" style="width: 90%;" value="<?php echo htmlspecialchars($details[$i]['unit_price'] ?? ''); ?>"></td>
                            <td><input type="text" name="abstract[]" value="<?php echo htmlspecialchars($details[$i]['note'] ?? ''); ?>"></td>
                            <?php if ($i === 0): ?>
                                <td rowspan="15"><textarea rows="5" name="order_note"><?php echo htmlspecialchars($order_note); ?></textarea></td>
                            <?php endif; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center">
                <input type="button" id="order-cansel-button" class="btn btn-danger" value="戻る">
                <input type="button" id="order-insert-button" class="btn btn-success" value="編集完了">
            </div>
            <!-- 編集確認モーダル -->
            <div class="modal fade" id="order-insert" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">注文書の編集完了をします</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div>本当に編集完了しますか？</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                            <button type="submit" name="update" class="btn btn-success" id="modal-update-btn">編集完了する</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <!-- ...existing code... -->
    </main>

    <div class="modal fade" id="order-cansel" tabindex="-1"> <!--  id属性の値をポップアップの名前をつけ、変更する  -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">注文書の作成を中断しますか？</h5> <!--  ポップアップのタイトルを変更する 太字になるところです  -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に中断して戻りますか？</div> <!--  ポップアップのメッセージを変更する 太字じゃないところです  -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./注文管理.html"><button type="button" class="btn btn-danger"
                                onclick="hideForm()">戻る</button></a> <!--  href属性の値を変更する ./遷移後の画面.htmlにする  -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="order-delete" tabindex="-1"> <!--  id属性の値をポップアップの名前をつけ、変更する  -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">削除しますか</h5> <!--  ポップアップのタイトルを変更する 太字になるところです  -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に削除しますか？</div> <!--  ポップアップのメッセージを変更する 太字じゃないところです  -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./注文管理.html"><button type="button" class="btn btn-danger"
                                onclick="hideForm()">削除する</button></a> <!--  href属性の値を変更する ./遷移後の画面.htmlにする  -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS読み込み（jQuery → Bootstrap） -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- ポップアップ表示のスクリプト -->
<script>
    $(function () {
        // 編集完了ボタンでのみフォーム送信
        $('#order-insert-button').on('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('order-insert'));
            modal.show();
        });
        $('#modal-update-btn').on('click', function () {
            $('#order-edit-form').submit();
        });
        $('#order-cansel-button').on('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('order-cansel'));
            modal.show();
        });
        $('#order-delete-button').on('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('order-delete'));
            modal.show();
        });
        // フォームの通常送信を禁止
        $('#order-edit-form').on('submit', function(e) {
            if (!$('#modal-update-btn').is(':focus')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>

</html>