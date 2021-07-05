<?php


namespace Xiangxin\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\WebProcessor;


class MLog
{
    public static function TestWrite($str): array
    {
        return ['test' => 'ok'];
    }

    private static $loggers;

    /**
     * 日志默认保存路径
     * @var string
     */
    private static $logPath = '/Users/liyk/56hello/Mlog/logs/';


    //日志默认通道
    private static $channel = 'default';


    //每天默认日志大小
    private static $maxDefaultSize = 1024 * 512 * 10; //5M

    //登录用户id
    public static $userId;

    //钉钉机器人token
    public static $dingToken;


    /*
     * 初始化具体的日志类
     * 第一个参数是日志目录
     */
    public static function init()
    {
        //获取参数
        $argv = func_get_args();
        //设置目录
        if (!empty($argv[0])) {
            self::$logPath = $argv[0];
        }
        //设置默认通道名
        if (!empty($argv[1])) {
            self::$channel = $argv[1];
        }
        //设置每天默认日志大小
        if (!empty($argv[2])) {
            self::$maxDefaultSize = $argv[2];
        }
        //设置登录用户id
        if (!empty($argv[3])) {
            self::$userId = $argv[3];
        }
        //设置钉钉机器人token
        if (!empty($argv[4])) {
            self::$dingToken = $argv[4];
        }
        LogFormat::_init_basic_fields();
    }


    /**
     * monolog日志
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function record($level, $message, $context, $channel = '', $dingTalk = false)
    {
        $real_level = $level;
        if ($level == 'sql') {
            $level = 'debug';
        }
        $basic_fields = LogFormat::_gen_basic_fields($level);
        $trace = LogFormat::_format_content($basic_fields);

        if (empty($channel)) {
            $channel = self::$channel;
        }
        $logger = self::createLogger($channel, $level, $real_level);
        if (!$logger) {
            return 0;
        }
        $level = Logger::toMonologLevel($level);
        if (!is_int($level)) {
            $level = Logger::INFO;
        }
        $message_arr = [
            'time' => date('Y-m-d H:i:s'),
            'userId' => self::$userId,
            'msg' => $message,
            'trace' => $trace,
        ];
        $mes = '';
        foreach ($message_arr as $key => $value) {
            $mes .= $key . ':' . $value . '###';
        }

        $mes = rtrim($mes, '###');

        //钉钉通知
        if ($dingTalk) {
            $d_con = '日志: ' . $mes . "；调试内容：" . json_encode($context, JSON_UNESCAPED_UNICODE);
            DingLog::sendTxt(self::$dingToken, $d_con);
        }


        return $logger->addRecord($level, $mes, $context);
    }


    /**
     * 创建日志
     * @param $name
     * @return mixed
     */
    private static function createLogger($name, $level, $real_level, $file_format = 'json')
    {
        // 日志文件目录
        $logPath = self::$logPath;
        $now_m = date('Y-m');
        $nowDir = $logPath . '/' . $now_m;
        if (!is_dir($nowDir)) {
            if (!@file_exists($nowDir) && !@mkdir($nowDir, 0777, true)) {
                return false;
            }
        }
        //日志文件名
        $cur_file_name = $real_level . '_' . date('Ymd');
        $file_default = $nowDir . '/' . $cur_file_name . '.log';

        //如果文件不存在,则创建该文件
        if (!@file_exists($file_default) && (!@touch($file_default) || !@chmod($file_default, 0777))) {
            return false;
        } else {
            // 清除缓存
            clearstatcache(true, $file_default);
        }

        //如果默认文件太大了，则按当前时刻生成新的文件
        if (file_exists($file_default) && filesize($file_default) >= self::$maxDefaultSize) {
            $cur_file_name = $real_level . '_' . date('Ymd_H');
            $cur_file = $nowDir . '/' . $cur_file_name . '.log';
            if (!@file_exists($cur_file) && (!@touch($cur_file) || !@chmod($cur_file, 0777))) {
                return false;
            }
        }

        $file_path = $nowDir . '/' . $cur_file_name;

        if (empty(self::$loggers[$file_path])) {
            // 根据业务域名与方法名进行日志名称的确定
            $category = $name ?? self::$channel;

            // 创建日志
            $logger = new Logger($category);

            //日志文件地址
            $file_name = "{$file_path}.log";

            // 日志等级
            $level = Logger::toMonologLevel($level);
            if (!is_int($level)) {
                $level = Logger::INFO;
            }

            $stream_handler = new StreamHandler($file_name, $level); // 过滤级别
            switch (strtolower($file_format)) {
                case "line":
                    // 日志格式
                    $formatter = new LineFormatter("%datetime% %channel%:%level_name% %message% %context% %extra%\n",
                        "Y-m-d H:i:s", false, true);
                    break;
                case "json":
                    $formatter = new JsonFormatter(2);
                    break;
                default:
            }

            $stream_handler->setFormatter($formatter);
            $logger->pushHandler($stream_handler);

            //向日志记录添加唯一标识符
            $uid_obj = new UidProcessor();
            $logger->pushProcessor($uid_obj);

            //将进程id添加到日志记录中
            $pid_obj = new ProcessIdProcessor();
            $logger->pushProcessor($pid_obj);

            //增加当前请求的URI、请求方法和访问IP等信息
            $logger->pushProcessor(new WebProcessor());

            self::$loggers[$file_path] = $logger;
        }
        return self::$loggers[$file_path];
    }


    /**
     * 记录日志信息
     * @access public
     * @param string $level 日志级别
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function log($level, $message, array $context = [], $channel = '', $dingTalk = false)
    {
        self::record($level, $message, $context, $channel, $dingTalk);
    }


    /**
     * 记录错误信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function error($message, array $context = [], $channel = '', $dingTalk = false)
    {
        self::log(__FUNCTION__, $message, $context, $channel, $dingTalk);
    }

    /**
     * 记录warning信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function warning($message, array $context = [], $channel = '', $dingTalk = false)
    {
        self::log(__FUNCTION__, $message, $context, $channel, $dingTalk);
    }

    /**
     * 记录notice信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function notice($message, array $context = [], $channel = '', $dingTalk = false)
    {
        self::log(__FUNCTION__, $message, $context, $channel, $dingTalk);
    }

    /**
     * 记录一般信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function info($message, array $context = [], $channel = '', $dingTalk = false)
    {
        self::log(__FUNCTION__, $message, $context, $channel, $dingTalk);
    }

    /**
     * 记录调试信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function debug($message, array $context = [], $channel = '', $dingTalk = false)
    {
        self::log(__FUNCTION__, $message, $context, $channel, $dingTalk);
    }

    /**
     * 记录sql信息(最终是记录为debug级别)
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function sql($message, array $context = [], $channel = '', $dingTalk = false)
    {
        self::log(__FUNCTION__, $message, $context, $channel, $dingTalk);
    }
}