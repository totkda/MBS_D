// ドキュメントの読み込みが完了したら実行
$(function () {
    // フォーム送信時に完了モーダルを表示し、送信を一時停止
    $('#import-form').on('submit', function (e) {
        e.preventDefault();
        const completeModal = new bootstrap.Modal(document.getElementById('import-complete'));
        completeModal.show();
        // モーダルの「閉じる」ボタンでフォームを実際に送信
        $('#import-complete-close').one('click', () => {
            e.target.submit();
        });
    });
});