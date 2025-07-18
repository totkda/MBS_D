<?php
require_once(__DIR__ . '/db_connect.php');

// 納品ID取得
$delivery_id = isset($_GET['delivery_id']) ? intval($_GET['delivery_id']) : 0;

// 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $delivery_id > 0) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM delivery_details WHERE delivery_id = ?");
        $stmt->execute([$delivery_id]);
        $stmt = $pdo->prepare("DELETE FROM deliveries WHERE delivery_id = ?");
        $stmt->execute([$delivery_id]);
        $pdo->commit();
        // 削除後は管理画面にリダイレクト
        header("Location: 納品管理.html");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $delete_message = '<div class="alert alert-danger">削除エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
        <?php echo $delete_message ?? ''; ?>
        <div>
            <div class="text-end">
                <input type="button" class="btn btn-primary" value="pdfダウンロード">
                <input type="button" id="delivery-delete-button" class="btn btn-danger" value="削除">
            </div>
        </div>
        <form method="post">
            <div>
                <div class="d-flex justify-content-between">
                    <span>納品書</span>
                    <input type="date">
                    <span>
                        <label for="customer-delivery-no">No.</label>
                        <input type="text" id="customer-delivery-no" size="4" readonly>
                    </span>
                </div>
                <div>
                    <input type="text" id="customer-name">
                    <label for="customer-name">様</label>
                </div>
                <div>
                    下記のとおり納品いたしました
                </div>
            </div>

            <!-- 下部 -->
            <div>
                <table class="table table-bordered  border-dark  table-striped table-hover table-sm align-middle mb-0"">
                    <colgroup>
                        <col style="width: 2%;">
                        <col style="width: 42%;">
                        <col style="width: 11%;">
                        <col style="width: 11%;">
                        <col style="width: 33%;">
                    </colgroup>
                    <thead class="table-dark table-bordered  border-light sticky-top">
                        <tr>
                            <th colspan="2">品名</th>
                            <th>数量</th>
                            <th>単価</th>
                            <th>
                                <span>金額(</span>
                                <input type="radio" name="price" id="price-excluded">
                                <label for="price-excluded">税抜</label>
                                <input type="radio" name="price" id="price-included">
                                <label for="price-included">税込)</label>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>日経コンピュータ 11月号</td>
                            <td>1</td>
                            <td>&yen;1300</td>
                            <td>&yen;1300</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>日経ネットワーク 11月号</td>
                            <td>1</td>
                            <td>&yen;1300</td>
                            <td>&yen;1300</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">合計</th>
                            <td>2</td>
                            <td></td>
                            <td>&yen;2600</td>
                        </tr>
                    </tfoot>
                </table>
                <table class="table table-bordered  border-dark  table-striped table-hover table-sm align-middle mt-0">
                    <colgroup>
                        <col style="width: 12%;">
                        <col style="width: 12%;">
                        <col style="width: 12%;">
                        <col style="width: 19%;">
                        <col style="width: 11%;">
                        <col style="width: 33%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td>税率</td>
                            <td>%</td>
                            <td>消費税率等</td>
                            <td></td>
                            <td>税込合計金額</td>
                            <td>&yen;2600</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
        <div class="text-center">
            <input type="button" id="delivery-cansel-button" class="btn btn-danger" value="戻る">
            <input type="button" id="delivery-insert-button" class="btn btn-success" value="編集完了">
        </div>
    </main>
    <script src="./js/delivery_detail.js"></script>
<div class="modal fade" id="delivery-insert" tabindex="-1"> <!--  id属性の値をポップアップの名前をつけ、変更する  -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">納品書の編集完了をします</h5> <!--  ポップアップのタイトルを変更する 太字になるところです  -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に編集完了しますか？</div> <!--  ポップアップのメッセージを変更する 太字じゃないところです  -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./納品管理.html"><button type="button" class="btn btn-success"
                                onclick="hideForm()">編集完了する</button></a> <!--  href属性の値を変更する ./遷移後の画面.htmlにする  -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delivery-cansel" tabindex="-1"> <!--  id属性の値をポップアップの名前をつけ、変更する  -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">納品書の作成を中断しますか？</h5> <!--  ポップアップのタイトルを変更する 太字になるところです  -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に中断して戻りますか？</div> <!--  ポップアップのメッセージを変更する 太字じゃないところです  -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./納品管理.html"><button type="button" class="btn btn-danger"
                                onclick="hideForm()">戻る</button></a> <!--  href属性の値を変更する ./遷移後の画面.htmlにする  -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delivery-delete" tabindex="-1"> <!--  id属性の値をポップアップの名前をつけ、変更する  -->
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">削除します</h5> <!--  ポップアップのタイトルを変更する 太字になるところです  -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に削除しますか？</div> <!--  ポップアップのメッセージを変更する 太字じゃないところです  -->
                </div>
                <div class="modal-footer">
                    <form method="post" style="display:inline;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                        <button type="submit" name="delete" class="btn btn-danger">削除する</button>
                    </form>
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