// desktop\code\delivery_register.js

document.addEventListener('DOMContentLoaded', function () {
    // モーダルオープン
    const openModalBtn = document.getElementById('openModal');
    if (openModalBtn) {
        openModalBtn.addEventListener('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('delivery-register-form'));
            modal.show();
        });
    }

    // 登録二重確認モーダル
    const insertBtn = document.getElementById('delivery-insert-button');
    if (insertBtn) {
        insertBtn.addEventListener('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('delivery-insert'));
            modal.show();
        });
    }
});

// 登録ボタンの処理
$(function () {
    // 「登録する」ボタン（モーダル内）クリック時
    $('#delivery-insert .btn-success').off('click').on('click', function (e) {
        e.preventDefault();
        // 入力値取得
        const customerName = $('#customer-name').val();
        const deliveryDate = $('input[type="date"]').first().val();
        // 品目データ取得
        const items = [];
        // 納品書テーブルのtbodyから取得
        $('.table.table-bordered tbody tr').each(function () {
            const tds = $(this).find('td');
            const itemName = tds.eq(1).text().trim();
            const quantity = tds.eq(2).text().trim();
            const price = tds.eq(3).text().replace('¥', '').trim();
            if (itemName) {
                items.push({
                    item_name: itemName,
                    quantity: Number(quantity) || 0,
                    price: Number(price) || 0
                });
            }
        });
        // Ajax送信
        $.ajax({
            url: './delivery_register_api.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                customer_name: customerName,
                delivery_date: deliveryDate,
                items: items
            }),
            dataType: 'json',
            success: function (res) {
                if (res.result === 'ok') {
                    alert('登録が完了しました');
                    window.location.href = './納品管理.html';
                } else {
                    alert('登録失敗: ' + (res.message || ''));
                }
            },
            error: function (xhr) {
                alert('通信エラー: ' + xhr.status);
            }
        });
    });
});
