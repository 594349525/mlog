<?php

namespace Xiangxin\Logger;

class LogFormat
{
    /**
     * 日志换行符
     */
    private static $LOG_NEW_LINE = "\n";

    /**
     * 日志字段格式
     */
    private static $LOG_FIELD_FORMAT = "[%s]";

    /**
     * 日志路径分隔符
     */
    private static $LOG_PATH_SEPARATE = "/";

    /**
     * 日志切分用空格
     */
    private static $LOG_SPACE = "";


    /**
     * 基础日志字段，无特殊说明表示默认所有状态使用
     *
     * app_name，应用名称
     * back_trace，debug_backtrace的返回，默认debug和fatal时使用
     * code_block，代码段，默认debug时使用
     * code_line，打日志的位置
     * exec_time，从开始到当前日志时的执行时间，单位毫秒(ms)
     * log_id，唯一id用于跟踪问题
     * log_level，日志级别的字符串表示
     * memory，内存使用情况，默认debug和fatal时使用
     * method，HTTP请求的方式
     * pid，PHP脚本进程id
     * req_ip，用户发请求时的ip
     * req_vars，$_REQUEST的内容，默认debug和fatal时使用
     * server_host，接收请求的服务器名
     * server_ip，接收请求的服务器ip
     * sessions，$_SESSION的内容，默认debug和fatal时使用
     * step_time，上次日志到这次日志的执行时间，单位毫秒(ms)
     * timestamp，当前日志的时间戳，带时区和微秒
     * uri，请求的REQUEST_URI
     */
    const
        BF_APP_NAME = 'app_name',
        BF_BACK_TRACE = 'back_trace',
        BF_CODE_BLOCK = 'code_block',
        BF_CODE_LINE = 'code_line',
        BF_EXCEPTION = 'exception',
        BF_EXEC_TIME = 'exec_time',
        BF_LOG_ID = 'log_id',
        BF_LOG_LEVEL = 'log_level',
        BF_MEMORY = 'memory',
        BF_METHOD = 'method',
        BF_PROCESS_ID = 'pid',
        BF_REQ_IP = 'req_ip',
        BF_REQ_VARS = 'req_vars',
        BF_SERVER_HOST = 'server_host',
        BF_SERVER_IP = 'server_ip',
        BF_SESSION = 'sessions',
        BF_STEP_TIME = 'step_time',
        BF_TIMESTAMP = 'timestamp',
        BF_APP_ID = 'app_id',
        BF_URI = 'uri';


    /**
     * 日志级别
     */
    const
        LL_FATAL = 'error', //error is a alias of fatal
        LL_WARNING = 'warning',
        LL_NOTICE = 'notice',
        LL_INFO = 'info', //info is a alias of trace
        LL_DEBUG = 'debug';

    /**
     * 各个日志级别的日志字段输出序列
     */
    private static $_output_sequences = array(
        self::LL_FATAL => array(
            self::BF_APP_ID,
            self::BF_MEMORY,
            self::BF_REQ_VARS,
            self::BF_SESSION,
            self::BF_BACK_TRACE,
            self::BF_CODE_BLOCK,
            self::BF_CODE_LINE
        ),
        self::LL_WARNING => array(
            self::BF_REQ_VARS,
            self::BF_BACK_TRACE,
            self::BF_CODE_LINE
        ),
        self::LL_NOTICE => array(
            self::BF_BACK_TRACE,
            self::BF_CODE_LINE
        ),
        self::LL_INFO => array(
            self::BF_APP_ID,
            self::BF_CODE_LINE
        ),
        self::LL_DEBUG => array(),
    );

