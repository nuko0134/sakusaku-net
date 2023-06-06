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

//画像を変換する
function Add_Prefix_2_Img_Src($content, $prefix){
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
        if(!str_ends_with($url, "/")){
            $url = $url . "/";
            $img_path = str_replace("./", $url, $img_path);
        }
        else{
            $img_path = str_replace("./", $url, $img_path);
        }
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
            $title = $titles;
            $content_raw = "ウィキペディアに{$titles}という項目が存在しませんでした。";
            return array($title, $content_raw);
        }
    }
    else if($url_parts == $domain_list[1]){
        $title = "Youtubeには対応していません。";
        $content_raw = "「元ページを表示」をクリックしてYoutubeアプリでご視聴ください。";
        return array($title, $content_raw);
    }
    else if($url_parts == $domain_list[2]){
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


?>
