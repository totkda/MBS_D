<?php
header('Content-Type: application/json');

$pdo = new PDO('mysql:host=localhost;dbname=mbs;charset=utf8mb4', 'root', '');
$query = $_GET['keyword'] ?? '';

if (strlen($query) > 0) {
    $stmt = $pdo->prepare("SELECT DISTINCT customer_name FROM customers WHERE customer_name LIKE :query LIMIT 10");
    $stmt->execute([':query' => $query . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($results);
} else {
    echo json_encode([]);
}
