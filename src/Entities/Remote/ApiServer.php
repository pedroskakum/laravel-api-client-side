<?php
/**
 * Created by PhpStorm.
 * User: pedroskakum
 * Date: 03/03/2017
 * Time: 17:31
 */

namespace pedroskakum\ApiClientLayer\Entities\Remote;


class ApiServer
{
    protected $appKey;
    protected $appSecret;
    protected $endpointUrl;

    public function __construct($appKey, $appSecret, $endpointUrl)
    {
        $this->appKey =$appKey;
        $this->appSecret = $appSecret;
        $this->endpointUrl = $endpointUrl;
    }

    /**
     * @return mixed
     */
    public function getAppKey()
    {
        return $this->appKey;
    }

    /**
     * @return mixed
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * @return mixed
     */
    public function getEndpointUrl()
    {
        return $this->endpointUrl;
    }

}
