// desktop\code\order_detail.js

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('pdf-btn').addEventListener('click', function () {
        alert('PDFダウンロード機能（ダミー）');
    });
    document.getElementById('delete-btn').addEventListener('click', function () {
        if (confirm('本当に削除しますか？')) {
            alert('削除しました（ダミー）');
        }
    });
    document.getElementById('edit-btn').addEventListener('click', function () {
        alert('編集完了（ダミー）');
    });
});
