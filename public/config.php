<?php
//セッション開始
session_start();
//データベース接続　　getenvでdocker-compose.ymlにあるenvironmentを呼んでる
$DB_HOST = getenv('DB_HOST');
$DB_NAME = getenv('DB_NAME');
$DB_USER = getenv('DB_USER');
$DB_PASS = getenv('DB_PASS');

//Mysqlにつなげるコード
$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

try {
    //pdoはデータベースとやり取りするよう
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        //失敗したときにエラー出す
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        //エラーを$user['email']で表示されるように、これないと$user[0]でわかりずらくなる
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    //失敗したら下の文が表示される
} catch (PDOException $e) {
    exit("データベース接続できてない: " . $e->getMessage());
}

//XSS対策用　これがないと投稿でコードが書けてしまう
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

//ログインを必須にする　これがないとログイン必須ページに入れてしまう
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
