<?php

//基本ライブラリの読み込み
require_once('components/xss.php');
require_once('components/components.php');
require_once('components/cookie.php');

// 初期値設定
$search_query = $_REQUEST['keyword'];
$api_key = getSpecificLineFromFile('.env', 2);
$cx = getSpecificLineFromFile('.env', 3);

// 検索用URL
$tmp_url = "https://www.googleapis.com/customsearch/v1?";

$params_list = array('q'=>$search_query,'key'=>$api_key,'cx'=>$cx,'alt'=>'json','start'=>'1');
$req_param = http_build_query($params_list);
$request = $tmp_url.$req_param;

// jsonデータ取得
$json = file_get_contents($request,true);
$json_d = json_decode($json,true);

//itemsの中身を取得
$items = $json_d['items'];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="style/search.css">

    <title><?php echo $search_query ?></title>
</head>
<body>
    <header>
        <h1 class="logo_title"><a href="./"></p><span style="color: red;">サクサク</span><span class="logo_title_net">ねっと</span></a></h1>
        <form action="search.php" method="get" class="search_container">
                    <input type="text" class="search_box_input" placeholder="キーワード..." name="keyword" value="<?php echo $search_query ?>">
        </form>
    </header>
    <main>
        <?php

        // urlを取得
        foreach($items as $value){
            echo "<div class=\"blank_10px\"></div><div class=\"results_item\">";

            $link = $value['link'];
            $title = $value['title'];
            $snippet = htmlspecialchars($value['snippet']);

            echo "<a href=\"read.php?url={$link}\"><div class=\"item_title\">{$title}</div></a>";
            echo "<p class=\"item_description\">{$snippet}</p>";

            echo "</div>";
        }

        ?>
        
    </main>
</body>
</html>
