// ページのDOMがすべて読み込まれた後に実行
window.addEventListener('DOMContentLoaded', () => {
    // 検索ボタンを取得
    const searchButton = document.querySelector('input.btn.btn-primary.w-100');

    // 検索ボタンがクリックされたときの処理
    searchButton.addEventListener('click', () => {
        // 検索条件を取得
        const customerName = document.getElementById('customer_name').value.trim(); // 顧客名
        const branchName = document.getElementById('branch_name').value.trim();     // 支店名
        const sortKey = document.getElementById('sort_select').value;               // 並び替えキー
        const sortOrder = document.querySelector('select[name="order"]').value;   // 昇順・降順

        // テーブルのすべての行を取得
        const rows = Array.from(document.querySelectorAll('tbody tr'));

        // 行をフィルタ（検索条件に合致するものだけ残す）
        let filteredRows = rows.filter(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 5) return false; // 必要なセル数がなければ除外

            const rowBranch = cells[0].textContent.trim();   // 支店名
            const rowCustomer = cells[2].textContent.trim(); // 顧客名

            // 支店名でフィルタ
            if (branchName && !rowBranch.includes(branchName)) return false;
            // 顧客名でフィルタ
            if (customerName && !rowCustomer.includes(customerName)) return false;

            return true;
        });

        // 並び替え用の列インデックスを決定
        const columnIndex = {
            'total_sales': 3,      // 合計売上
            'avg_lead_time': 4,   // 平均リードタイム
            'customer_name': 2    // 顧客名
        }[sortKey];

        // 並び替え処理
        filteredRows.sort((a, b) => {
            const valA = a.children[columnIndex].textContent.trim();
            const valB = b.children[columnIndex].textContent.trim();

            // 数値と文字列の両方に対応
            const numA = parseFloat(valA.replace(/,/g, ''));
            const numB = parseFloat(valB.replace(/,/g, ''));

            const isNumeric = !isNaN(numA) && !isNaN(numB);

            if (isNumeric) {
                // 数値の場合
                return sortOrder === 'asc' ? numA - numB : numB - numA;
            } else {
                // 文字列の場合
                return sortOrder === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            }
        });

        // テーブルのtbodyを更新（既存の行を削除し、フィルタ・ソート済みの行を追加）
        const tbody = document.querySelector('tbody');
        tbody.innerHTML = ''; // 既存の行を削除

        filteredRows.forEach(row => {
            tbody.appendChild(row);
        });
    });
});