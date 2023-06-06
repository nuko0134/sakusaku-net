<?php

require_once("readability/Readability.php");

//リンクにすべてプレフィックスなどを追加する関数
function Add_Prefix_2_Link($content, $prefix){
    // 正規表現パターン（[^>]*を追加）
    $pattern = '/<a[^>]* href="(?!https?:\/\/redirect\.com)(?!.+<img.+)([^"]+)"/';

    // 置換後の文字列
    $replacement = '<a href="'.$prefix.'$1"';

    // preg_replace関数で置換
    $new_content = preg_replace($pattern, $replacement, $content);

    // 結果を表示
    return $new_content;
}


// HTMLからimgタグを探し出してsrc属性に$prefixを追加する関数
function Add_Prefix_2_Img_Src($html, $prefix) { // $prefixを引数として受け取る
    // 正規表現でimgタグを見つける
    $imgRegex = '~<img.*?>~';
    // imgタグごとに処理する
    $newHtml = preg_replace_callback($imgRegex, function($imgTag) use ($prefix) { // $prefixをクロージャに渡す
      // 正規表現でsrc属性の内容を取り出す
      $srcRegex = '/src="(.*?)"/';
      preg_match($srcRegex, $imgTag[0], $srcMatch);
      // src属性がなければそのまま返す
      if (!$srcMatch) return $imgTag[0];
      // src属性の内容を取り出す
      $src = $srcMatch[1];
      // src属性の内容がdata:imageで始まる場合はdata-src属性の内容に置き換える
      if (strpos($src, "data:image") === 0) {
        // 正規表現でdata-src属性の内容を取り出す
        $dataSrcRegex = '/data-src="(.*?)"/';
        preg_match($dataSrcRegex, $imgTag[0], $dataSrcMatch);
        // data-src属性があればその内容に置き換える
        if ($dataSrcMatch) {
          $src = $dataSrcMatch[1];
        }
      }
      // src属性の内容に$prefixを追加する
      $newSrc = $src;
      // imgタグのsrc属性を新しい内容に置き換える
      $newImgTag = preg_replace($srcRegex, "src=\"$newSrc\"", $imgTag[0]);
      // 新しいimgタグを返す
      return $newImgTag;
    }, $html);
    // 新しいHTMLを返す
    return Add_Prefix_2_Img_Src_Tmp($newHtml, $prefix);
  }
  
  
//画像を変換する
function Add_Prefix_2_Img_Src_Tmp($content, $prefix){
    // 正規表現パターン（[^>]*を追加し、src属性以外のものを無視）
    $pattern = '/<img[^>]* src="(?!https?:\/\/redirect\.com)([^"]+)"[^>]*>/';
    // 置換後の文字列（src属性だけにする）
    $replacement = '<img src="'.$prefix.'$1">';
    // preg_replace関数で置換
    $new_content = preg_replace($pattern, $replacement, $content);
    // 結果を表示
    return $new_content;
}


// URLが画像ファイルかどうかを正規表現で確認する関数
function is_image_url($url) {
    // 画像ファイルの拡張子を表す正規表現
    $pattern = '/\.(png|webp|jpg|jpeg|gif)(\?.*)?$/i';
    // URLに正規表現がマッチすればtrueを返す
    return preg_match($pattern, $url);
}

