// desktop\code\order_register.js

document.addEventListener('DOMContentLoaded', function () {
    // 行追加/削除ボタンとテーブル本体の要素を取得
    const addBtn = document.querySelector('input[value="+"]');
    const removeBtn = document.querySelector('input[value="-"]');
    const tbody = document.querySelector('table tbody');

    // --- 行追加機能 ---
    addBtn?.addEventListener('click', function () {
        if (tbody) {
            // 現在の最終行をクローンする前に、行がない場合の初期行を考慮
            const lastRow = tbody.rows.length > 0 ? tbody.rows[tbody.rows.length - 1] : null;
            let newRow;

            if (lastRow) {
                // 既存の最終行をクローン
                newRow = lastRow.cloneNode(true);
            } else {
                // テーブルが空の場合、新しい<tr>要素をゼロから作成
                newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td></td>
                    <td><input type="number" name="productId[]" placeholder="商品ID" required></td>
                    <td><input type="number" name="quantity[]" value="1" min="1" required></td>
                    <td><input type="number" name="unitPrice[]" step="0.01" placeholder="0.00" required></td>
                    <td><input type="text" name="itemNote[]" placeholder="商品に関するメモ"></td>
                `;
            }

            // 新しい行の行番号を更新
            const newRowIndex = tbody.rows.length + 1; // 現在の行数 + 1 が新しい行番号
            newRow.querySelector('td:first-child').textContent = newRowIndex; // 最初のtdのテキストを更新

            // 新しい行の入力フィールドの値をクリア
            newRow.querySelectorAll('input, textarea').forEach(input => {
                // input のタイプに応じてデフォルト値を設定
                if (input.type === 'number') {
                    input.value = ''; // 数値入力はクリア
                } else if (input.type === 'text') {
                    input.value = ''; // テキスト入力はクリア
                }
                // 例えば、quantityはデフォルトで1にしたいなら '1' を設定
                if (input.name.includes('quantity')) {
                    input.value = '1';
                }
            });

            // 新しい行をテーブルに追加
            tbody.appendChild(newRow);
        }
    });

    // --- 行削除機能 ---
    removeBtn?.addEventListener('click', function () {
<<<<<<< HEAD
        // テーブルに行が1つしかない場合は削除しない (最低1行は必要)
        if (tbody && tbody.rows.length > 1) {
            const lastRow = tbody.rows[tbody.rows.length - 1];
            const inputsInLastRow = lastRow.querySelectorAll('input');

            let hasData = false;
            // 最終行の入力フィールドにデータがあるかチェック
            for (const input of inputsInLastRow) {
                if (input.value.trim() !== '' && input.value.trim() !== '1') { // quantityのデフォルト値'1'はデータと見なさない
                    hasData = true;
                    break;
                }
            }

            if (hasData) {
                // データがある場合は警告を表示して何もしない
                alert('入力されたデータがあるため、この行は削除できません。');
            } else {
                // データがない場合は行を削除
                tbody.deleteRow(tbody.rows.length - 1);
            }
        } else if (tbody && tbody.rows.length === 1) {
            // 最後の1行を削除しようとした場合
            alert('これ以上行を削除することはできません。');
=======
        if (tbody && tbody.rows.length > 5) { // 最低5行は残す
            tbody.deleteRow(tbody.rows.length - 1);
>>>>>>> a7889aec7928ec0ba2d1f2b320d86da38fe57414
        }
    });

    // --- 登録ボタン機能 ---
    document.querySelector('.btn-success')?.addEventListener('click', function () {
        // 入力値の取得
        const orderDate = document.querySelector('input[type="date"]').value;
        const customerId = document.getElementById('customer-id').value; 

        // 注文明細のデータ収集
        const rows = Array.from(document.querySelectorAll('table tbody tr'));
        const items = rows.map(row => {
            const tds = row.querySelectorAll('td');
            // 各セルのinput要素から値を取得
            // HTMLの列順序に合わせてインデックスを使用
            const productIdInput = tds[1]?.querySelector('input[name^="productId"]');
            const quantityInput = tds[2]?.querySelector('input[name^="quantity"]');
            const unitPriceInput = tds[3]?.querySelector('input[name^="unitPrice"]');
            const itemNoteInput = tds[4]?.querySelector('input[name^="itemNote"]');

            return {
                productId: productIdInput ? productIdInput.value : '',
                quantity: quantityInput ? quantityInput.value : '',
                unitPrice: unitPriceInput ? unitPriceInput.value : '',
                note: itemNoteInput ? itemNoteInput.value : ''
            };
        }).filter(item => item.productId !== '' || item.quantity !== '' || item.unitPrice !== '' || item.note !== ''); // 全ての項目が空の行は除外

        // 注文全体の備考欄
        const note = document.getElementById('order-note')?.value || '';

        // 簡単な入力チェック (PHP側でも行うべきですが、クライアント側でも補助的に)
        if (!orderDate) {
            alert('注文日を入力してください。');
            return;
        }
        if (!customerId) {
            alert('顧客IDを入力してください。');
            return;
        }
        if (items.length === 0) {
            alert('少なくとも1つの注文明細を入力してください。');
            return;
        }

        // データ送信 (PHPスクリプトへのfetchリクエスト)
        fetch('order_register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                orderDate, 
                customerId, 
                items, 
                note 
            })
        })
        .then(res => {
            // サーバーからのレスポンスがJSON形式であることを確認
            if (!res.ok) {
                // HTTPエラーの場合 (例: 500 Internal Server Error)
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            const contentType = res.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return res.json();
            } else {
                // JSONでないレスポンスの場合（例: PHPのエラーメッセージがHTMLで返される場合）
                console.error('Expected JSON, but received:', res.text());
                throw new Error('サーバーからの応答が不正です。');
            }
        })
        .then(data => {
            if (data.success) {
                alert('登録が完了しました');
                // 登録成功後、フォームをクリアするか、ページをリロードする
                location.reload(); 
            } else {
                // サーバーからのエラーメッセージを表示
                alert('登録に失敗しました: ' + (data.message || '不明なエラー'));
            }
        })
        .catch(err => {
            // 通信エラーやJSONパースエラーなどのクライアント側エラー
            console.error('Fetch error:', err);
            alert('通信エラーまたはデータの処理中にエラーが発生しました: ' + err.message);
        });
    });
});