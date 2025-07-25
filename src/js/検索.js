//注文管理検索.js,統計検索.js,納品管理検索.jsを１つにまとめたもの
window.addEventListener('DOMContentLoaded', () => {
    // 検索ボタン（共通で一つだけある想定）
    const searchButton = document.querySelector('input.btn.btn-primary.w-100');
    if (!searchButton) return;

    // ページ判別用に要素の存在チェックなど
    const isOrderManagePage = !!document.getElementById('order-date-since') && !!document.getElementById('status-select');
    const isStatisticsPage = !!document.getElementById('sort_select') && !!document.querySelector('select[name="order"]');
    const isDeliveryManagePage = !!document.getElementById('order-date-since') && !!document.getElementById('status-select') && document.querySelectorAll('tbody tr').length > 0;

    searchButton.addEventListener('click', () => {
        if (isOrderManagePage && !isStatisticsPage && !isDeliveryManagePage) {
            // 【注文管理検索.js】の処理
            orderManageSearch();
        } else if (isStatisticsPage) {
            // 【統計検索.js】の処理
            statisticsSearch();
        } else if (isDeliveryManagePage) {
            // 【納品管理検索.js】の処理
            deliveryManageSearch();
        }
    });

    // 注文管理検索.js の処理関数
    function orderManageSearch() {
        const dateSince = document.getElementById('order-date-since').value;
        const dateUntil = document.getElementById('order-date-until').value;
        const customerName = document.getElementById('customer_name').value.trim();
        const status = document.getElementById('status-select').value;
        const branchName = document.getElementById('branch_name').value.trim();

        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 4) return;

            const rowCustomerName = cells[1].textContent.trim();
            const rowDate = cells[2].textContent.trim();
            const rowStatus = cells[3].textContent.trim();
            // const rowBranchName = cells[4] ? cells[4].textContent.trim() : "";

            let show = true;

            if (customerName && !rowCustomerName.includes(customerName)) show = false;
            if (dateSince && rowDate < dateSince) show = false;
            if (dateUntil && rowDate > dateUntil) show = false;
            if (status !== 'すべて' && rowStatus !== status) show = false;
            // if (branchName && !rowBranchName.includes(branchName)) show = false;

            row.style.display = show ? '' : 'none';
        });
    }

    // 統計検索.js の処理関数
    function statisticsSearch() {
        const customerName = document.getElementById('customer_name').value.trim();
        const branchName = document.getElementById('branch_name').value.trim();
        const sortKey = document.getElementById('sort_select').value;
        const sortOrder = document.querySelector('select[name="order"]').value;

        const rows = Array.from(document.querySelectorAll('tbody tr'));

        let filteredRows = rows.filter(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 5) return false;

            const rowBranch = cells[0].textContent.trim();
            const rowCustomer = cells[2].textContent.trim();

            if (branchName && !rowBranch.includes(branchName)) return false;
            if (customerName && !rowCustomer.includes(customerName)) return false;

            return true;
        });

        const columnIndex = {
            'total_sales': 3,
            'avg_lead_time': 4,
            'customer_name': 2
        }[sortKey];

        filteredRows.sort((a, b) => {
            const valA = a.children[columnIndex].textContent.trim();
            const valB = b.children[columnIndex].textContent.trim();

            const numA = parseFloat(valA.replace(/,/g, ''));
            const numB = parseFloat(valB.replace(/,/g, ''));

            const isNumeric = !isNaN(numA) && !isNaN(numB);

            if (isNumeric) {
                return sortOrder === 'asc' ? numA - numB : numB - numA;
            } else {
                return sortOrder === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            }
        });

        const tbody = document.querySelector('tbody');
        tbody.innerHTML = '';
        filteredRows.forEach(row => tbody.appendChild(row));
    }

    // 納品管理検索.js の処理関数
    function deliveryManageSearch() {
        const dateSince = document.getElementById('order-date-since').value;
        const dateUntil = document.getElementById('order-date-until').value;
        const customerName = document.getElementById('customer_name').value.trim();
        const status = document.getElementById('status-select').value;
        const branchName = document.getElementById('branch_name').value.trim();

        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 5) return;

            const rowCustomerName = cells[1].textContent.trim();
            const rowDate = cells[2].textContent.trim();
            const rowStatus = cells[4].textContent.trim();

            let show = true;

            if (customerName && !rowCustomerName.includes(customerName)) show = false;
            if (dateSince && rowDate < dateSince) show = false;
            if (dateUntil && rowDate > dateUntil) show = false;
            if (status !== 'すべて' && rowStatus !== status) show = false;
            // 支店名でのフィルタを必要ならここに追加可能

            row.style.display = show ? '' : 'none';
        });
    }
});
