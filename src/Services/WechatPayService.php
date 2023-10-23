<?php
namespace Smbear\Wechat\Services;

use Exception;

class WechatPayService extends BaseService
{
    protected array $unified = [];

    /**
     * @Author: dori
     * @Date: 2022/9/8
     * @Descrip:native支付
     * @return array
     * @throws Exception
     */
    public function createJsBizPackage(): array
    {
        $this->unified = [
            'appid' => $this->appid,
            'body' => $this->orderInfo['body'],
            'mch_id' => $this->mchid,
            'nonce_str' => MD5($this->orderInfo['orders_number']),
            'notify_url' => $this->orderInfo['notify_url'],
            'out_trade_no' => $this->orderInfo['orders_number'],
            'spbill_create_ip' => getClientIp(),
            'total_fee' => floatval($this->orderInfo['total']) * 100,
        ];
        $this->unified['attach'] = 'pay';
        $this->unified['trade_type'] = 'NATIVE';
        $this->unified['sign'] = $this->getSign($this->unified);
        $responseXml = self::curlPost(
            'https://api.mch.weixin.qq.com/pay/unifiedorder',
            self::arrayToXml($this->unified)
        );
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            throw new Exception('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            throw new Exception($unifiedOrder->return_msg);
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            throw new Exception($unifiedOrder->err_code_des);
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
        $arr['paySign'] = $this->getSign($arr);
        return $arr;
    }

    /**
     * @Author: dori
     * @Date: 2022/9/13
     * @Descrip:APP支付
     * @Return array
     * @throws Exception
     */
    public function wechatPayApp()
    {
        $this->unified = [
            'appid' => $this->appid,
            'body' => $this->orderInfo['body'],
            'mch_id' => $this->mchid,
            'nonce_str' => MD5($this->orderInfo['orders_number']),
            'notify_url' => $this->orderInfo['notify_url'],
            'out_trade_no' => $this->orderInfo['orders_number'],
            'spbill_create_ip' => getClientIp(),
            'total_fee' => floatval($this->orderInfo['total']) * 100,
        ];
        $notify_url = $this->orderInfo['notify_url'];//回调地址
        $this->unified['scene_info'] = '{"h5_info":{"type":"Wap","wap_url":'.$notify_url.',"wap_name":"APP支付"}}';
        $this->unified['trade_type'] = 'APP';//交易类型 具体看API 里面有详细介绍

        $this->unified['sign'] = $this->getSign($this->unified);
        $responseXml = self::curlPost(
            'https://api.mch.weixin.qq.com/pay/unifiedorder',
            self::arrayToXml($this->unified)
        );
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        //将微信返回的XML 转换成数组
        $objectxml = (array)simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!array_key_exists("appid", $objectxml)
            || !array_key_exists("prepay_id", $objectxml)
            || $objectxml['prepay_id'] == "") {
            //数据没有获取到下单失败
            throw new Exception('error');
        }

        $result = [];
        $result['appid'] = $objectxml['appid'];
        $result['partnerid'] = $objectxml['mch_id'];
        $result['prepayid'] = $objectxml['prepay_id'];
        $result['noncestr'] = $objectxml['nonce_str'];// md5(uniqid(microtime(true), true));
        $result['timestamp'] = time().'';//时间戳属性
        $result['package'] = 'Sign=WXPay';
        //$result['signType'] = 'MD5';
        $sign = $this->getSign($result);
        $result['sign'] = $sign;

        return $result;
    }

    /**
     * @Author: dori
     * @Date: 2022/9/15
     * @Descrip:jsapi支付
     * @Return array
     * @throws Exception
     */
    public function jsApiPay($code,$baseUrl)
    {
        $this->unified = [
            'appid' => $this->appid,
            'body' => $this->orderInfo['body'],
            'mch_id' => $this->mchid,
            'nonce_str' => MD5($this->orderInfo['orders_number']),
            'notify_url' => $this->orderInfo['notify_url'],
            'out_trade_no' => $this->orderInfo['orders_number'],
            'spbill_create_ip' => getClientIp(),
            'total_fee' => floatval($this->orderInfo['total']) * 100,
        ];
        $this->unified['openid'] = $this->getOpenid($code,$baseUrl);
        //场景信息 必要参数
        $this->unified['scene_info'] = '{"h5_info":{"type":"Wap","wap_url":'.$this->orderInfo['notify_url'].',"wap_name":"H5JSAPI支付"}}';
        $this->unified['trade_type'] = 'JSAPI';//交易类型 具体看API 里面有详细介绍
        //生成sign的数据
        $this->unified['sign'] = $this->getSign($this->unified);
        $responseXml = self::curlPost(
            'https://api.mch.weixin.qq.com/pay/unifiedorder',
            self::arrayToXml($this->unified)
        );
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        //将微信返回的XML 转换成数组
        $objectxml = (array)simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!array_key_exists("appid", $objectxml)
            || !array_key_exists("prepay_id", $objectxml)
            || $objectxml['prepay_id'] == "") {
            //数据没有获取到下单失败
            throw new Exception('error');
        }

        $result = [];
        $result['appId'] = $objectxml['appid'];
        $result['timeStamp'] = time();//时间戳属性
        //生成32位唯一字符串
        $result['nonceStr'] = md5(uniqid(microtime(true), true));
        $result['package'] = 'prepay_id='.$objectxml['prepay_id'];//扩展字段字符串
        $result['signType'] = 'MD5';
        $sign = $this->getSign($result);
        $result['paySign'] = $sign;
        return $result;
    }
    /**
    * @Author: dori
    * @Date: 2022/9/15
    * @Descrip:h5支付
    * @Return array
    */
    public function h5Pay(): array
    {
        $this->unified = [
            'appid' => $this->appid,
            'body' => $this->orderInfo['body'],
            'mch_id' => $this->mchid,
            'nonce_str' => MD5($this->orderInfo['orders_number']),
            'notify_url' => $this->orderInfo['notify_url'],
            'out_trade_no' => $this->orderInfo['orders_number'],
            'spbill_create_ip' => getClientIp(),
            'total_fee' => floatval($this->orderInfo['total']) * 100,
        ];
        //场景信息 必要参数
        $this->unified['scene_info'] = '{"h5_info":{"type":"Wap","wap_url":'.$this->orderInfo['notify_url'].',"wap_name":"H5支付"}}';
        $this->unified['trade_type'] = 'MWEB';//交易类型 具体看API 里面有详细介绍
        $signA = '';
        $this->unified['sign'] = $this->getSign($this->unified);
        $responseXml = self::curlPost(
            'https://api.mch.weixin.qq.com/pay/unifiedorder',
            self::arrayToXml($this->unified)
        );
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        //将微信返回的XML 转换成数组
        $objectxml = (array)simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $objectxml;
    }
}
