<?php
// 統計検索.php（検索処理を共通化）

// 1. パラメータ取得
$customer_name = $_GET['customer_name'] ?? '';
$branch_name = $_GET['branch_name'] ?? '';
$sort = $_GET['sort'] ?? 'total_sales';
$order = $_GET['order'] ?? 'desc';

// 2. 並び替えカラムの安全なマッピング
$allowedSortColumns = [
    'total_sales' => 'total_sales',
    'avg_lead_time' => 'avg_lead_time',
    'customer_name' => 'c.customer_name'
];

$sortColumn = $allowedSortColumns[$sort] ?? 'total_sales';

// 3. 昇順・降順のチェック
$order = strtolower($_GET['order'] ?? 'desc');
$order = $order === 'asc' ? 'ASC' : 'DESC';  


// 4. SQL定義（$sortColumnと$orderを使う）
$sql = "SELECT 
    b.branch_name, 
    c.customer_id, 
    c.customer_name, 
    COALESCE(SUM(od.quantity * p.price), 0) AS total_sales, 
    COALESCE(AVG(DATEDIFF(d.delivery_date, o.order_date)), 0) AS avg_lead_time
FROM customers c
LEFT JOIN branches b ON c.branch_id = b.branch_id
LEFT JOIN orders o ON c.customer_id = o.customer_id
LEFT JOIN order_details od ON o.order_id = od.order_id
LEFT JOIN products p ON od.product_id = p.product_id
LEFT JOIN order_delivery_map odm ON o.order_id = odm.order_id
LEFT JOIN deliveries d ON odm.delivery_id = d.delivery_id
WHERE (:customer_name = '' OR c.customer_name LIKE :customer_name_like)
AND (:branch_name = '' OR b.branch_name LIKE :branch_name_like)
GROUP BY b.branch_name, c.customer_id, c.customer_name
ORDER BY $sortColumn $order
LIMIT 20";

// 5. SQL実行
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':customer_name', $customer_name);
$stmt->bindValue(':customer_name_like', $customer_name === '' ? '%' : "%$customer_name%", PDO::PARAM_STR);
$stmt->bindValue(':branch_name', $branch_name);
$stmt->bindValue(':branch_name_like', $branch_name === '' ? '%' : "%$branch_name%", PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll();
