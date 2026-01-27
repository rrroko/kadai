<?php
//これでconfig.phpを呼んで共通設定（セッションスタートとDBやxss対策、ログインしないと見れないように）
//DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
require_once __DIR__ . '/config.php';

//セッション変数（ログイン情報）を空にする
$_SESSION = [];
//セッションを無効化
session_destroy();

//ログアウト後にlogin.phpに移動
header('Location: login.php');
//処理しよう
exit;
