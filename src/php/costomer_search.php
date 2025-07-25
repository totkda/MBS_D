<?php
require_once(__DIR__ . '/db_connect.php');

header('Content-Type: application/json');

$keyword = $_GET['keyword'] ?? '';

if (trim($keyword) === '') {
    echo json_encode([]);
    exit;
}

$sql = "SELECT customer_name FROM customers WHERE customer_name LIKE :keyword LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([':keyword' => '%' . $keyword . '%']);

$results = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($results);
?>
