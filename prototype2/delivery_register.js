// desktop\code\delivery_register.js

document.addEventListener('DOMContentLoaded', function () {
    // モーダルオープン
    const openModalBtn = document.getElementById('openModal');
    if (openModalBtn) {
        openModalBtn.addEventListener('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('delivery-register-form'));
            modal.show();
        });
    }

    // 登録二重確認モーダル
    const insertBtn = document.getElementById('delivery-insert-button');
    if (insertBtn) {
        insertBtn.addEventListener('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('delivery-insert'));
            modal.show();
        });
    }
});