function save_with_webp($img_path, $url){
    if (str_starts_with($img_path, "./")){
        if(str_ends_with($url, ".html")){
            $int = 0;
            while($int < 6){
                $url = mb_substr($url, 0, -1);
                $int++;
            }
            $url = $url . "/";
            $img_path = str_replace("./", $url, $img_path);
        }
        else if(str_ends_with($url, ".htm")){
            $int = 0;
            while($int < 5){
                $url = mb_substr($url, 0, -1);
                $int++;
            }
            $url = $url . "/";
            $img_path = str_replace("./", $url, $img_path);
        }
        else if(str_ends_with($url, "/")){
            $img_path = str_replace("./", $url, $img_path);
        }
        else{
            $url = $url . "/";
            $img_path = str_replace("./", $url, $img_path);
        }
    }
    if (str_starts_with($img_path, "/")){
        $url_parts = parse_url($url, PHP_URL_HOST);
        $img_path = "http://{$url_parts}{$img_path}";
    }

    $data = file_get_contents($img_path);
    $tmp_filename = mt_rand(1, 100000000);

    file_put_contents("tmp/" . $tmp_filename, $data);

    $pattern_png = '/\.(png)(\?.*)?$/i';
    $pattern_jpg = '/\.(jpg|jpeg)(\?.*)?$/i';
    $pattern_webp = '/\.(webp)(\?.*)?$/i';
    $pattern_gif = '/\.(gif)(\?.*)?$/i';

    if(preg_match($pattern_jpg, $img_path)){
        // jpgファイルを読み込む
        $image_file = imagecreatefromjpeg("tmp/" . $tmp_filename);
    }
    else if(preg_match($pattern_png, $img_path)){
        // jpgファイルを読み込む
        $image_file = imagecreatefrompng("tmp/" . $tmp_filename);
    }
    else{
        unlink("../tmp/" . $tmp_filename);
        return ($img_path);
    }

    // コピー元画像のサイズ取得
    $imagesize = getimagesize("tmp/" . $tmp_filename);
    $src_w = $imagesize[0];
    $src_h = $imagesize[1];

    // コピー先画像サイズ指定
    $resize_w = $src_w / 3;
    $resize_h = $src_h / 3;

    // コピー先画像作成
    $resize_image = imagecreatetruecolor($resize_w, $resize_h);
    
    // リサイズしてコピー
    imagecopyresampled(
        $resize_image, // コピー先の画像
        $image_file, // コピー元の画像
        0,          // コピー先の x 座標
        0,          // コピー先の y 座標。
        0,          // コピー元の x 座標
        20,          // コピー元の y 座標
        $resize_w,     // コピー先の幅
        $resize_h,     // コピー先の高さ
        $src_w,     // コピー元の幅
        $src_h
    );

    
    // webp画像に変換して保存
    $webp = imagewebp($resize_image, "tmp/" . $tmp_filename . ".webp");

    // 読み込んだ画像をメモリから開放しておく
    imagedestroy($image_file);
    imagedestroy($resize_image);

    unlink("tmp/" . $tmp_filename);

    return "tmp/" . $tmp_filename . ".webp";
}

