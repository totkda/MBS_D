<?php
// navbar.php
// サイト共通ナビゲーションバー
// 必要な場合はパスやファイル名を調整してください。
?>
<nav class="main-nav">
    <ul>
        <li><a href="/src/index.php">ホーム</a></li>
        <li><a href="/src/order/order_management.php">注文管理</a></li>
        <li><a href="/src/delivery/delivery_management.php">納品管理</a></li>
        <li><a href="/src/customer/customer_register.php">顧客登録</a></li>
    </ul>
</nav>
<style>
    /* ナビゲーションバーのスタイル（共通） */
    nav ul {
        list-style-type: none;
    }

    nav ul li {
        display: inline;
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
        color: #fff;
        border-color: #0069d9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>