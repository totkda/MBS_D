// desktop\code\customer_import.js

document.addEventListener('DOMContentLoaded', function () {
    const importBtn = document.querySelector('.btn-success');
    if (importBtn) {
        importBtn.addEventListener('click', function () {
            const fileInput = document.querySelector('input[type="file"]');
            if (!fileInput.files.length) {
                alert('ファイルを選択してください');
                return;
            }
            // ファイルの取込処理（API送信等）
            alert('顧客データの取り込みを実施します（ダミー）');
        });
    }
});