function Readability_Raw($url){
    $title = "軽量化できませんでした";
    $content_raw = "<p>記事の取得時にエラーが発生しました。URLが正しいかを確認してください。</p>";

    //URLからHTMLを取得してUTF8にエンコーディング
    try{
        $html = file_get_contents($url);
        $html = mb_convert_encoding($html, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS" );

    }
    catch(Exception $e){
        header("HTTP/1.1 404 Not Found");
        $title = "軽量化できませんでした";
        $content_raw = "<p>記事の取得時にエラーが発生しました。ドメイン名が間違っている可能性があります。URLが正しいかを確認してください。</p>";
    }

    //tidy_parse_stringが使用可能であれば、
    //tidy::cleanRepairでHTMLの誤りなどを修正
    if (function_exists('tidy_parse_string')) {
        $tidy = tidy_parse_string($html, array(), 'UTF8');
        $tidy->cleanRepair();
        $html = $tidy->value;
    }

    //Readabilityを初期化
    $readability = new Readability($html, $url);
    $result = $readability->init();

    if ( empty($result) ) return array($title, $content_raw);;

    //タイトルを取得
    $title =  $readability->getTitle()->textContent;

    //記事本文を取得
    $content_raw = $readability->getContent()->innerHTML;

    //再度、tidy_parse_stringを使用
    if (function_exists('tidy_parse_string')) {
        $tidy = tidy_parse_string($content_raw, array('indent'=>true, 'show-body-only' => true), 'UTF8');
        $tidy->cleanRepair();
        $content_raw = $tidy->value;
    }

    return array($title, $content_raw);

}

// ファイルから特定の一行のみを取得
function getSpecificLineFromFile($filename, $lineNum) {
 
    if ($lineNum < 1) {
      exit("無効な行の指定です。");
    }
   
    $fp = fopen($filename, 'r');
    for ($i = 0; $i < $lineNum; $i++) { 
      $targetLine = fgets($fp);
    }
    fclose($fp);
   
    return trim($targetLine);
}

function isDomainUrlonList($url){
    $domain_list = ['ja.wikipedia.org', 'www.youtube.com', 'www.google.com'];

    foreach($domain_list as $value){
        $url_parts = parse_url($url, PHP_URL_HOST);
        if ($url_parts == $value){
            return true;
        }
    }

    return false;
}

function Readability_Raw_Extra($url){
    $domain_list = ['ja.wikipedia.org', 'www.youtube.com', 'www.google.com'];


    $url_parts = parse_url($url, PHP_URL_HOST);
    if ($url_parts == $domain_list[0]){
        //$content = "
        //<form action=\"read.php\" method=\"get\" class=\"search_container\">
            
        //    <input type=\"text\" name=\"extra\" value=\"true\" hidden>
        //    <input type=\"text\" name=\"url\" value=\"read.php?url=https://ja.wikipedia.org/wiki/\" hidden>
        //    <input type=\"text\" class=\"search_box_input\" placeholder=\"Wikipedia内を検索...\" name=\"titles\">
        //</form>";


        $url_parts_all = parse_url($url);
        $titles = explode('/', $url_parts_all['path'])[2];

        // 検索用URL
        $tmp_url = "https://ja.wikipedia.org/w/api.php?";

        $params_list = array('action'=>'query','format'=>'json','prop'=>'extracts','explaintext'=>'True','exsectionformat'=>'plain', 'titles'=>$titles);
        $req_param = http_build_query($params_list);
        $request = $tmp_url.$req_param;

        $json = file_get_contents($request,true);
        $json_d = json_decode($json,true);

        $article_id = key(array_slice( $json_d['query']['pages'], 0, 1, true));

        if($article_id != -1){
            extract_substrings('--', '-', str_replace("\n", '-', $json_d['query']['pages'][$article_id]['extract']));

            $content_with_bar = str_replace("\n", '-', $json_d['query']['pages'][$article_id]['extract']);

            //$array_content_with_bar_h4 = extract_substrings('---', '--', $content_with_bar);

            //foreach($array_content_with_bar_h4 as $value){
              //  $content_with_bar = str_replace("---{$value}--", "<br><h4>{$value}</h4>", $content_with_bar);
            //}

            $array_content_with_bar_h3 = extract_substrings('---', '-', $content_with_bar);

            foreach($array_content_with_bar_h3 as $value){
                $content_with_bar = str_replace("---{$value}-", "<br><h3>{$value}</h3>", $content_with_bar);
            }

            $content_with_bar = str_replace("-", "<br>", $content_with_bar);

            $title = $titles;
            $content_raw = '<p>' . $content_with_bar . '</p>';

            return array($title, $content_raw);
        }
        else{
            header("HTTP/1.1 404 Not Found");
            $title = $titles;
            $content_raw = "ウィキペディアに{$titles}という項目が存在しませんでした。";
            return array($title, $content_raw);
        }
    }
    else if($url_parts == $domain_list[1]){
        header("HTTP/1.1 404 Not Found");
        $title = "Youtubeには対応していません。";
        $content_raw = "「元ページを表示」をクリックしてYoutubeアプリでご視聴ください。";
        return array($title, $content_raw);
    }
    else if($url_parts == $domain_list[2]){
        header("HTTP/1.1 404 Not Found");
        $title = "Google.comには対応していません。";
        $content_raw = "本サイトはGoogle検索を使用していますので検索結果は同じです。軽量化を望まれていて、Google検索を使用したい場合はこのサービスに付属している検索エンジンをご利用ください。";
        return array($title, $content_raw);
    }
    

    return false;
}

function iterative_cutter($needle_start, $needle_end, $content)
{
  $start = 0;
  $results = array();
  while(true){
    $ret = strpos($content, $needle_start, $start);
    if($ret == false){
      return $results;
    }
    $start = $ret + strlen($needle_start);
    $end = strpos($content, $needle_end, $start);
    $line = substr($content, $start, $end - $start);
    $results[] = $line;
    $start = $end + strlen($needle_end);
  }
}

function extract_substrings($begin, $end, $text)
{
    $pos = 0;
    $result = array();
    while(true){
        $found = strpos($text, $begin, $pos);
        if($found == false){
            return $result;
        }

        $pos = $found + strlen($begin);
        $stop = strpos($text, $end, $pos);
        $sub = substr($text, $pos, $stop - $pos);
        $result[] = $sub;
        $pos = $stop + strlen($end);
    }
}

function getRobots($url) {
  $robotsUrl = "http://" . parse_url($url, PHP_URL_HOST) . "/robots.txt"; // robots.txtのURLを作成
  $robot = null; // 現在のUser-agentを表す変数
  $allRobots = []; // 全てのUser-agentとDisallowパスを格納する配列

  $fh = fopen($robotsUrl,'r'); // robots.txtを読み込む
  while (($line = fgets($fh)) != false) { // 1行ずつ処理する
    if (preg_match('/User-agent:\s*(.*)/i', $line, $match)) { // User-agent行なら
      $robot = $match[1]; // User-agent名を取得
      if (!isset($allRobots[$robot])) { // 配列にまだ存在しなければ
        $allRobots[$robot] = []; // 空の配列を作成
      }
    } elseif (preg_match('/Disallow:\s*(.*)/i', $line, $match)) { // Disallow行なら
      if ($robot !== null) { // Us  er-agent名が設定されていれば
        $allRobots[$robot][] = $match[1]; // Disallowパスを配列に追加
      }
    }
  }
  fclose($fh); // ファイルを閉じる
  return $allRobots; // 配列を返す
}

function isScrapable($url) {
  $parsedUrl = parse_url($url); // URLをパースする
  $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host']; // ベースURLを作成
  $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/'; // パス部分を取得（なければ/）
  $allRobots = getRobots($baseUrl); // robots.txtの内容を取得

  if (isset($allRobots['*'])) { // 全てのUser-agentに対する指示があれば
    foreach ($allRobots['*'] as $disallow) { // Disallowパスを順にチェック
      if ($disallow === '' || $disallow === '/') { // 空文字や/なら
        return false; // スクレイピング不可
      } elseif (substr($disallow, -1) === '$' && $path === substr($disallow, 0, -1)) { // $で終わるパスと完全一致なら
        return false; // スクレイピング不可
      } elseif (strpos($path, $disallow) === 0) { // パスの先頭がDisallowパスと一致なら
        return false; // スクレイピング不可
      }
    }
  }

  $path = $path . '/';

  if (isset($allRobots['*'])) { // 全てのUser-agentに対する指示があれば
    foreach ($allRobots['*'] as $disallow) { // Disallowパスを順にチェック
      if ($disallow === '' || $disallow === '/') { // 空文字や/なら
        return false; // スクレイピング不可
      } elseif (substr($disallow, -1) === '$' && $path === substr($disallow, 0, -1)) { // $で終わるパスと完全一致なら
        return false; // スクレイピング不可
      } elseif (strpos($path, $disallow) === 0) { // パスの先頭がDisallowパスと一致なら
        return false; // スクレイピング不可
      }
    }
  }


  return true; // スクレイピング可能
}

function replace_img_tags($html, $replacement) {
    // IMGタグの正規表現パターン
    $pattern = '/<img[^>]*>/i';
    // $replacementで置換する
    $result = preg_replace($pattern, $replacement, $html);
    // 結果を返す
    return $result;
}
?>
