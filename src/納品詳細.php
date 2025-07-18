<?php
require_once 'db_connect.php'; // DB接続ファイルを読み込み

// クエリパラメータからdelivery_idを取得
$deliveryId = $_GET['delivery_id'] ?? null;

if (!$deliveryId) {
    die('納品IDが指定されていません。');
}

try {

    // 納品書情報を取得
    $sql = "
        SELECT 
            d.delivery_id,
            d.delivery_date,
            c.customer_name,
            c.address,
            c.phone_number,
            d.delivery_status_name
        FROM deliveries d
        LEFT JOIN customers c ON d.customer_id = c.customer_id
        WHERE d.delivery_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deliveryId]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$delivery) {
        die('指定された納品IDのデータが見つかりません。');
    }

    // 納品書詳細情報を取得
    $sqlDetails = "
        SELECT 
            dd.product_id,
            p.product_name,
            dd.quantity,
            od.unit_price AS price,
            (dd.quantity * od.unit_price) AS total_price
        FROM delivery_details dd
        LEFT JOIN products p ON dd.product_id = p.product_id
        LEFT JOIN order_details od ON dd.product_id = od.product_id
        WHERE dd.delivery_id = ?
    ";
    $stmtDetails = $pdo->prepare($sqlDetails);
    $stmtDetails->execute([$deliveryId]);
    $deliveryDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('データベースエラー: ' . $e->getMessage());
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
                <li><a href="./index.php">ホーム</a></li>
                <li><a href="./注文管理.php">注文管理</a></li>
                <li><a href="./納品管理.php">納品管理</a></li>
                <li><a href="./顧客取込.php">顧客登録</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mt-5">
        <div>
            <div class="text-end">
                <input type="button" class="btn btn-primary" value="pdfダウンロード">
                <input type="button" id="delivery-delete-button" class="btn btn-danger" value="削除">
            </div>
        </div>
        <form>
            <div>
                <div class="d-flex justify-content-between">
                    <span>納品書</span>
                    <input type="date" value="<?= htmlspecialchars($delivery['delivery_date']) ?>" readonly>
                    <span>
                        <label for="customer-delivery-no">No.</label>
                        <input type="text" id="customer-delivery-no" size="4" value="<?= htmlspecialchars($delivery['delivery_id']) ?>" readonly>
                    </span>
                </div>
                <div>
                    <input type="text" id="customer-name" value="<?= htmlspecialchars($delivery['customer_name']) ?>" readonly>
                    <label for="customer-name">様</label>
                </div>
                <div>
                    下記のとおり納品いたしました
                </div>
            </div>

            <!-- 下部 -->
            <div>
                <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle mb-0">
                    <colgroup>
                        <col style="width: 2%;">
                        <col style="width: 42%;">
                        <col style="width: 11%;">
                        <col style="width: 11%;">
                        <col style="width: 33%;">
                    </colgroup>
                    <thead class="table-dark table-bordered border-light sticky-top">
                        <tr>
                            <th colspan="2">品名</th>
                            <th>数量</th>
                            <th>単価</th>
                            <th>金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveryDetails as $index => $detail): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($detail['product_name']) ?></td>
                            <td><?= htmlspecialchars($detail['quantity']) ?></td>
                            <td>&yen;<?= number_format($detail['price']) ?></td>
                            <td>&yen;<?= number_format($detail['total_price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">合計</th>
                            <td><?= array_sum(array_column($deliveryDetails, 'quantity')) ?></td>
                            <td></td>
                            <td>&yen;<?= number_format(array_sum(array_column($deliveryDetails, 'total_price'))) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>
        <div class="text-center">
            <input type="button" id="delivery-cansel-button" class="btn btn-danger" value="戻る">
            <input type="button" id="delivery-insert-button" class="btn btn-success" value="編集完了">
        </div>
    </main>

    <!-- モーダル -->
    <div class="modal fade" id="delivery-insert" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">納品書の編集完了をします</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に編集完了しますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./納品管理.php"><button type="button" class="btn btn-success">編集完了する</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delivery-cansel" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">納品書の作成を中断しますか？</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に中断して戻りますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./納品管理.php"><button type="button" class="btn btn-danger">戻る</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delivery-delete" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">削除します</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に削除しますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./納品管理.php"><button type="button" class="btn btn-danger">削除する</button></a>
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
            $('#delivery-insert-button').on('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('delivery-insert'));
                modal.show();
            });

            $('#delivery-cansel-button').on('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('delivery-cansel'));
                modal.show();
            });

            $('#delivery-delete-button').on('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('delivery-delete'));
                modal.show();
            });
        });
    </script>
</body>

</html>