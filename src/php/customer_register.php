<?php
// customer_registor.php
// 顧客取込画面（ファイルアップロード＆DB登録）
// DB接続設定
$host = '127.0.0.1';
$db = 'mbs';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// ファイルアップロード＆DB登録処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['customer_file'])) {
    $file = $_FILES['customer_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpName = $file['tmp_name'];
        // CSVファイルとして読み込み
        if (($handle = fopen($tmpName, 'r')) !== false) {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                $pdo->beginTransaction();
                $rowCount = 0;
                $successCount = 0;
                $errorRows = [];
                while (($data = fgetcsv($handle))) {
                    // 1行目はヘッダーと仮定
                    if ($rowCount === 0) {
                        $rowCount++;
                        continue;
                    }
                    // 支店IDがbranchesテーブルに存在するかチェック
                    $branchCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM branches WHERE branch_id = ?");
                    $branchCheckStmt->execute([$data[1] ?? null]);
                    if ($branchCheckStmt->fetchColumn() == 0) {
                        $errorRows[] = $rowCount + 1; // CSVの行番号（1始まり）
                        $rowCount++;
                        continue;
                    }
                    // CSV列: 顧客名, 支店ID, 電話番号, 郵便番号, 住所, 登録日, 備考
                    $sql = "INSERT INTO customers (customer_name, branch_id, phone_number, postal_code, address, registration_date, note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $data[0] ?? null, // 顧客名
                        $data[1] ?? null, // 支店ID
                        $data[2] ?? null, // 電話番号
                        $data[3] ?? null, // 郵便番号
                        $data[4] ?? null, // 住所
                        $data[5] ?? null, // 登録日
                        $data[6] ?? null  // 備考
                    ]);
                    $successCount++;
                    $rowCount++;
                }
                $pdo->commit();
                $message = "{$successCount}件の顧客情報を登録しました。";
                if (!empty($errorRows)) {
                    $message .= "（支店ID不正によりスキップ: 行 " . implode(', ', $errorRows) . "）";
                }
            } catch (Exception $e) {
                if (isset($pdo)) $pdo->rollBack();
                $message = '登録エラー: ' . $e->getMessage();
            }
            fclose($handle);
        } else {
            $message = 'ファイルの読み込みに失敗しました。';
        }
    } else {
        $message = 'ファイルアップロードに失敗しました。';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>MBSアプリ - 顧客取込</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ...既存のCSS... */
    </style>
</head>

<body>
    <!-- ナビゲーションバー -->
    <header class="container text-center">
        <nav class="main-nav">
            <ul>
                <li><a href="./index.php">ホーム</a></li>
                <li><a href="./注文管理.php">注文管理</a></li>
                <li><a href="./納品管理.php">納品管理</a></li>
                <li><a href="./customer_registor.php">顧客登録</a></li>
            </ul>
        </nav>
    </header>
    <main class="container mt-5">
        <!-- メッセージ表示 -->
        <?php if ($message): ?>
            <div class="alert alert-info"> <?= htmlspecialchars($message) ?> </div>
        <?php endif; ?>
        <!-- ファイルアップロードフォーム -->
        <form method="post" enctype="multipart/form-data">
            <div>顧客表のファイルを選択してください（CSV形式）</div>
            <input type="file" name="customer_file" class="mt-2" accept=".csv" required>
            <div class="text-center mt-3">
                <input type="submit" id="client-insert-button" class="btn btn-success" value="取り込む">
            </div>
        </form>
    </main>
    <!-- モーダル（ダミー） -->
    <div class="modal fade" id="client-insert" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">顧客情報を取り込みます</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に顧客情報を取り込みますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./customer_registor.php"><button type="button" class="btn btn-success">取り込みします</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- JS読み込み（jQuery → Bootstrap） -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="./js/customer_import.js"></script> -->
</body>

</html>