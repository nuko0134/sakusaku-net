<?php

//基本ライブラリの読み込み
require_once('components/xss.php');
require_once('components/components.php');

if(isset($_REQUEST['url'])){
    $url = $_REQUEST['url'];

    //画像ファイル化によって対応を分ける
    if(is_image_url($url)){
        $root_url = $_REQUEST['root_url'];

        header('Content-Type: image/webp');

        $imagefile_path = save_with_webp($url, $root_url);
        readfile($imagefile_path);
        unlink($imagefile_path);
    }
    else{
        if(isScrapable($url)){
            if(isDomainUrlonList($url)){
                if (isset($_REQUEST['extra'])){
                    $url = $url . $_REQUEST['titles'];
                }
    
                $content_raw_array = Readability_Raw_Extra($url);
    
                $title = $content_raw_array[0];
                $content_raw = $content_raw_array[1];
    
                //HTMLのリンクを変換
                $content_url_prefix = Add_Prefix_2_Link($content_raw, "http://localhost/sakusaku-net/read.php?url=");
    
                //HTMLのリンクを変換
                $content_url_prefix = Add_Prefix_2_Img_Src($content_url_prefix, "http://localhost/sakusaku-net/read.php?root_url={$url}&url=");
            }
            else{
                //Rawの本文を取得
                $content_raw_array = Readability_Raw($url);
    
                $title = $content_raw_array[0];
                $content_raw = $content_raw_array[1];
    
                //HTMLのリンクを変換
                $content_url_prefix = Add_Prefix_2_Link($content_raw, "http://localhost/sakusaku-net/read.php?url=");
    
                //HTMLのリンクを変換
                $content_url_prefix = Add_Prefix_2_Img_Src($content_url_prefix, "http://localhost/sakusaku-net/read.php?root_url={$url}&url=");
            }
        }
        else{
            $title = "Robots.txtによってブロックされました";
            $content_url_prefix = "お客様がリクエストされたウェブサイトは、Robots.txtによってスクレイピングが禁止されているため、表示できません。元のページを表示する、をクリックしてウェブサイトを表示してください。";
            $url = "read.php";
        }

    }
}
else{
    header("HTTP/1.1 404 Not Found");
    $title = "URLを指定してください";
    $content_url_prefix = "URLが指定されていません。URLを指定して再度試してください。";
    $url = "read.php";
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="style/reader.css">

    <title><?php echo $title ?></title>
</head>
<body>
    <header>
        <h1><a href="./"></p><span style="color: red;">サクサク</span><span  class="logo_title_net">ねっと</span></a></h1>
        <form action="read.php" method="get" class="search_container">
            <input type="text" class="search_box_input" placeholder="URL..." name="url">
        </form>
        <a href="<?php echo $url?>">元ページを表示</a>
    </header>
    <hr>
    <div class="article_body"><h1><?php echo $title ?></h1><?php echo $content_url_prefix ?></div>
    
    <footer>
    <hr>
        <p>記事内容・写真の著作権は<a href="<?php echo $url?>">元サイト</a>様の作成者の方に帰属いたします。記事の著作権者はこのサービス（さくさくねっと）の作成者（Komugikotan）ではありません。</p>
        <p>©Komugikotan 2023</p>
    </footer>
</body>
</html>