document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('customer_name');
    const suggestionBox = document.getElementById('suggestions');

    input.addEventListener('input', () => {
        const query = input.value.trim();
        suggestionBox.innerHTML = '';
        if (query.length === 0) return;

        fetch(`./customer_suggest.php?term=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                suggestionBox.innerHTML = '';
                data.forEach(name => {
                    const item = document.createElement('a');
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = name;
                    item.style.cursor = 'pointer';
                    item.addEventListener('click', () => {
                        input.value = name;
                        suggestionBox.innerHTML = '';
                    });
                    suggestionBox.appendChild(item);
                });
            });
    });

    // 外部クリックでサジェスト非表示
    document.addEventListener('click', (event) => {
        if (!suggestionBox.contains(event.target) && event.target !== input) {
            suggestionBox.innerHTML = '';
        }
    });
});

