document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('customer-name');

    if (!input) return; // 要素が存在しない場合は終了

    // サジェストボックスの作成
    const suggestionBox = document.createElement('div');
    suggestionBox.id = 'suggestions';
    suggestionBox.style.position = 'absolute';
    suggestionBox.style.backgroundColor = 'white';
    suggestionBox.style.border = '1px solid #ccc';
    suggestionBox.style.zIndex = '1000';
    suggestionBox.style.width = input.offsetWidth + 'px';
    suggestionBox.style.maxHeight = '200px';
    suggestionBox.style.overflowY = 'auto';
    suggestionBox.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
    suggestionBox.style.cursor = 'pointer';
    suggestionBox.style.fontSize = '14px';

    input.parentNode.style.position = 'relative'; // 親を relative にして絶対位置を制御
    input.parentNode.appendChild(suggestionBox);

    input.addEventListener('input', async () => {
        const keyword = input.value.trim();
        if (keyword.length === 0) {
            suggestionBox.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`customer_search.php?keyword=${encodeURIComponent(keyword)}`);
            const names = await response.json();

            suggestionBox.innerHTML = '';
            names.forEach(name => {
                const div = document.createElement('div');
                div.textContent = name;
                div.style.padding = '5px 10px';
                div.addEventListener('click', () => {
                    input.value = name;
                    suggestionBox.innerHTML = '';
                });
                suggestionBox.appendChild(div);
            });
        } catch (error) {
            console.error('検索エラー:', error);
        }
    });

    // 外部クリックで候補を閉じる
    document.addEventListener('click', (e) => {
        if (!suggestionBox.contains(e.target) && e.target !== input) {
            suggestionBox.innerHTML = '';
        }
    });
});
