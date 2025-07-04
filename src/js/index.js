// desktop\code\index.js

document.addEventListener('DOMContentLoaded', function () {
    const totalPages = 20;    // 総ページ数（好きな数字でOK）
    let currentPage = 7;      // 現在のページ（テスト用で7にしてます）

    function renderPagination() {
        const area = document.getElementById('pagination-area');
        area.innerHTML = '';

        // ←ボタン
        const prevBtn = document.createElement('button');
        prevBtn.textContent = '←';
        prevBtn.className = 'btn btn-outline-secondary mx-1';
        prevBtn.disabled = (currentPage === 1);
        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                renderPagination();
                // ページ切り替え時の処理をここに
            }
        });
        area.appendChild(prevBtn);

        // ページ番号生成ロジック
        let pages = [];

        if (totalPages <= 10) {
            // 全部表示
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            // たくさんある場合
            pages.push(1); // 1ページ目は必ず
            let start = Math.max(2, currentPage - 1);
            let end = Math.min(totalPages - 1, currentPage + 1);

            if (start > 2) {
                pages.push('...');
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            if (end < totalPages - 1) {
                pages.push('...');
            }
            pages.push(totalPages); // 最後のページは必ず
        }

        // ページ番号と...を描画
        pages.forEach(p => {
            if (p === '...') {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'mx-1';
                area.appendChild(ellipsis);
            } else {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = p;
                pageBtn.className = 'btn btn-outline-primary mx-1';
                if (p === currentPage) {
                    pageBtn.classList.add('active');
                }
                pageBtn.addEventListener('click', function () {
                    currentPage = p;
                    renderPagination();
                    // ページ切り替え時の処理をここに
                });
                area.appendChild(pageBtn);
            }
        });

        // →ボタン
        const nextBtn = document.createElement('button');
        nextBtn.textContent = '→';
        nextBtn.className = 'btn btn-outline-secondary mx-1';
        nextBtn.disabled = (currentPage === totalPages);
        nextBtn.addEventListener('click', function () {
            if (currentPage < totalPages) {
                currentPage++;
                renderPagination();
                // ページ切り替え時の処理をここに
            }
        });
        area.appendChild(nextBtn);
    }

    renderPagination();
});
