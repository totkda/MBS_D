<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文登録</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="date"], input[type="text"], input[type="number"], textarea {
            width: calc(100% - 10px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        .item-actions { text-align: right; margin-bottom: 10px; }
        .item-actions input {
            padding: 5px 10px;
            margin-left: 5px;
            cursor: pointer;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-success:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>注文登録</h1>

        <div class="form-group">
            <label for="order-date">注文日:</label>
            <input type="date" id="order-date" name="orderDate" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
            <label for="customer-id">顧客ID:</label>
            <input type="number" id="customer-id" name="customerId" placeholder="例: 101" required>
        </div>

        <h2>注文明細</h2>
        <div class="item-actions">
            <input type="button" value="+">
            <input type="button" value="-">
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>商品ID</th> <th>数量</th>
                    <th>単価</th> <th>メモ</th> </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><input type="number" name="productId[]" placeholder="商品ID" required></td>
                    <td><input type="number" name="quantity[]" value="1" min="1" required></td>
                    <td><input type="number" name="unitPrice[]" step="0.01" placeholder="0.00" required></td>
                    <td><input type="text" name="itemNote[]" placeholder="特記事項"></td>
                </tr>
            </tbody>
        </table>

        <div class="form-group">
            <label for="order-note">備考:</label> <textarea id="order-note" name="note" rows="4" placeholder="注文全体に関する備考"></textarea>
        </div>

        <button type="button" class="btn-success">登録</button>
    </div>

    <script src="./order_register.js"></script>
</body>
</html>