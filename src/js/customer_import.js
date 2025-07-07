// ドキュメントの読み込みが完了したら実行
$(function () {
    // ボタンがクリックされたときの処理
    $('#client-insert-button').on('click', function () {  // #①ポップアップを出すボタンにid属性を追加し、ボタンの名前をつける ②#ボタンの名前 に変更する
        // モーダルウィンドウを取得し、表示する
        const modal = new bootstrap.Modal(document.getElementById('client-insert'));  //  上で付けたポップアップの名前に変更する
        modal.show();
    });
});