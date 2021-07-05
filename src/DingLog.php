<?php

namespace Xiangxin\Logger;

/**
 * 钉钉相关接口和curl接口
 */
class DingLog
{
    const HOST = "https://oapi.dingtalk.com";

    public static function sendTxt($token, $content, $isAtAll = true)
    {
        $url = self::HOST . "/robot/send?access_token=$token";
        $data = array(
            'msgtype' => 'text',
            'text' => array('content' => $content),
            'at' => array(
                'atMobiles' => array(),
                'isAtAll' => $isAtAll,
            ),
        );
        $ret = self::reqDing($url, $data);

        return $ret;
    }

    /**
     * 发起http请求，post为true用POST方式，false为GET方式请求
     */
    public static function reqDing($url, $postData, $post = true)
    {
        if (is_array($postData)) {
            $postData = json_encode($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $data = empty($data) ? array() : json_decode($data, true);

        return $data;
    }

    public static function getHoliday1($date = '')
    {
        $holidayUrl = 'http://api.k780.com/?app=life.workday&date=%s&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json';

        if ($date === '') {
            $date = date('Ymd', time());
        }
        $url = sprintf($holidayUrl, $date);
        $judgeHolidayRet = self::reqDing($url, []);
        $worknm = $judgeHolidayRet['result']['worknm'];
        if (!isset($judgeHolidayRet['result']['worknm'])) {
            return false;
        }
        if ($worknm == '工作日') {
            return 'yes';
        }
        return 'no';
    }

    public static function getHoliday2($date = '')
    {
        $holidayUrl = 'http://timor.tech/api/holiday/info/%s';

        if ($date === '') {
            $date = date('Y-m-d', time());
        }
        $url = sprintf($holidayUrl, $date);
        $judgeHolidayRet = self::reqDing($url, [], false);
        $worknm = $judgeHolidayRet['type']['type'];
        if (!isset($judgeHolidayRet['type']['type'])) {
            return false;
        }
        if (($worknm == 0) || ($worknm == 3)) {
            return 'yes';
        }
        return 'no';
    }

    /**
     * 判断当天是否为工作日
     */
    public static function judgeWorkDay()
    {
        $ret1 = self::getHoliday1();
        if ($ret1 == 'yes') {
            return 'yes';
        } elseif ($ret1 == 'no') {
            return 'no';
        }
        return self::getHoliday2();
    }
}