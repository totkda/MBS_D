// ページのDOMがすべて読み込まれた後に実行
window.addEventListener('DOMContentLoaded', () => {
    // 検索ボタンを取得
    const searchButton = document.querySelector('input.btn.btn-primary.w-100');

    // 検索ボタンがクリックされたときの処理
    searchButton.addEventListener('click', () => {
        // 各検索条件の値を取得
        const dateSince = document.getElementById('order-date-since').value; // 開始日
        const dateUntil = document.getElementById('order-date-until').value; // 終了日
        const customerName = document.getElementById('customer_name').value.trim(); // 顧客名
        const status = document.getElementById('status-select').value; // ステータス
        const branchName = document.getElementById('branch_name').value.trim(); // 支店名

        // テーブルのすべての行を取得
        const rows = document.querySelectorAll('tbody tr');

        // 各行ごとに表示・非表示を判定
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            // 必要なセル数がなければスキップ
            if (cells.length < 4) return;

            // 行から各値を取得
            const rowCustomerName = cells[1].textContent.trim(); // 顧客名
            const rowDate = cells[2].textContent.trim();         // 日付
            const rowStatus = cells[4].textContent.trim();       // ステータス

            let show = true; // 表示するかどうかのフラグ

            // 顧客名でフィルタ
            if (customerName && !rowCustomerName.includes(customerName)) show = false;
            // 日付（開始）でフィルタ（※rowDateNormが未定義なので修正が必要かも）
            if (dateSince && rowDateNorm < dateSince) show = false;
            // 日付（終了）でフィルタ
            if (dateUntil && rowDateNorm > dateUntil) show = false;
            // ステータスでフィルタ
            if (status !== 'すべて' && rowStatus !== status) show = false;

            // フラグに応じて行の表示・非表示を切り替え
            row.style.display = show ? '' : 'none';
        });
    });
});