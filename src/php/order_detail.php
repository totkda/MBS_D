<?php
// order_detail.php
// 注文詳細画面（DBから注文データを取得して表示・編集）

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

// 注文ID取得（GETパラメータ）

// $order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// $order = null;
// $order_items = [];
// $errors = [];
// $success = false;

// try {
//     $pdo = new PDO($dsn, $user, $pass, $options);

//     // POST時：編集保存処理
//     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//         // バリデーション
//         $customer_name = trim($_POST['customer_name'] ?? '');
//         $order_date = trim($_POST['order_date'] ?? '');
//         $order_no = trim($_POST['order_no'] ?? '');
//         $remarks = trim($_POST['remarks'] ?? '');
//         $items = $_POST['items'] ?? [];

//         if ($customer_name === '') {
//             $errors[] = '顧客名は必須です。';
//         }
//         if ($order_date === '') {
//             $errors[] = '注文日を入力してください。';
//         }
//         if (!preg_match('/^\\d+$/', $order_no)) {
//             $errors[] = '注文No.は数字で入力してください。';
//         }
//         // 商品明細バリデーション
//         foreach ($items as $idx => $item) {
//             if (trim($item['product_name']) === '' && trim($item['quantity']) === '' && trim($item['unit_price']) === '') {
//                 continue; // 空行はスキップ
//             }
//             if (trim($item['product_name']) === '') {
//                 $errors[] = ($idx + 1) . '行目の品名は必須です。';
//             }
//             if (!preg_match('/^\\d+$/', $item['quantity'])) {
//                 $errors[] = ($idx + 1) . '行目の数量は数字で入力してください。';
//             }
//             if (!preg_match('/^\\d+(\\.\\d+)?$/', $item['unit_price'])) {
//                 $errors[] = ($idx + 1) . '行目の単価は数値で入力してください。';
//             }
//         }

//         if (!$errors) {
//             // トランザクションで更新
//             $pdo->beginTransaction();
//             // 注文テーブル更新
//             $stmt = $pdo->prepare('UPDATE orders SET customer_name=?, order_date=?, order_no=?, remarks=? WHERE id=?');
//             $stmt->execute([$customer_name, $order_date, $order_no, $remarks, $order_id]);
//             // 明細は一旦全削除→再登録（簡易実装）
//             $pdo->prepare('DELETE FROM order_items WHERE order_id=?')->execute([$order_id]);
//             $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_name, quantity, unit_price, note) VALUES (?, ?, ?, ?, ?)');
//             foreach ($items as $item) {
//                 if (trim($item['product_name']) === '' && trim($item['quantity']) === '' && trim($item['unit_price']) === '') continue;
//                 $stmt->execute([
//                     $order_id,
//                     trim($item['product_name']),
//                     intval($item['quantity']),
//                     floatval($item['unit_price']),
//                     trim($item['note'])
//                 ]);
//             }
//             $pdo->commit();
//             $success = true;
//         }
//     }

//     // 最新データ取得
//     $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
//     $stmt->execute([$order_id]);
//     $order = $stmt->fetch();
//     $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
//     $stmt->execute([$order_id]);
//     $order_items = $stmt->fetchAll();
// } catch (PDOException $e) {
//     die('DB接続エラー: ' . $e->getMessage());
// }

// if (!$order) {
//     die('注文が見つかりません。');
// }
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>注文詳細 | MBSアプリ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <header class="container text-center">
        <?php include 'navbar.php'; ?>
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
            <div class="alert alert-success">編集内容を保存しました。</div>
        <?php endif; ?>
        <!-- 注文詳細フォーム -->
        <form method="post" id="order-edit-form">
            <div class="text-end">
                <button type="button" class="btn btn-primary">pdfダウンロード</button>
                <button type="button" id="order-delete-button" class="btn btn-danger">削除</button>
            </div>
            <div class="d-flex justify-content-between">
                <span>注文書</span>
                <input type="date" name="order_date" value="<?php echo htmlspecialchars($order['order_date']); ?>">
                <span>
                    <label for="customer-order-no">No.</label>
                    <input type="text" name="order_no" id="customer-order-no" size="4" value="<?php echo htmlspecialchars($order['order_no']); ?>" readonly>
                </span>
            </div>
            <div>
                <input type="text" name="customer_name" id="customer-name" value="<?php echo htmlspecialchars($order['customer_name']); ?>">
                <label for="customer-name">様</label>
            </div>
            <div>下記のとおり御注文申し上げます</div>
            <!-- 注文商品テーブル -->
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
                        <?php foreach ($order_items as $i => $item): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><input type="text" name="items[<?php echo $i; ?>][product_name]" value="<?php echo htmlspecialchars($item['product_name']); ?>"></td>
                                <td><input type="text" name="items[<?php echo $i; ?>][quantity]" value="<?php echo htmlspecialchars($item['quantity']); ?>"></td>
                                <td>&yen;<input type="text" style="width: 90%;" name="items[<?php echo $i; ?>][unit_price]" value="<?php echo htmlspecialchars($item['unit_price']); ?>"></td>
                                <td><input type="text" name="items[<?php echo $i; ?>][note]" value="<?php echo htmlspecialchars($item['note']); ?>"></td>
                                <?php if ($i === 0): ?>
                                    <td rowspan="<?php echo count($order_items); ?>"><textarea rows="5" name="remarks"><?php echo htmlspecialchars($order['remarks']); ?></textarea></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center">
                <input type="button" id="order-cansel-button" class="btn btn-danger" value="戻る">
                <input type="submit" id="order-insert-button" class="btn btn-success" value="編集完了">
            </div>
        </form>
    </main>
    <!-- 編集完了モーダル -->
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
                    <div class="text-end">
                        <a href="./注文管理.html"><button type="button" class="btn btn-success" onclick="hideForm()">編集完了する</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- 戻るモーダル -->
    <div class="modal fade" id="order-cansel" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">注文書の作成を中断しますか？</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に中断して戻りますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./注文管理.html"><button type="button" class="btn btn-danger" onclick="hideForm()">戻る</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- 削除モーダル -->
    <div class="modal fade" id="order-delete" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">削除しますか</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に削除しますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./注文管理.html"><button type="button" class="btn btn-danger" onclick="hideForm()">削除する</button></a>
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
        $(function() {
            $('#order-insert-button').on('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('order-insert'));
                modal.show();
            });
            $('#order-cansel-button').on('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('order-cansel'));
                modal.show();
            });
            $('#order-delete-button').on('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('order-delete'));
                modal.show();
            });
        });
    </script>
</body>

</html>