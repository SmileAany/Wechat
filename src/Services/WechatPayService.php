<?php
namespace Dori\Wechat\Services;

class WechatPayService extends BaseService
{
    /**
     * @Author: dori
     * @Date: 2022/9/8
     * @Descrip:native支付
     * @return array
     */
    public function createJsBizPackage(): array
    {
        $unified = array(
            'appid' => $this->appid,
            'attach' => 'pay',
            'body' => $this->orderInfo['body'],
            'mch_id' => $this->mchid,
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $this->orderInfo['notify_url'],
            'out_trade_no' => $this->orderInfo['orders_number'],
            'spbill_create_ip' => getClientIp(),
            'total_fee' => floatval($this->orderInfo['total']) * 100,
            'trade_type' => 'NATIVE',
        );
        $unified['sign'] = self::getSign($unified, $this->key);
        $responseXml = self::curlPost('https://api.mch.weixin.qq.com/pay/unifiedorder', self::arrayToXml($unified));
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            die('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            die($unifiedOrder->return_msg);
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            die($unifiedOrder->err_code);
        }
        $codeUrl = (array)($unifiedOrder->code_url);
        if(!$codeUrl[0]) exit('get code_url error');
        $arr = array(
            "appId" => $this->appid,
            "timeStamp" => time(),
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
            "code_url" => $codeUrl[0],
        );
        $arr['paySign'] = self::getSign($arr, $this->key);
        return $arr;
    }

    /**
    * @Author: dori
    * @Date: 2022/9/13
    * @Descrip:APP支付
    * @Return array
    */
    private function wechatPayApp()
    {
        $userip = getClientIp();
        $notify_url = $this->orderInfo['notify_url'];//回调地址
        $httpsArr = [];
        $httpsArr['appid'] =$this->appid;//微信APPID
        $httpsArr['body'] = $this->orderInfo['body'];
        $httpsArr['mch_id'] = $this ->mchid;
        $httpsArr['nonce_str'] = MD5($this->orderInfo['orders_number']);//随机字符串
        $httpsArr['notify_url'] = $notify_url;//回调地址
        $httpsArr['out_trade_no'] = $this->orderInfo['orders_number'];//订单号
        //场景信息 必要参数
        $httpsArr['scene_info'] = '{"h5_info":{"type":"Wap","wap_url":'.$notify_url.',"wap_name":"APP支付"}}';
        $httpsArr['spbill_create_ip'] =  $userip;//回调地址
        $httpsArr['total_fee'] = $this->orderInfo['total'];
        $httpsArr['trade_type'] = 'APP';//交易类型 具体看API 里面有详细介绍

        $signA = '';
        foreach ($httpsArr as $key => $val) {
            $signA .= $key.'='.$val.'&';
        }
        $stringSignTemp = $signA . "key=" . $this->key; //注：key为商户平台设置的密钥key
        $sign = MD5($stringSignTemp); //注：MD5签名方式
        $sign = strtoupper($sign);
        $post_data = "<xml>
                            <appid>".$httpsArr['appid']."</appid>
                            <body>".$httpsArr['body']."</body>
                            <mch_id>".$httpsArr['mch_id']."</mch_id>
                            <nonce_str>".$httpsArr['nonce_str']."</nonce_str>
                            <notify_url>".$httpsArr['notify_url']."</notify_url>
                            <out_trade_no>".$httpsArr['out_trade_no']."</out_trade_no>
                            <scene_info>".$httpsArr['scene_info']."</scene_info>
                            <spbill_create_ip>".$httpsArr['spbill_create_ip']."</spbill_create_ip>
                            <total_fee>".$httpsArr['total_fee']."</total_fee>
                            <trade_type>".$httpsArr['trade_type']."</trade_type>
                            <sign>$sign</sign>
                        </xml>";//拼接成XML 格式
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";//微信传参地址
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $res = curl_exec($ch);
        curl_close($ch);
        $dataxml = $res; //后台POST微信传参地址  同时取得微信返回的参数
        //将微信返回的XML 转换成数组
        $objectxml = (array)simplexml_load_string($dataxml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!array_key_exists("appid", $objectxml)
            || !array_key_exists("prepay_id", $objectxml)
            || $objectxml['prepay_id'] == "") {
            //数据没有获取到下单失败
            return array_merge($objectxml);
        }

        $result = [];
        $result['appid'] = $objectxml['appid'];
        $result['partnerid'] = $objectxml['mch_id'];
        $result['prepayid'] = $objectxml['prepay_id'];
        $result['noncestr'] = $objectxml['nonce_str'];// md5(uniqid(microtime(true), true));
        $result['timestamp'] = time().'';//时间戳属性
        $result['package'] = 'Sign=WXPay';
        //$result['signType'] = 'MD5';
        $sign = $this->getSign($result,$this->key);
        $result['sign'] = $sign;

        return $result;
    }
}
