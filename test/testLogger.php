<?php
require_once "../vendor/autoload.php";

use Xiangxin\Logger\MLog;

MLog::init('/Users/liyk/56hello/Mlog/logs/', '', '', 45);

MLog::info('测试内容5', [['name' => 'lyk'], ['code' => 'ddddd']], 'financial');
MLog::error('测试内容6', [['name' => 'lyk'], ['code' => 'ddddd']], 'note');
var_dump(1);
die;