<?php
// api/search_orders.php
header('Content-Type: application/json');

$pdo = new PDO('mysql:host=localhost;dbname=mbs;charset=utf8mb4', 'root', '');

$where = [];
$params = [];

if (!empty($_GET['since'])) {
    $where[] = 'o.order_date >= :since';
    $params[':since'] = $_GET['since'];
}
if (!empty($_GET['until'])) {
    $where[] = 'o.order_date <= :until';
    $params[':until'] = $_GET['until'];
}

$whereSQL = '';
if ($where) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

$sql = "
    SELECT 
        p.product_name,
        od.quantity,
        p.unit,
        od.unit_price,
        od.note,
        o.remark
    FROM orders o
    JOIN order_details od ON o.id = od.order_id
    JOIN products p ON od.product_id = p.id
    $whereSQL
    ORDER BY o.order_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results, JSON_UNESCAPED_UNICODE);
