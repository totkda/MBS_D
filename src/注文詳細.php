<?php
require_once __DIR__ . '/db_connect.php';
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order_details = [];
$order_info = [];
if ($order_id > 0) {
    // 注文明細
    $sql = 'SELECT od.*, p.short_name, p.product_name FROM order_details od LEFT JOIN products p ON od.product_id = p.product_id WHERE od.order_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // 注文基本情報（orders + customers）
    $sql2 = 'SELECT o.order_date, o.order_id, c.customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ?';
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$order_id]);
    $order_info = $stmt2->fetch(PDO::FETCH_ASSOC);
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
                <li><a href="./index.html">ホーム</a></li>
                <li><a href="./注文管理.html">注文管理</a></li>
                <li><a href="./納品管理.html">納品管理</a></li>
                <li><a href="./顧客取込.html">顧客登録</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mt-5">
        <div>
            <div class="text-end">
                <button type="button" class="btn btn-primary">pdfダウンロード</button>
                <button type="button" id="order-delete-button" class="btn btn-danger">削除</button>
            </div>
            <div class="d-flex justify-content-between">
                <span>注文書</span>
                <input type="date" value="<?= htmlspecialchars($order_info['order_date'] ?? '') ?>">
                <span>
                    <label for="customer-order-no">No.</label>
                    <input type="text" id="customer-order-no" size="4" readonly value="<?= htmlspecialchars($order_info['order_id'] ?? '') ?>">
                </span>
            </div>
            <div>
                <input type="text" id="customer-name" value="<?= htmlspecialchars($order_info['customer_name'] ?? '') ?>">
                <label for="customer-name">様</label>
            </div>
            <div>
                下記のとおり御注文申し上げます
            </div>
        </div>
        <!--  表  -->
        <div style="height: 300px; overflow-y: auto;">
            <table class="table table-bordered  border-dark  table-striped table-hover table-sm align-middle">
                <colgroup>
                    <col style="width: 2%;">
                    <col style="width: 30%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 24%;">
                    <col style="width: 24%;">
                </colgroup>
                <thead class="table-dark table-bordered  border-light sticky-top">
                    <tr>
                        <th colspan="2">品名</th>
                        <th>数量</th>
                        <th>単価</th>
                        <th>摘要</th>
                        <th>備考</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($order_id > 0 && $order_details): ?>
                        <?php foreach ($order_details as $i => $detail): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <input type="text" value="<?php if (!empty($detail['short_name']) && !empty($detail['product_name'])): ?><?= htmlspecialchars($detail['short_name']) ?>（<?= htmlspecialchars($detail['product_name']) ?>）<?php else: ?><?= htmlspecialchars($detail['product_id'] ?? '') ?><?php endif; ?>">
                                </td>
                                <td><input type="text" value="<?= htmlspecialchars($detail['quantity'] ?? '') ?>"></td>
                                <td>&yen;<input type="text" style="width: 90%;" value="<?= htmlspecialchars($detail['unit_price'] ?? '') ?>"></td>
                                <td><input type="text" value="<?= htmlspecialchars($detail['note'] ?? '') ?>"></td>
                                <td rowspan="1"><textarea rows="2"><?= htmlspecialchars($detail['remarks'] ?? '') ?></textarea></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">注文明細がありません</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center">
            <input type="button" id="order-cansel-button" class="btn btn-danger" value="戻る">
            <input type="button" id="order-insert-button" class="btn btn-success" value="編集完了">
        </div>
    </main>

    <div class="modal fade" id="order-insert" tabindex="-1"> <!--  id属性の値をポップアップの名前をつけ、変更する  -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">注文書の編集完了をします</h5> <!--  ポップアップのタイトルを変更する 太字になるところです  -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に編集完了しますか？</div> <!--  ポップアップのメッセージを変更する 太字じゃないところです  -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./注文管理.html"><button type="button" class="btn btn-success"
                                onclick="hideForm()">編集完了する</button></a> <!--  href属性の値を変更する ./遷移後の画面.htmlにする  -->
                    </div>
                </div>
            </div>
        </div>
    </div>

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