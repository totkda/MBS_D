$(document).ready(function() {
    const $input = $('#customer_name');
    const $suggestBox = $('<div id="suggest-box"></div>').css({
        position: 'absolute',
        border: '1px solid #ccc',
        backgroundColor: '#fff',
        zIndex: 1000,
        width: $input.outerWidth(),
        maxHeight: '150px',
        overflowY: 'auto'
    }).hide();

    $input.after($suggestBox);

    $input.on('input', function() {
        const keyword = $(this).val();
        if (keyword.length === 0) {
            $suggestBox.hide();
            return;
        }

        $.get('customer_suggest.php', { keyword }, function(data) {
            $suggestBox.empty();
            if (data.length === 0) {
                $suggestBox.hide();
                return;
            }

            data.forEach(name => {
                const $item = $('<div></div>').text(name).css({
                    padding: '5px',
                    cursor: 'pointer'
                }).on('click', function() {
                    $input.val(name);
                    $suggestBox.hide();
                }).hover(
                    function() { $(this).css('background-color', '#f0f0f0'); },
                    function() { $(this).css('background-color', '#fff'); }
                );
                $suggestBox.append($item);
            });
            const offset = $input.offset();
            $suggestBox.css({
                top: offset.top + $input.outerHeight(),
                left: offset.left
            }).show();
        }, 'json');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customer_name, #suggest-box').length) {
            $suggestBox.hide();
        }
    });
});