    /**
     * 基础日志字段的获取方式
     *
     * 第一个字段表示callable的callback
     * 第二个字段表示是否需要静态化，如果为true表示第一次初始化后，以结果值的形式静态化，如果为false表示每次输出日志都会按照callback的返回动态获取
     * 第三个字段表示传入callback的参数
     */
    public static $_basic_fields = array(
        self::BF_APP_ID => array(array('Xiangxin\Logger\MLog', 'get_app_id'), false, array()),
        self::BF_BACK_TRACE => array(array('Xiangxin\Logger\MLog', '_bf_back_trace'), false, array()),
        self::BF_CODE_BLOCK => array(array('Xiangxin\Logger\MLog', '_bf_code_block'), false, array()),
        self::BF_CODE_LINE => array(array('Xiangxin\Logger\MLog', '_bf_code_line'), false, array()),
        self::BF_METHOD => array(array('Xiangxin\Logger\MLog', '_bf_method'), true, array()),
        self::BF_PROCESS_ID => array(array('Xiangxin\Logger\MLog', '_bf_pid'), true, array()),
        self::BF_REQ_IP => array(array('Xiangxin\Logger\MLog', '_bf_req_ip'), true, array()),
        self::BF_REQ_VARS => array(array('Xiangxin\Logger\MLog', '_bf_req_vars'), true, array()),
        self::BF_SERVER_HOST => array(array('Xiangxin\Logger\MLog', '_bf_server_host'), true, array()),
        self::BF_SERVER_IP => array(array('Xiangxin\Logger\MLog', '_bf_server_ip'), true, array()),
        self::BF_SESSION => array(array('Xiangxin\Logger\MLog', '_bf_sessions'), true, array()),
        self::BF_STEP_TIME => array(array('Xiangxin\Logger\MLog', '_bf_step_time'), false, array()),
        self::BF_TIMESTAMP => array(array('Xiangxin\Logger\MLog', '_bf_timestamp'), false, array()),
        self::BF_URI => array(array('Xiangxin\Logger\MLog', '_bf_uri'), true, array()),
    );

    /**
     * 当前记录日志的时间点，带微秒的float
     */
    private
    static $_timestamp_last = 0;


    /**
     * 上一次记录日志的时间点，带微秒的float
     */
    private
    static $_timestamp_now = 0;


