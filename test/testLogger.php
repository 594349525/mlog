<?php
require_once "../vendor/autoload.php";

$str = "xxxxxxx";
$str1 = Xiangxin\Logger\MLog::TestWrite($str);
var_dump($str1);
die;