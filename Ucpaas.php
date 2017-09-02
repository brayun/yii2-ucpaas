<?php
/*
 *          ┌─┐       ┌─┐
 *       ┌──┘ ┴───────┘ ┴──┐
 *       │                 │
 *       │       ───       │
 *       │  ─┬┘       └┬─  │
 *       │                 │
 *       │       ─┴─       │
 *       └───┐         ┌───┘
 *           │         └──────────────┐
 *           │                        ├─┐
 *           │                        ┌─┘
 *           │                        │
 *           └─┐  ┐  ┌───────┬──┐  ┌──┘
 *             │ ─┤ ─┤       │ ─┤ ─┤
 *             └──┴──┘       └──┴──┘
 *        @Author Ethan <ethan@brayun.com>
 */

namespace brayun\ucpaas;


use GuzzleHttp\Client;
use yii\base\Component;

class Ucpaas extends Component
{

    public $accountSid;

    public $authToken;

    public $appId;

    protected $client;

    protected $base_uri = 'https://api.ucpaas.com';

    protected $version = '2014-06-30';

    protected $uri;

    public function init()
    {
        $this->uri = str_replace(['{version}', '{accountSid}'], [$this->version, $this->accountSid], '/{version}/Accounts/{accountSid}/safetyCalls/');
        $this->client = new Client([
            'base_uri' => $this->base_uri
        ]);
    }

    /**
     * 绑定AXB
     * @param string $caller 主叫号码  必须为11位手机号，号码前加0086如008613631686024
     * @param string $callee 被叫号码  必须为11位手机号，号码前加0086如008615031686024
     * @param string $dstVirtualNum 分配的直呼虚拟中间保护号码
     * @param string $bindId 绑定id，客户方平台保证唯一
     * @param string $callerRingName 主叫呼入时播放IVR语音文件名
     * @param string $calleeRingName 被叫呼入时播放IVR语音文件名
     * @param integer $maxAge 主被叫+虚拟保护号码允许合作方最大cache存储时间(单位秒) 默认绑定为1800/S 最大无上限
     * @param string $requestId 字符串最大长度不超过128字节，该requestId在后面话单和录音URL推送中原样带回
     * @param string $statusUrl 状态回调通知地址，正式环境可以配置默认推送地址
     * @param string $hangupUrl 话单推送地址，不填推到默认协商地址
     * @param string $recordUrl 录单URL回调通知地址，不填推到默认协商地址
     * @param int $record 字符串最大长度不超过128字节，该requestId在后面话单和录音URL推送中原样带回
     * @param string $cityId 城市区号，dstVirtualNum号码归属 城市Id格式为 0086+去零区号比如北京0086755
     * @return mixed
     */
    public function bindVirPhone($caller, $callee, $dstVirtualNum, $bindId, $record = 1, $cityId = '0086021', $maxAge = 300, $callerRingName = '', $calleeRingName = '', $requestId = '', $statusUrl = '', $hangupUrl = '', $recordUrl = '')
    {
        $res = $this->request('applyNumber', 'POST', [
            'caller' => '0086'.$caller,
            'callee' => '0086'.$callee,
            'callerRingName' => $callerRingName,
            'calleeRingName' => $calleeRingName,
            'dstVirtualNum' => '0086'.$dstVirtualNum,
            'bindId' => $bindId,
            'maxAge' => $maxAge,
            'cityId' => $cityId,
            'requestId' => $requestId,
            'record' => $record,
            'statusUrl' => $statusUrl,
            'hangupUrl' => $hangupUrl,
            'recordUrl' => $recordUrl
        ]);
        $response = (array)json_decode($res);
        return $response;
    }

    /**
     * 解除绑定
     * @param $bindId
     * @param $cityId
     * @return mixed
     */
    public function unbindVirPhone($bindId, $cityId)
    {
        $res = $this->request('freeNumber', 'POST', [
            'bindId' => $bindId,
            'cityId' => $cityId,
        ]);
        $response = (array)json_decode($res);
        return $response;
    }

    /**
     * 实时绑定并发数查询接口
     * @param string $cityId
     * @return mixed
     */
    public function getConcurrent($cityId = '0086021')
    {
        $res = $this->request('freeNumber', 'POST', [
            'cityId' => $cityId,
        ]);
        $response = (array)json_decode($res);
        return $response;
    }

    /**
     * @param $uri
     * @param string $method
     * @param array $options
     * @return string
     */
    protected function request($uri, $method = 'GET', $options = [])
    {
        $options = [
            'query' => [
                'sig' => strtoupper(md5($this->accountSid.$this->authToken.date('YmdHis')))
            ],
            'headers' => [
                'Accept'     => 'application/json',
                'Content-Type'  => 'application/json;charset=utf-8',
                'Authorization' => base64_encode($this->accountSid.':'.date('YmdHis'))
            ],
            'body' => json_encode(array_merge([
                'appId' => $this->appId,
            ], $options))
        ];
        $response = $this->client->request($method, $this->uri.$uri, $options);
        return $response->getBody()->getContents();
    }

}