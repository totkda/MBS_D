<?php
// ファイルアップロード処理
$uploadSuccess = false;
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['customer_file'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = basename($_FILES['customer_file']['name']);
    $uploadFile = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['customer_file']['tmp_name'], $uploadFile)) {
        $uploadSuccess = true;
    } else {
        $errorMsg = 'ファイルのアップロードに失敗しました。';
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
        nav ul { list-style-type: none; }
        nav ul li { display: inline; }
        .container { padding: 20px 0; }
        .main-nav ul { display: flex; justify-content: center; list-style: none; margin: 0; padding: 0; gap: 15px; }
        .main-nav a {
            display: inline-block; padding: 10px 24px; font-family: "Helvetica", "Arial", sans-serif;
            font-size: 16px; color: #333; background-color: #f4f4f4; text-decoration: none;
            border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .main-nav a:hover {
            background-color: #007bff; color: #fff; border-color: #0069d9;
            transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
                <li><a href="./customer_registor.php">顧客登録</a></li>
            </ul>
        </nav>
    </header>
    <main class="container mt-5">
        <?php if ($uploadSuccess): ?>
            <div class="alert alert-success">ファイルのアップロードが完了しました。</div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div>顧客表のファイルを選択してください</div>
            <input type="file" name="customer_file" class="mt-2" required>
            <div class="text-center mt-3">
                <input type="submit" id="client-insert-button" class="btn btn-success" value="取り込む">
            </div>
        </form>
    </main>
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
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="document.querySelector('form').submit();">取り込みします</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
