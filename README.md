## 1. EC2 準備(インスタンスの作成）

EC2を開いて
インスタンスを起動をクリック

アプリケーションおよび OS イメージ (Amazon マシンイメージ)
- Amazon Linux 2023
  
インスタンスタイプ
- t3.micro

キーペア
 -新規作成して
 -キーペアのタイプRSA
 -プライベートキーファイル形式　.pem で作成  
 -自動でダウンロードされる .pemのファイルを保存


ネットワーク設定
-ファイアウォール（セキュリティグループ）
-セキュリティグループを作成
-SSHトラフィックを許可をチェック
-インターネットからのHTTPトラフィックを許可をチェック

インスタンスを起動を選択

インスタンスが起動しているか確認起動していたら次

## 2. SSH 接続

PowerShellを開いて

ssh -i C:\Users\Desktop\（キーペア作成時に自動でダウンロードされたファイル）key.pem ec2-user@<PublicIP>
　　　　　　　↑
       key.pemがある場所

  接続できたらOK

##  3. Dockerのインストール
-docker導入
-下のコードをPowerShellのSSHで入力

```bash
sudo dnf -y update || sudo yum -y update

sudo dnf -y install docker || sudo yum -y install docker

sudo systemctl enable --now docker

sudo usermod -aG docker ec2-user

sudo dnf -y install curl || sudo yum -y install curl
```
-Docker composeをインストール
```bash
ARCH=$(uname -m)
case "$ARCH" in
  x86_64)   BIN=docker-compose-linux-x86_64 ;;
  aarch64)  BIN=docker-compose-linux-aarch64 ;;
  *) echo "Unsupported arch: $ARCH"; exit 1 ;;
esac

sudo mkdir -p /usr/libexec/docker/cli-plugins

sudo curl -L "https://github.com/docker/compose/releases/download/v2.27.0/$BIN" \
  -o /usr/libexec/docker/cli-plugins/docker-compose
  
sudo chmod +x /usr/libexec/docker/cli-plugins/docker-compose
```
-反映するために再起動
```bash
exit
```
-もう一度SSHに入る
ssh -i C:\Users\Desktop\（キーペア作成時に自動でダウンロードされたファイル）key.pem ec2-user@<PublicIP>
　　　　　　　↑
       key.pemがある場所


## 4.gitのインストール
-gitを入れる
```bash

sudo dnf -y install git || sudo yum -y install git

git --version
```
-リポジトリをクローン
```bash
cd ~

rm -rf kadai

git clone https://github.com/rrroko/kadai.git

cd kadai
```
## 5.画像フォルダの準備
-画像フォルダの権限
```bash
sudo chmod -R 777 public/upload/image
```
## 6.ビルドと起動
```bash
docker compose up -d --build
```
-状態の確認
```bash
docker compose ps　←　ちゃんと起動していればOK
```
## 7.DBの作成
-init.sqlを適応
```bash
docker compose exec -T mysql mysql -ukadai -ppassword kadai_db < init.sql
```
## 8.サイト確認
http://<PublicIP>/
