// desktop\code\delivery_register.js

document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('delivery-detail-table').getElementsByTagName('tbody')[0];
    const addBtn = document.getElementById('add-row');
    const removeBtn = document.getElementById('remove-row');
    const registerBtn = document.getElementById('register-btn');

    // 行追加
    addBtn.addEventListener('click', function () {
        const rowCount = table.rows.length;
        const lastRow = table.rows[rowCount - 1];
        const newRow = lastRow.cloneNode(true);

        // 行番号振り直し
        newRow.cells[0].textContent = rowCount + 1;
        // inputを空にする
        Array.from(newRow.querySelectorAll('input')).forEach(input => input.value = '');
        table.appendChild(newRow);
    });

    // 行削除（最低1行は残す）
    removeBtn.addEventListener('click', function () {
        if (table.rows.length > 1) {
            table.deleteRow(table.rows.length - 1);
        }
    });

    // 登録ボタン
    registerBtn.addEventListener('click', function () {
        if (confirm('この内容で登録しますか？')) {
            alert('登録しました！（ダミー）');
            // 実際の登録処理はここに記述
        }
    });
});
