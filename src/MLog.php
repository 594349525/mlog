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
    private static $userId;


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
    }

    /**
     * monolog日志
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function record($level, $message, $context, $channel = '')
    {
        if (empty($channel)) {
            $channel = self::$channel;
        }
        $logger = self::createLogger($channel, $level);
        $level = Logger::toMonologLevel($level);
        if (!is_int($level)) {
            $level = Logger::INFO;
        }
        // $backtrace数组第$idx元素是当前行，第$idx+1元素表示上一层，另外function、class需再往上取一个层次
        // PHP7 不会包含'call_user_func'与'call_user_func_array'，需减少一层
        if (version_compare(PCRE_VERSION, '7.0.0', '>=')) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $idx = 0;
        } else {
            $backtrace = debug_backtrace();
            $idx = 1;
        }
        $trace = basename($backtrace[$idx]['file']) . ":" . $backtrace[$idx]['line'];
        if (!empty($backtrace[$idx + 1]['function'])) {
            $trace .= '##';
            $trace .= $backtrace[$idx + 1]['function'];
        }

        $message = sprintf(date('Y-m-d H:i:s') . ' -- userId:' . self::$userId . ' -- %s -- %s', $message, $trace);

        return $logger->addRecord($level, $message, $context);
    }

    /**
     * 创建日志
     * @param $name
     * @return mixed
     */
    private static function createLogger($name, $level, $file_format = 'json')
    {
        // 日志文件目录
        $logPath = self::$logPath;
        $now_m = date('Y-m');
        $nowDir = $logPath . '/' . $now_m;
        if (!is_dir($nowDir)) {
            mkdir($nowDir, 0777, true);
        }
        //日志文件名
        $cur_file_name = $level . '_' . date('Ymd');
        $file_default = $nowDir . '/' . $cur_file_name . '.log';
        if (!file_exists($file_default)) { //如果文件不存在,则创建该文件
            touch($file_default);
        } else {
            // 清除缓存
            clearstatcache(true, $file_default);
        }
        //如果默认文件太大了，则按当前时刻生成新的文件
        if (file_exists($file_default) && filesize($file_default) >= self::$maxDefaultSize) {
            $cur_file_name = $level . '_' . date('Ymd_H');
            $cur_file = $nowDir . '/' . $cur_file_name . '.log';
            if (!file_exists($cur_file)) { //如果文件不存在,则创建该文件
                touch($cur_file);
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

            //增加当前脚本的文件名和类名等信息
            $logger->pushProcessor(new IntrospectionProcessor());

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
    public static function log($level, $message, array $context = [], $channel = '')
    {
        if ($level == 'sql') {
            $level = 'debug';
        }
        self::record($level, $message, $context, $channel);
    }

    /**
     * 记录emergency信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function emergency($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录警报信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function alert($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录紧急情况
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function critical($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录错误信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function error($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录warning信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function warning($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录notice信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function notice($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录一般信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function info($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录调试信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function debug($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * 记录sql信息
     * @access public
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function sql($message, array $context = [], $channel = '')
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }
}