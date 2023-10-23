<?php


namespace Smbear\Wechat\Services;


class ConfigService
{
    protected $mchid;
    protected $appid;
    protected $key;
    protected $appsceret;
    protected $orderInfo = [];

    /**
     * @Author: dori
     * @Date: 2022/9/9
     * @Descrip:设置微信参数
     * @param string $mchid
     * @param string $appid
     * @param string $key
     * @param string $appsceret
     * @return ConfigService
     */
    public function setConfig(string $mchid,string $appid,string $key,string $appsceret=''): ConfigService
    {
        $this->mchid = $mchid;
        $this->appid = $appid;
        $this->key = $key;
        $this->appsceret = $appsceret;
        return $this;
    }

    public function setOrderInfo(array $orderInfo)
    {
        $this->orderInfo = $orderInfo;
        return $this;
    }
}
