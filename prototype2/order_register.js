// desktop\code\order_register.js

document.addEventListener('DOMContentLoaded', function () {
    // 行追加/削除
    const addBtn = document.querySelector('input[value="+"]');
    const removeBtn = document.querySelector('input[value="-"]');
    const tbody = document.querySelector('table tbody');

    addBtn?.addEventListener('click', function () {
        if (tbody) {
            const lastRow = tbody.rows[tbody.rows.length - 1];
            const newRow = lastRow.cloneNode(true);
            // クリア
            newRow.querySelectorAll('input, textarea').forEach(input => input.value = '');
            tbody.appendChild(newRow);
        }
    });

    removeBtn?.addEventListener('click', function () {
        if (tbody && tbody.rows.length > 1) {
            tbody.deleteRow(tbody.rows.length - 1);
        }
    });

    // 登録ボタン
    document.querySelector('.btn-success')?.addEventListener('click', function () {
        alert('登録処理（ダミー）');
    });
});
