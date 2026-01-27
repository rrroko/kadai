<?php
//これでconfig.phpを呼んで共通設定（セッションスタートとDBやxss対策、ログインしないと見れないように）
//DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
require_once __DIR__ . '/config.php';
//ログインしてる人だけが入れるようにログインしていない人はlogin.phpに移動
require_login();
?>
<!--ここからHTML-->
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>投稿</title>
</head>
<body>
<h1>投稿</h1>

<nav>
  <a href="timeline.php">タイムライン</a> |
  <a href="all_post.php">全投稿</a> |
  <a href="profile.php">ユーザー一覧</a> |
  <a href="logout.php">ログアウト</a>
</nav>

<hr>
<!--metodでフォームのデータをPOSTで送る、actionで送信先、enctypeがないと画像を送れない-->
<form id="postForm" method="post" action="post_create.php" enctype="multipart/form-data">
  <div>
    <label>本文</label><br>
<!--本文入力 name=bodyでPHP（post_create.php）側で$_POST['body']で受け取れる-->
    <textarea name="body" rows="4" cols="50" required></textarea>
  </div>

  <div>
    <label>画像</label><br>

    <!--画像選択-->
    <!--送る前にリサイズして軽く、同じ画像を追加、追加削除したリストを反映するために　選ぶ用imagePickerと送る用imagesHiddenに分けてる-->
    <input type="file" id="imagePicker" accept="image/*">
    <!--追加済みの枚数表示-->
    <p>追加済み：<span id="count">0</span> / 4</p>
    <!--プレビューの表示エリア  JSでimgを作って並べる-->
    <div id="previews" style="display:flex; gap:8px; flex-wrap:wrap;"></div>

    <input type="file" id="imagesHidden" name="images[]" multiple style="display:none;">
  </div>

  <button type="submit">投稿する</button>
</form>

<!--ここからjavascript-->
<script>
//画像を縮小してJPEGに変換する
async function resizeImageToBlob(file, maxW = 1280, maxH = 1280, quality = 0.8) {
  //ファイルを画像として読み込む
  const img = document.createElement('img');
  img.src = URL.createObjectURL(file);
  await new Promise((resolve) => img.onload = resolve);

  //縮小サイズを計算
  let w = img.width, h = img.height;
  //これがあるので小さい画像は拡大されない
  const scale = Math.min(maxW / w, maxH / h, 1);
  w = Math.round(w * scale);
  h = Math.round(h * scale);

  //canvasで描画
  //canvasで画像加工用の板を作る
  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;

  const ctx = canvas.getContext('2d');
  //縮小描画している
  ctx.drawImage(img, 0, 0, w, h);

  //JPEG化してBlobを作る
  const blob = await new Promise((resolve) =>
    canvas.toBlob(resolve, 'image/jpeg', quality)
  );

  return blob;
}

//画面のパーツをつかむ
const picker = document.getElementById('imagePicker');
const countEl = document.getElementById('count');
const previews = document.getElementById('previews');
const hidden = document.getElementById('imagesHidden');

//追加した画像ファイルを記憶
const selected = [];

//プレビューのために作ったURLを一時的に保存
const previewUrls = [];

//プレビューを表示
function renderPreviews() {
  //バグらないために全部けして作り直す
  previews.innerHTML = '';
  countEl.textContent = String(selected.length);

  //1枚ずつプレビューカードを作る
  //file:画像ファイル  idx:何番目か
  selected.forEach((file, idx) => {
    const url = previewUrls[idx];

    const card = document.createElement('div');
    card.style.border = '1px solid #ccc';
    card.style.padding = '6px';
    card.style.width = '140px';

    //プレビュー画像を作る
    const img = document.createElement('img');
    img.src = url;
    img.alt = 'preview';
    img.style.maxWidth = '128px';
    img.style.maxHeight = '128px';
    img.style.display = 'block';
    img.style.marginBottom = '6px';

    //ファイル名も表示
    const name = document.createElement('div');
    name.textContent = file.name;
    name.style.fontSize = '12px';
    name.style.overflow = 'hidden';
    name.style.textOverflow = 'ellipsis';
    name.style.whiteSpace = 'nowrap';

    //削除ボタン
    const rm = document.createElement('button');
    rm.type = 'button';
    rm.textContent = '削除';
    rm.style.marginTop = '6px';
    rm.addEventListener('click', () => {
      //削除時に一時的に作ったプレビューURLを削除
      URL.revokeObjectURL(previewUrls[idx]);

      selected.splice(idx, 1);
      previewUrls.splice(idx, 1);

      renderPreviews();
    });

    card.appendChild(img);
    card.appendChild(name);
    card.appendChild(rm);
    previews.appendChild(card);
  });
}

//画像を選んだら実行される
picker.addEventListener('change', () => {
  //１枚取り出して数える
  const f = picker.files && picker.files[0] ? picker.files[0] : null;
  if (!f) return;

  //最大４枚までなのでチェック
  if (selected.length >= 4) {
    alert('画像は最大4枚までです');
    picker.value = ''; // 同じファイルを再選択できるようにリセットしている
    return;
  }

  //追加してプレビューを作成
  selected.push(f);
  previewUrls.push(URL.createObjectURL(f));

  //同じファイルをもう一回選べるように必ずリセット
  picker.value = '';

  renderPreviews();
});

//投稿ボタンを押して時に処理
document.getElementById('postForm').addEventListener('submit', async (e) => {
  if (selected.length === 0) return; // 画像なし場合はそのまま送る

  //画像があるなら止めてＪＳで加工
  e.preventDefault();

  //hiddenに詰めるDataTransferはinput.filesを作るためのもの
  const dt = new DataTransfer();

  for (let i = 0; i < selected.length; i++) {
    //リサイズして画像（File）を送る
    //画像をBlob（JPEG）にする
    //それをfileにする
    //ファイル名は必ずユニークにする
    //dtに追加
    const blob = await resizeImageToBlob(selected[i]);

    const uniqueName = `${Date.now()}_${i}_${Math.random().toString(16).slice(2)}.jpg`;
    const file = new File([blob], uniqueName, { type: 'image/jpeg' });

    dt.items.add(file);
  }

  //hiddenにセットして本送信
  hidden.files = dt.files;
  e.target.submit();
});


renderPreviews();
</script>

</body>
</html>