    /**
     * 如果有系统方法就使用，没有就使用系统命令
     *
     * @return integer
     */
    private static function _bf_memory()
    {
        if (function_exists('memory_get_usage')) {
            $memory = memory_get_usage();
        } else {
            $output = array();
            if (strncmp(PHP_OS, 'WIN', 3) === 0) {
                exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output);
                $memory = isset($output[5]) ? preg_replace('/[\D]/', '', $output[5]) * 1024 : 0;
            } else {
                $pid = getmypid();
                exec("ps -eo%mem,rss,pid | grep $pid", $output);
                $output = explode("  ", $output[0]);
                $memory = isset($output[1]) ? $output[1] * 1024 : 0;
            }
        }
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($memory / pow(1024, ($i = floor(log($memory, 1024)))), 2) . $unit[$i];
    }

    /**
     * http请求的方法
     *
     * @return string
     */
    private static function _bf_method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "";
    }

    /**
     * 进程id
     *
     * @return integer
     */
    private static function _bf_pid()
    {
        return intval(getmypid());
    }

    /**
     * 仅供参考，未必准确
     *
     * @return string
     */
    private static function _bf_req_ip()
    {
        $ips = array();
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ips[] = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_merge($ips, explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ips[] = $_SERVER['REMOTE_ADDR'];
        }
        foreach ($ips as $ip) {
            if (preg_match('/^[0-2]{0,1}[0-9]{0,1}[0-9]{1}\.[0-2]{0,1}[0-9]{0,1}[0-9]{1}\.[0-2]{0,1}[0-9]{0,1}[0-9]{1}\.[0-2]{0,1}[0-9]{0,1}[0-9]{1}$/',
                $ip)) {
                return $ip;
            }
        }
        return 'unknown';
    }


    /**
     * 服务器名字
     *
     * @return string
     */
    private static function _bf_server_host()
    {
        return sprintf("%s", isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "");
    }

    /**
     * 服务器ip
     *
     * @return string
     */
    private static function _bf_server_ip()
    {
        return sprintf("%s", isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "");
    }

    /**
     * session信息
     *
     * @return array
     */
    private static function _bf_sessions()
    {
        return empty($_SESSION) ? "" : @json_encode($_SESSION);
    }

    /**
     * 从上次打日志到本次日志的执行时间
     *
     * @return string
     */
    private static function _bf_step_time()
    {
        $step_time = sprintf("%s", 1000 * (floatval(self::$_timestamp_now) - floatval(self::$_timestamp_last)));
        self::$_timestamp_last = self::$_timestamp_now;
        return $step_time;
    }

    /**
     * 格式为yyyy-mm-dd hh:mm:ss.微秒 时区
     *
     * @return string
     */
    private static function _bf_timestamp()
    {
        $timestamp = self::_get_timestamp();
        self::$_timestamp_now = implode('.', $timestamp);
        return date("Y-m-d H:i:s", $timestamp[0]) . "." . str_pad($timestamp[1], 6, "0") . " " . date('O',
                $timestamp[0]);
    }

    /**
     * 获取时间戳，带微秒
     *
     * @return array
     */
    private static function _get_timestamp()
    {
        list($usec, $sec) = explode(" ", microtime());
        $usec = substr($usec, 2, 6);
        return array($sec, $usec);
    }

    /**
     * 请求的uri
     *
     * @return string
     */
    private static function _bf_uri()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "";
    }


    /**
     * http请求数组内容
     *
     * @return array
     */
    private static function _bf_req_vars()
    {
        return empty($_REQUEST) ? "" : @json_encode($_REQUEST);
    }


    /**
     * 打日志的前后3行代码
     *
     * @return string
     */
    private static function _bf_code_block()
    {
        $trace = array_slice(debug_backtrace(), 5);
        $code_block = array();
        if (!isset($trace[0]['file']) || !isset($trace[0]['line']) || !is_readable(@$trace[0]['file']) || !$fp = fopen($trace[0]['file'],
                'r')) {
            return $code_block;
        }
        $bline = intval(@$trace[0]['line']) - 3;
        $eline = intval(@$trace[0]['line']) + 3;
        $line = 0;
        while (($row = fgets($fp))) {
            if (++$line > $eline) {
                break;
            }
            if ($line < $bline) {
                continue;
            }
            if ($line == intval(@$trace[0]['line'])) {
                $line = ">>>$line";
            }
            $code_block[] = "$line: " . rtrim($row);
        }
        fclose($fp);
        return "\n" . implode("\n", $code_block);
    }

    /**
     * 初始化基本日志字段
     */
    public static function _init_basic_fields()
    {
        foreach (self::$_basic_fields as $field => $v) {
            if (!is_array($v)) {
                continue;
            }
            $callable_name = array_shift($v);
            if (!is_callable($callable_name)) {
                continue;
            }
            $need_static = array_shift($v);
            if (!$need_static) {
                continue;
            }
            $args = array_shift($v);
            self::$_basic_fields[$field] = @call_user_func_array($callable_name, $args);
        }
    }


    /**
     * 打日志的位置，格式是文件名:行数
     *
     * @return string
     */
    private static function _bf_code_line()
    {
        $trace = array_slice(debug_backtrace(), 5);
        foreach ($trace as $v) {
            if (isset($v['file']) && isset($v['line'])) {
                return sprintf("%s:%s", $v['file'], $v['line']);
            }
        }
        return "";
    }

    /**
     * 获取用户id
     *
     * @return string
     */
    public static function get_app_id()
    {
        return self::$userId;
    }

    /**
     * 将前三个trace信息弹出，日志记录的调用信息，无用
     *
     * @return array
     */
    private static function _bf_back_trace()
    {
        if (!isset(self::$_basic_fields[self::BF_EXCEPTION])) {
            $traces = array_slice(debug_backtrace(), 6);
        } else {
            if (is_array(self::$_basic_fields[self::BF_EXCEPTION])) {
                $traces = self::$_basic_fields[self::BF_EXCEPTION];
            } else {
                return self::$_basic_fields[self::BF_EXCEPTION];
            }
        }
        $content = "\n";
        foreach ($traces as $k => $trace) {
            if (is_array($trace)) {
                $args = array();
                if (isset($trace['args'])) {
                    foreach ($trace['args'] as $arg) {
                        if (is_bool($arg)) {
                            $args[] = $arg ? "true" : "false";
                        } else {
                            if (is_numeric($arg)) {
                                $args[] = $arg;
                            } else {
                                if (is_string($arg)) {
                                    $args[] = '"' . str_replace(array("\n", "\t"), array("", " "), trim($arg)) . '"';
                                } else {
                                    if (is_scalar($arg)) {
                                        $args[] = $arg;
                                    } else {
                                        $args[] = json_encode($arg);
                                    }
                                }
                            }
                        }
                    }
                }
                $content .= "#$k "
                    . (isset($trace['file']) ? $trace['file'] : 'nofile')
                    . '(' . (isset($trace['line']) ? $trace['line'] : 'noline') . '): '
                    . (isset($trace['class']) ? $trace['class'] : '')
                    . (isset($trace['type']) ? $trace['type'] : '')
                    . (isset($trace['function']) ? $trace['function'] : '')
                    . '(' . implode(', ', $args) . ')'
                    . "\n";
            }
        }
        return $content;
    }


    /**
     * 根据日志级别及该日志级别对应的输出序列生成对应顺序的基本日志信息
     *
     * @return mixed 当不存在对应日志级别输出序列或当前日志级别大于输出控制日志级别时返回false，否则返回基本日志信息数组
     */
    public static function _gen_basic_fields($log_level)
    {
        if (!isset(self::$_output_sequences[$log_level])) {
            return false;
        }
        $basic_fields = array();
        foreach (self::$_output_sequences[$log_level] as $field) {
            if (!isset(self::$_basic_fields[$field])) {
                continue;
            }
            if (is_scalar(self::$_basic_fields[$field])) {
                $basic_fields[$field] = self::$_basic_fields[$field];
            }
            if (is_array(self::$_basic_fields[$field])) {
                $v = self::$_basic_fields[$field];
                $callable_name = array_shift($v);
                if (!is_callable($callable_name)) {
                    continue;
                }
                $need_static = array_shift($v);
                $args = array_shift($v);
                $basic_fields[$field] = @call_user_func_array($callable_name, $args);
            }
        }
        return $basic_fields;
    }

    /**
     * 格式化日志内容
     *
     * @param array $basic_fields
     * @param string $format
     * @param array $args
     * @return string
     */
    public static function _format_content($basic_fields, $format = '', $args = [])
    {
        $content = "";
        $back_trace = "";
        foreach ($basic_fields as $field => $v) {
            if ($field == self::BF_EXCEPTION) {
                continue;
            }
            if ($field != self::BF_BACK_TRACE) {
                $content .= self::_format_field($v);
            } else {
                $back_trace = $v;
            }
        }
        empty($back_trace) ? $back_trace = self::$LOG_NEW_LINE : null;
        $format = str_replace('%', '', $format);
        return @($content .= " " . str_replace(array("\n", "\t"), array("", " "),
                trim(@vsprintf($format, $args))) . $back_trace . self::$LOG_NEW_LINE);
    }


    /**
     * 格式化日志字段，非标量用json_encode处理
     *
     * @param mixed $field
     * @return string
     */
    public static function _format_field($field)
    {
        $value = "";
        if (is_scalar($field)) {
            $value = $field;
        } else {
            false !== ($value = @json_encode($field)) ? null : $value = "Error:" . @json_last_error() . ", " . @json_last_error_msg();
        }
        return sprintf(self::$LOG_FIELD_FORMAT, self::$LOG_SPACE . $value . self::$LOG_SPACE);
    }
}