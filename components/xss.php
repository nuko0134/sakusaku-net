<?php

//エスケープ処理をする関数
function escape($value) {
    return htmlentities($value, ENT_QUOTES, 'UTF-8');
  }
  
//$_POSTの値をエスケープ
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
      $_POST[$key] = escape($value);
    }
}
  
//$_GETの値をエスケープ
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
      $_GET[$key] = escape($value);
    }
}
  
  
//$_REQUESTの値をエスケープ
if (!empty($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
      $_REQUEST[$key] = escape($value);
    }
}
  

?>