<?php


namespace Dori\Wechat\Services;


class ConfigService
{
    protected $mchid;
    protected $appid;
    protected $key;
    protected $orderInfo = [];
    /**
    * @Author: dori
    * @Date: 2022/9/9
    * @Descrip:设置微信参数
    * @Return ConfigService
    */
    public function setConfig(string $mchid,string $appid,string $key): ConfigService
    {
        $this->mchid = $mchid;
        $this->appid = $appid;
        $this->key = $key;
        return $this;
    }

    public function setOrderInfo(array $orderInfo)
    {
        $this->orderInfo = $orderInfo;
        return $this;
    }
}
