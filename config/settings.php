<?php

//基本ライブラリの読み込み
require_once('../components/xss.php');
require_once('../components/components.php');
require_once('../components/cookie.php');

if(isset($_POST['img_show'])){
    if($_POST['img_show'] == "true"){
        echo "<script>document.cookie = 'SAKUSAKUimgshow=true; Expires=Wed, 06 Jan 2030 08:11:26 GMT; SameSite=Lax;';</script>";
        echo "設定が変更されました。<a href=\"settings.php\">リロードする</a>";
    }
    else if($_POST['img_show'] == "false"){
        echo "<script>document.cookie = 'SAKUSAKUimgshow=false; Expires=Wed, 06 Jan 2030 08:11:26 GMT; SameSite=Lax;';</script>";
        echo "設定が変更されました。<a href=\"settings.php\">リロードする</a>";
    }
    else{
        $tmp_content = "設定できませんでした。";
    }
}
else{
    $tmp_content = "";
}

$tmp_content = $_COOKIE['SAKUSAKUimgshow'];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="../style/global.css">
    <link rel="stylesheet" href="../style/reader.css">

    <title>設定 -サクサクねっと</title>
</head>
<body>
    <header>
        <h1><a href="../"></p><span style="color: red;">サクサク</span><span  class="logo_title_net">ねっと</span></a></h1>
    </header>
    <hr>
    <div style="text-align: center;" class="article_body">
        <h1>設定</h1>
        <p>画像を表示するかを設定できます。</p>
        <p>いまは<?php echo $_COOKIE['SAKUSAKUimgshow']?>です。</p>
        <form method="post">
            <input type="checkbox" name="img_show" value="true">表示する
            <input type="checkbox" name="img_show" value="false">表示しない
            <br>
            <input type="submit" value="送信">
        </form>
    </div>  
    <footer style="position: absolute; bottom: 0; width: 90%;">
    <hr>
        <p>©Komugikotan 2023</p>
    </footer>
</body>
</html>