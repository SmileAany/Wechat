<?php
namespace wechat\src\Services;

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
            'spbill_create_ip' => '127.0.0.1',
            'total_fee' => floatval($this->orderInfo['total']) * 100,
            'trade_type' => 'NATIVE',
        );
        $unified['sign'] = self::getSign($unified, $this->apiKey);
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
        $arr['paySign'] = self::getSign($arr, $this->apiKey);
        return $arr;
    }
}
