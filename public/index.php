<?php
//これでconfig.phpを呼んで共通設定（セッションスタートとDBやxss対策、ログインしないと見れないように）
//DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
require_once __DIR__ . '/config.php';

//ログイン判定　user_idがあればログイン済み判定
if (isset($_SESSION['user_id'])) {
    //ログイン済みならtimeline.phpへ移動
    header('Location: timeline.php');
} else {
    //ログインがまだならlogin.phpへ移動
    header('Location: login.php');
}
//処理を終了
exit;
