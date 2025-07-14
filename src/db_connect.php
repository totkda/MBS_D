<?php
// データベース接続情報
$username = 'root';
$password = '';
$dsn = 'mysql:host=localhost;dbname=mbs;charset=utf8mb4';

try {
    // PDOインスタンスの作成
    $pdo = new PDO($dsn, $username, $password);

    // エラーモードを例外に設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // 接続失敗時のエラーメッセージ
    die('データベース接続失敗: ' . $e->getMessage());
}
