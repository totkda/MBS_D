<?php
// order_register.php
// 注文登録画面（新規注文登録・バリデーション付き）


// データベース接続情報

// POST時：登録処理

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>注文登録 | MBSアプリ</title>
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