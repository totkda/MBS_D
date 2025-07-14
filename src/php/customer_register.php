<?php
// customer_register.php
// 顧客取込画面（ファイルアップロード＆DB登録）

// DB接続共通化
require_once __DIR__ . '/../db_connect.php';

$message = '';
$imported = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['customer_file'])) {
    $file = $_FILES['customer_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpName = $file['tmp_name'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileType === 'csv') {
            if (($handle = fopen($tmpName, 'r')) !== false) {
                try {
                    $pdo->beginTransaction();
                    $rowCount = 0;
                    $successCount = 0;
                    $errorRows = [];
                    while (($data = fgetcsv($handle))) {
                        if ($rowCount === 0) {
                            $rowCount++;
                            continue;
                        }
                        $branchCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM branches WHERE branch_id = ?");
                        $branchCheckStmt->execute([$data[1] ?? null]);
                        if ($branchCheckStmt->fetchColumn() == 0) {
                            $errorRows[] = $rowCount + 1;
                            $rowCount++;
                            continue;
                        }
                        $sql = "INSERT INTO customers (customer_name, branch_id, phone_number, postal_code, address, registration_date, note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $data[0] ?? null,
                            $data[1] ?? null,
                            $data[2] ?? null,
                            $data[3] ?? null,
                            $data[4] ?? null,
                            $data[5] ?? null,
                            $data[6] ?? null
                        ]);
                        $successCount++;
                        $rowCount++;
                    }
                    $pdo->commit();
                    $message = "{$successCount}件の顧客情報を登録しました。";
                    if (!empty($errorRows)) {
                        $message .= "（支店ID不正によりスキップ: 行 " . implode(', ', $errorRows) . "）";
                    }
                    $imported = true;
                } catch (Exception $e) {
                    if (isset($pdo)) $pdo->rollBack();
                    $message = '登録エラー: ' . $e->getMessage();
                }
                fclose($handle);
            } else {
                $message = 'ファイルの読み込みに失敗しました。';
            }
        } else {
            $message = 'CSVファイル（.csv）のみ対応しています。EXCELファイル（.xlsx）は対応していません。';
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
    <title>MBSアプリ | 顧客取込</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ...既存のCSS... */
    </style>
</head>

<body>
    <!-- ナビゲーションバー -->
    <header class="container text-center">
        <?php include 'navbar.php'; ?>
    </header>
    <main class="container mt-5">
        <!-- メッセージ表示 -->
        <?php if ($message): ?>
            <div class="alert alert-info"> <?= htmlspecialchars($message) ?> </div>
        <?php endif; ?>
        <!-- ファイルアップロードフォーム -->
        <form method="post" enctype="multipart/form-data" id="import-form">
            <div>顧客表のファイルを選択してください（CSV形式 .csv のみ対応）</div>
            <input type="file" name="customer_file" class="mt-2" accept=".csv" required>
            <div class="text-center mt-3">
                <button type="submit" id="client-insert-button" class="btn btn-success">取り込む</button>
            </div>
        </form>
    </main>
    <!-- 完了モーダル -->
    <div class="modal fade" id="import-complete" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">取り込み完了</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>顧客情報の取り込みが完了しました。</div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="import-complete-close" class="btn btn-primary" data-bs-dismiss="modal">閉じる</button>
                </div>
            </div>
        </div>
    </div>
    <!-- JS読み込み（jQuery → Bootstrap） -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/src/js/customer_register.js"></script>
</body>

</html>