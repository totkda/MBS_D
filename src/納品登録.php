<?php
// 納品登録画面（HTML→PHP化）
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>納品登録</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
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

        .main-nav a:hover {
            background-color: #007bff;
            color: #ffffff;
            border-color: #0069d9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        td input,
        textarea {
            width: 100%;
        }
    </style>
</head>

<body>

    <!-- ナビゲーションバー -->
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

        <!-- 納品書フォーム -->
        <form>
            <div>
                <div class="d-flex justify-content-between">
                    <span>納品書</span>
                    <input type="date" id="delivery-date" readonly>
                    <span>
                        <label for="customer-delivery-no">No.</label>
                        <input type="text" id="customer-delivery-no" size="4" readonly>
                    </span>
                </div>
                <div>
                    <input type="text" id="customer-name">
                    <label for="customer-name">様</label>
                </div>
                <div>
                    下記のとおり納品いたしました
                </div>
            </div>
            <!-- 下部 -->
            <div>
                <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle mb-0">
                    <colgroup>
                        <col style="width: 2%;">
                        <col style="width: 42%;">
                        <col style="width: 11%;">
                        <col style="width: 11%;">
                        <col style="width: 33%;">
                    </colgroup>
                    <thead class="table-dark table-bordered border-light sticky-top">
                        <tr>
                            <th colspan="2">品名</th>
                            <th>数量</th>
                            <th>単価</th>
                            <th>
                                <span>金額(</span>
                                <input type="radio" name="price" id="price-excluded" checked>
                                <label for="price-excluded">税抜</label>
                                <input type="radio" name="price" id="price-included">
                                <label for="price-included">税込)</label>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="delivery-details-tbody">
                        <!-- JSで明細を埋め込む -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">合計</th>
                            <td id="delivery-total-qty"></td>
                            <td></td>
                            <td id="delivery-total-amount"></td>
                        </tr>
                    </tfoot>
                </table>
                <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle mt-0">
                    <colgroup>
                        <col style="width: 12%;">
                        <col style="width: 12%;">
                        <col style="width: 12%;">
                        <col style="width: 19%;">
                        <col style="width: 11%;">
                        <col style="width: 33%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td>税率</td>
                            <td>10%</td>
                            <td>消費税率等</td>
                            <td></td>
                            <td>税込合計金額</td>
                            <td id="delivery-total-amount-incl"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <div class="mb-3 text-end">
                    <button type="button" id="openModal" class="btn btn-primary" onclick="showForm()">項目追加</button>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" id="delivery-cansel-button" class="btn btn-danger">戻る</button>
                    <button type="button" id="delivery-insert-button" class="btn btn-success">登録</button>
                </div>
            </div>
        </form>

        <!-- 納品登録フォーム -->
        <?php
        require_once __DIR__ . '/db_connect.php';
        $orders = $pdo->query('SELECT * FROM orders ORDER BY order_id')->fetchAll(PDO::FETCH_ASSOC);
        $orderDetails = [];
        $productNames = [];
        $customerNames = [];
        // 商品名取得
        $stmtProd = $pdo->query('SELECT product_id, product_name FROM products');
        foreach ($stmtProd as $row) {
            $productNames[$row['product_id']] = $row['product_name'];
        }
        // 顧客名取得
        $stmtCust = $pdo->query('SELECT customer_id, customer_name FROM customers');
        foreach ($stmtCust as $row) {
            $customerNames[$row['customer_id']] = $row['customer_name'];
        }
        foreach ($orders as $order) {
            $stmt = $pdo->prepare('SELECT * FROM order_details WHERE order_id = ? ORDER BY product_id');
            $stmt->execute([$order['order_id']]);
            $orderDetails[$order['order_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        ?>
        <div class="modal fade" id="delivery-register-form" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">納品登録フォーム</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body d-flex">
                        <!-- 検索フォーム（ダミー） -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">注文書検索</h5>
                                <div class="mt-4">
                                    <div class="mb-3">
                                        <div>注文日</div>
                                        <input type="date" id="order-date-since" class="form-control" style="display: inline;">
                                        <label for="order-date-since" class="form-label">から</label><br>
                                        <input type="date" id="order-date-until" class="form-control" style="display: inline;">
                                        <label for="order-date-until" class="form-label">まで</label><br>
                                    </div>
                                    <input type="hidden" name="page" value="1">
                                    <input type="button" value="検索" class="btn btn-primary w-100">
                                </div>
                            </div>
                        </div>
                        <!-- 注文書情報表示部 -->
                        <div style="flex:1; margin-left:20px;">
                            <div>
                                <div class="d-flex justify-content-between">
                                    <span>注文書</span>
                                    <input type="date" id="modal-order-date" readonly>
                                    <span>
                                        <label for="customer-order-no">No.</label>
                                        <input type="text" id="customer-order-no" size="4" readonly>
                                    </span>
                                </div>
                                <div>
                                    <input type="text" id="modal-customer-name" readonly>
                                    <label for="modal-customer-name">様</label>
                                </div>
                                <div>
                                    下記のとおり御注文申し上げます
                                </div>
                            </div>
                            <div style="height: 180px; overflow-y: auto;">
                                <table class="table table-bordered border-dark table-striped table-hover table-sm align-middle">
                                    <colgroup>
                                        <col style="width: 2%;">
                                        <col style="width: 30%;">
                                        <col style="width: 3%;">
                                        <col style="width: 1%;">
                                        <col style="width: 3%;">
                                        <col style="width: 8%;">
                                        <col style="width: 24%;">
                                        <col style="width: 24%;">
                                    </colgroup>
                                    <thead class="table-dark table-bordered border-light sticky-top">
                                        <tr>
                                            <th colspan="2">品名</th>
                                            <th colspan="3">数量</th>
                                            <th>単価</th>
                                            <th>摘要</th>
                                            <th>備考</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modal-order-details-tbody">
                                        <!-- JSで明細を埋め込む -->
                                    </tbody>
                                </table>
                                <thead class="table-dark table-bordered border-light sticky-top">
                                    <tr>
                                        <th>№</th>
                                        <th>品名</th>
                                        <th>数量</th>
                                        <th>/</th>
                                        <th>数量</th>
                                        <th>単価</th>
                                        <th>備考</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="modal-order-details-tbody">
                                </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-4">
                            <!-- ページング -->
                            <div class="mb-3">
                                <div class="text-center">
                                    <input type="button" id="order-prev" value="←">
                                    <span id="order-page-info"></span>
                                    <input type="button" id="order-next" value="→">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">登録確認</button>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <script>
            // PHPから注文書データをJSに渡す
            const allOrders = <?php echo json_encode($orders, JSON_UNESCAPED_UNICODE); ?>;
            const allOrderDetails = <?php echo json_encode($orderDetails, JSON_UNESCAPED_UNICODE); ?>;
            let orders = allOrders;
            let orderDetails = allOrderDetails;
            let orderPage = 0;
            // 顧客名→顧客ID逆引き
            const customerNames = window.customerNames || {};
            const customerIds = {};
            Object.entries(customerNames).forEach(([id, name]) => {
                customerIds[name] = id;
            });

            function renderOrderModal(page) {
                const productNames = window.productNames || {};
                const customerNames = window.customerNames || {};
                if (orders.length === 0) {
                    document.getElementById('modal-order-date').value = '';
                    document.getElementById('customer-order-no').value = '';
                    document.getElementById('modal-customer-name').value = '';
                    document.getElementById('modal-order-details-tbody').innerHTML = '<tr><td colspan="8" class="text-center">注文書がありません</td></tr>';
                    document.getElementById('order-page-info').textContent = '';
                    return;
                }
                const order = orders[page];
                // 必ずallOrderDetailsから取得
                const details = (window.allOrderDetails && window.allOrderDetails[order.order_id]) ? window.allOrderDetails[order.order_id] : [];
                document.getElementById('modal-order-date').value = order.order_date;
                document.getElementById('customer-order-no').value = order.order_id;
                document.getElementById('modal-customer-name').value = customerNames[order.customer_id] || '';
                let tbody = '';
                for (let i = 0; i < 5; i++) {
                    const d = details[i];
                    tbody += '<tr>';
                    tbody += `<td>${i + 1}</td>`;
                    tbody += `<td>${d ? (productNames[d.product_id] || '') : ''}</td>`;
                    tbody += `<td><input type="text" data-idx="${i}" value="${d ? d.quantity : ''}"></td>`;
                    tbody += '<td>/</td>';
                    tbody += `<td>${d ? d.quantity : ''}</td>`;
                    tbody += `<td>&yen;<input type="text" style="width:80%;" value="${d ? d.unit_price : ''}" readonly></td>`;
                    tbody += `<td>${d ? d.note : ''}</td>`;
                    tbody += i === 0 ? `<td rowspan="5"></td>` : '';
                    tbody += '</tr>';
                }
                document.getElementById('modal-order-details-tbody').setAttribute('id', `modal-order-details-tbody-order${order.order_id}`);
                document.getElementById(`modal-order-details-tbody-order${order.order_id}`).innerHTML = tbody;
            }
            // 直前の登録顧客名を保存
            let lastRegisteredCustomerName = '';
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('delivery-register-form');
                modal.addEventListener('show.bs.modal', function() {
                    // 顧客名でフィルタ
                    const cname = document.getElementById('customer-name').value.trim();
                    let filtered = allOrders;
                    if (cname && customerIds[cname]) {
                        filtered = allOrders.filter(o => o.customer_id == customerIds[cname]);
                    } else if (lastRegisteredCustomerName && customerIds[lastRegisteredCustomerName]) {
                        // 直前登録顧客名があればそれでフィルタ
                        filtered = allOrders.filter(o => o.customer_id == customerIds[lastRegisteredCustomerName]);
                        document.getElementById('customer-name').value = lastRegisteredCustomerName;
                    }
                    orders = filtered;
                    orderDetails = allOrderDetails;
                    orderPage = 0;
                    renderOrderModal(orderPage);
                });
                document.getElementById('order-prev').onclick = function() {
                    if (orderPage > 0) {
                        orderPage--;
                        renderOrderModal(orderPage);
                    }
                };
                document.getElementById('order-next').onclick = function() {
                    if (orderPage < orders.length - 1) {
                        orderPage++;
                        renderOrderModal(orderPage);
                    }
                };
            });
        </script>
    </main>

    <!-- モーダル・JSのみ残す -->
    <div class="modal fade" id="delivery-insert" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">納品書を登録します</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に登録しますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./納品管理.html"><button type="button" class="btn btn-success"
                                onclick="hideForm()">登録する</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="delivery-cansel" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">納品書の作成を中断しますか？</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div>本当に中断して戻りますか？</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    <div class="text-end">
                        <a href="./納品管理.html"><button type="button" class="btn btn-danger"
                                onclick="hideForm()">戻る</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="./js/delivery_register.js"></script>
    <script>
        // --- 納品書フォームへの反映 ---
        function updateDeliveryForm() {
            // 選択明細を取得
            const rows = deliveryConfirmRows || [];
            // 顧客名・納品日・No.（仮:最初の顧客/日付/No.）
            document.getElementById('delivery-date').value = new Date().toISOString().slice(0, 10);
            document.getElementById('customer-delivery-no').value = rows.length ? rows[0].order_id : '';
            // 顧客名
            const customerNames = window.customerNames || {};
            document.getElementById('customer-name').value = rows.length ? (customerNames[rows[0].customer_id] || rows[0].customer_id) : '';
            // 明細テーブル
            let tbody = '';
            let totalQty = 0;
            let totalAmount = 0;
            let totalAmountIncl = 0;
            const taxRate = 0.1;
            const productNames = window.productNames || {};
            for (let i = 0; i < 5; i++) {
                const d = rows[i];
                tbody += '<tr>';
                tbody += `<td>${i + 1}</td>`;
                tbody += `<td>${d ? (productNames[d.product_id] || '') : ''}</td>`;
                tbody += `<td>${d ? d.quantity : ''}</td>`;
                tbody += `<td>${d ? ('&yen;' + Number(d.unit_price).toLocaleString()) : ''}</td>`;
                let amount = d ? d.quantity * d.unit_price : 0;
                tbody += `<td class=\"delivery-amount\">${d ? ('&yen;' + amount.toLocaleString()) : ''}</td>`;
                tbody += '</tr>';
                if (d) {
                    totalQty += Number(d.quantity);
                    totalAmount += amount;
                }
            }
            totalAmountIncl = Math.round(totalAmount * (1 + taxRate));
            document.getElementById('delivery-details-tbody').innerHTML = tbody;
            document.getElementById('delivery-total-qty').textContent = totalQty;
            document.getElementById('delivery-total-amount').textContent = totalAmount ? '¥' + totalAmount.toLocaleString() : '';
            document.getElementById('delivery-total-amount-incl').textContent = totalAmountIncl ? '¥' + totalAmountIncl.toLocaleString() : '';
        }
        // 税抜/税込切替
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('price-excluded').addEventListener('change', updateDeliveryForm);
            document.getElementById('price-included').addEventListener('change', updateDeliveryForm);
        });
        // 登録確認時に納品書フォームへ反映
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-success[data-bs-dismiss="modal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    setTimeout(updateDeliveryForm, 200);
                });
            });
        });
    </script>
    <script>
        // --- 納品登録ロジック ---
        let deliverySelectedRows = [];
        // ①数量変更時に選択内容を保持
        function updateSelectedRows() {
            deliverySelectedRows = [];
            for (let orderIdx = 0; orderIdx < orders.length; orderIdx++) {
                const details = orderDetails[orders[orderIdx].order_id] || [];
                for (let i = 0; i < 5; i++) {
                    const d = details[i];
                    if (!d) continue;
                    // 各明細のinput要素を特定
                    const input = document.querySelector(`#modal-order-details-tbody-order${orders[orderIdx].order_id} input[data-idx='${i}']`);
                    if (input && Number(input.value) > 0) {
                        deliverySelectedRows.push({
                            order_id: orders[orderIdx].order_id,
                            product_id: d.product_id,
                            quantity: Number(input.value),
                            unit_price: d.unit_price,
                            customer_id: orders[orderIdx].customer_id
                        });
                    }
                }
            }
        }

        // ②明細inputにイベント付与
        function attachInputEvents() {
            document.querySelectorAll("#modal-order-details-tbody input[type='text']").forEach(input => {
                input.addEventListener('input', updateSelectedRows);
            });
        }

        // ③登録確認ボタン押下時に選択内容を確認
        let deliveryConfirmRows = [];
        document.addEventListener('DOMContentLoaded', function() {
            // ...既存...
            // モーダル初期表示
            const modal = document.getElementById('delivery-register-form');
            modal.addEventListener('show.bs.modal', function() {
                orderPage = 0;
                renderOrderModal(orderPage);
                setTimeout(attachInputEvents, 100); // inputイベント付与
            });
            document.getElementById('order-prev').onclick = function() {
                if (orderPage > 0) {
                    orderPage--;
                    renderOrderModal(orderPage);
                    setTimeout(attachInputEvents, 100);
                }
            };
            document.getElementById('order-next').onclick = function() {
                if (orderPage < orders.length - 1) {
                    orderPage++;
                    renderOrderModal(orderPage);
                    setTimeout(attachInputEvents, 100);
                }
            };
            // 登録確認ボタン
            document.querySelectorAll('.btn-success[data-bs-dismiss="modal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    updateSelectedRows();
                    deliveryConfirmRows = [...deliverySelectedRows];
                    // 必要なら確認ダイアログ等
                });
            });
            // 登録ボタン
            document.getElementById('delivery-insert-button').onclick = function() {
                if (!deliveryConfirmRows.length) {
                    alert('納品書に登録する明細がありません');
                    return;
                }
                // 顧客名を保存
                lastRegisteredCustomerName = document.getElementById('customer-name').value.trim();
                // サーバーへPOST
                fetch('納品登録.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            deliveries: deliveryConfirmRows
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('納品書を登録しました');
                            location.href = '納品管理.php';
                        } else {
                            alert('登録失敗: ' + (data.message || ''));
                        }
                    })
                    .catch(e => alert('通信エラー: ' + e));
            };
        });

        // 明細テーブルのtbodyにorder_idを付与
        function renderOrderModal(page) {
            // window.productNamesを必ず参照
            const productNames = window.productNames ? window.productNames : {};
            const customerNames = window.customerNames ? window.customerNames : {};
            console.log('productNames:', productNames); // デバッグ用
            if (orders.length === 0) {
                document.getElementById('modal-order-date').value = '';
                document.getElementById('customer-order-no').value = '';
                document.getElementById('modal-customer-name').value = '';
                document.getElementById('modal-order-details-tbody').innerHTML = '<tr><td colspan="8" class="text-center">注文書がありません</td></tr>';
                if (document.getElementById('order-page-info')) document.getElementById('order-page-info').textContent = '';
                return;
            }
            const order = orders[page];
            // window.allOrderDetailsを必ず参照
            if (!window.allOrderDetails) window.allOrderDetails = {};
            const details = window.allOrderDetails[order.order_id] || [];
            document.getElementById('modal-order-date').value = order.order_date;
            document.getElementById('customer-order-no').value = order.order_id;
            document.getElementById('modal-customer-name').value = customerNames[order.customer_id] || '';
            let tbody = '';
            for (let i = 0; i < 5; i++) {
                const d = details[i];
                tbody += '<tr>';
                // 品名ガード強化
                let pname = '';
                if (d && d.product_id !== undefined && d.product_id !== null && productNames[d.product_id]) {
                    pname = productNames[d.product_id];
                } else if (d && d.product_id !== undefined && d.product_id !== null) {
                    pname = d.product_id;
                }
                tbody += `<td>${i + 1}</td>`;
                tbody += `<td>${pname}</td>`;
                tbody += `<td><input type="text" data-idx="${i}" value="${d ? d.quantity : ''}"></td>`;
                tbody += '<td>/</td>';
                tbody += `<td>${d ? d.quantity : ''}</td>`;
                tbody += `<td>&yen;<input type="text" style="width:80%;" value="${d ? d.unit_price : ''}" readonly></td>`;
                tbody += `<td>${d ? d.note : ''}</td>`;
                tbody += i === 0 ? `<td rowspan="5"></td>` : '';
                tbody += '</tr>';
            }
            // tbodyのIDは固定
            document.getElementById('modal-order-details-tbody').innerHTML = tbody;
        }
        // showForm関数を追加
        function showForm() {
            const modal = new bootstrap.Modal(document.getElementById('delivery-register-form'));
            modal.show();
        }
        // 商品名・顧客名をJSグローバルに渡す
        window.productNames = <?php echo json_encode($productNames, JSON_UNESCAPED_UNICODE); ?>;
        window.customerNames = <?php echo json_encode($customerNames, JSON_UNESCAPED_UNICODE); ?>;
    </script>

    <?php
    // --- サーバー側納品登録処理 ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['deliveries']) || !is_array($input['deliveries']) || !count($input['deliveries'])) {
            echo json_encode(['success' => false, 'message' => '明細がありません']);
            exit;
        }
        require_once __DIR__ . '/db_connect.php';
        $pdo->beginTransaction();
        try {
            // 新しいdelivery_idを採番
            $stmt = $pdo->query('SELECT MAX(delivery_id) FROM deliveries');
            $maxId = $stmt->fetchColumn();
            $delivery_id = $maxId ? $maxId + 1 : 2001;
            $delivery_date = date('Y-m-d');
            $delivery_status_name = '納品済';
            $customer_ids = array_unique(array_column($input['deliveries'], 'customer_id'));
            foreach ($customer_ids as $customer_id) {
                // 納品書（deliveries）
                $pdo->prepare('INSERT INTO deliveries (delivery_id, delivery_date, customer_id, delivery_status_name) VALUES (?, ?, ?, ?)')
                    ->execute([$delivery_id, $delivery_date, $customer_id, $delivery_status_name]);
                $line_number = 1;
                foreach ($input['deliveries'] as $row) {
                    if ($row['customer_id'] != $customer_id) continue;
                    // 納品書明細
                    $pdo->prepare('INSERT INTO delivery_details (delivery_id, product_id, quantity) VALUES (?, ?, ?)')
                        ->execute([$delivery_id, $row['product_id'], $row['quantity']]);
                    // 注文納品対応表
                    $pdo->prepare('INSERT INTO order_delivery_map (order_id, delivery_id, line_number) VALUES (?, ?, ?)')
                        ->execute([$row['order_id'], $delivery_id, $line_number]);
                    $line_number++;
                }
                $delivery_id++;
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    ?>
</body>

</html>