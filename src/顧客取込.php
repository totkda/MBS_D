<?php
require_once(__DIR__ . '/db_connect.php');
require_once(__DIR__ . '/vendor/autoload.php'); // PhpSpreadsheet利用（composerでインストール必要）

use PhpOffice\PhpSpreadsheet\IOFactory;

$import_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['customer_file'])) {
    $file = $_FILES['customer_file']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $pdo->beginTransaction();
        foreach ($sheet->getRowIterator(2) as $row) { // 1行目はヘッダー
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }
            // $data: [顧客ID, 顧客名, 支店ID, 電話番号, 郵便番号, 住所, 登録日, 備考]
            if (!empty($data[0])) {
                $stmt = $pdo->prepare("REPLACE INTO customers (customer_id, customer_name, branch_id, phone_number, postal_code, address, registration_date, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]
                ]);
            }
        }
        $pdo->commit();
        $import_message = '<div class="alert alert-success">顧客情報を取り込みました。</div>';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $import_message = '<div class="alert alert-danger">取り込みエラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
        <?php echo $import_message; ?>
        <form method="post" enctype="multipart/form-data">
            <div>顧客表のファイルを選択してください</div>
            <input type="file" name="customer_file" class="mt-2" accept=".xlsx,.xls">
            <div class="text-center mt-3">
                <input type="submit" id="client-insert-button" class="btn btn-success" value="取り込む">
            </div>
        </form>
    </main>

    <div class="modal fade" id="client-insert" tabindex="-1"> <!--  id属性の値をポップアップの名前をつけ、変更する  -->
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">顧客情報を取り込みます</h5> <!--  ポップアップのタイトルを変更する 太字になるところです  -->
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div>本当に顧客情報を取り込みますか？</div> <!--  ポップアップのメッセージを変更する 太字じゃないところです  -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                            <div class="text-end">
                                <a href="./顧客取込.html"><button type="button" class="btn btn-success"
                                        onclick="hideForm()">取り込みします</button></a> <!--  href属性の値を変更する ./遷移後の画面.htmlにする  -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <!-- JS読み込み（jQuery → Bootstrap） -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/customer_import.js"></script>
    
</body>

</html>
<!-- このエラーは「PhpSpreadsheet」がインストールされていない場合や、autoload.phpのパスが間違っている場合に出ます。
// 対策:
// 1. コマンドラインで以下を実行してライブラリをインストールしてください。
//    composer require phpoffice/phpspreadsheet
// 2. autoload.phpのパスが正しいか確認してください。
//    require_once(__DIR__ . '/vendor/autoload.php'); // vendorディレクトリがsrcの直下にある場合
//    もしプロジェクト直下なら require_once(__DIR__ . '/../vendor/autoload.php'); などに修正してください。 -->