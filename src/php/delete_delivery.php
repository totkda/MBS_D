<?php
require_once '../db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    try {
        // 1. 納品書詳細を先に削除
        $stmt1 = $pdo->prepare('DELETE FROM delivery_details WHERE delivery_id = ?');
        $stmt1->execute([$id]);
        // 2. 納品書本体を削除
        $stmt2 = $pdo->prepare('DELETE FROM deliveries WHERE delivery_id = ?');
        $stmt2->execute([$id]);
        header('Location: ../納品管理.php');
        exit;
    } catch (PDOException $e) {
        echo '削除に失敗しました: ' . $e->getMessage();
    }
} else {
    echo '不正なIDです';
}
