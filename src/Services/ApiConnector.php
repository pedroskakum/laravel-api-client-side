<?php

namespace pedroskakum\ApiClientLayer\Services;

use pedroskakum\ApiClientLayer\Entities\Remote\ApiServer;
use pedroskakum\ApiClientLayer\Exceptions\ApiConnectorException;
use anlutro\cURL\cURL;

class ApiConnector {

    protected $ApiServer;
    protected $usertoken;
    private static $instance;
    protected $files = [];



    private function __construct(ApiServer $apiServer)
    {
        $this->ApiServer = $apiServer;
    }

    public static function getInstance($apiServer)
    {
        if(!self::$instance)
        {
            self::$instance = new ApiConnector($apiServer);
        }
        return self::$instance;
    }


    private function http_build_query_for_curl( $arrays, &$new = array(), $prefix = null ) {

        if ( is_object( $arrays ) ) {
            $arrays = get_object_vars( $arrays );
        }

        foreach ( $arrays AS $key => $value ) {
            $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
            if ( is_array( $value ) OR is_object( $value )  ) {
                $this->http_build_query_for_curl( $value, $new, $k );
            } else {
                $new[$k] = $value;
            }
        }
    }


    private function _request($url, $method = 'POST', $parameters = [])
    {
        if($url{0} != '/') throw new ApiConnectorException('Your service url must begin with slash.');
        $response = null;
        $curl = new cURL();
        $token = $this->generateToken($parameters);
        $parameters['__token'] = $token;
        foreach($this->files as $file){
            $parameters[$file->getPostFilename()] = $file;
        }

        $return = null;
        $url = $this->ApiServer->getEndpointUrl().$url;

        $request = $curl->newRequest($method, $url, $parameters);

        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        if(class_exists('Illuminate\Support\Facades\Config'))
            $request->setHeader('language', \Illuminate\Support\Facades\Config::get('app.locale'));

        $request->setOption(CURLOPT_FOLLOWLOCATION, 1);

        if($method == 'GET' || $method == 'DELETE'){
            $parameters['__token'] = $token;
            $iurl = $curl->buildUrl($url, $parameters);
            $request->setUrl($iurl);
        }
        if(in_array($method, ['DELETE', 'PUT'])) {
            $parameters['_method'] = $method;
            $method = 'POST';
        }
        try {
            if(count($this->files) > 0) {
                $novo = [];
                $this->http_build_query_for_curl($parameters, $novo);
                foreach($this->files as $file){
                    $novo[$file->getPostFilename()] = $file;
                }
                $curl->setDefaultOptions([CURLOPT_HEADER => ['Content-type: multipart/form-data']]);

                switch($method){
                    case 'POST':
                        $return = $curl->rawPost($url, $novo);
                        break;
                    case 'PUT':
                        $url = $curl->buildUrl($url, $novo); //Seria um problema do verbo put? Ter que passar parametros via get por não suportar uma payload assim como o POST....
                        $return = $curl->rawPut($url, $novo);
                        break;
                }
            } else {
                $return = $request->send();
            }
            //$return = $request->send();
        }catch(\Exception $err){
            throw new \Exception('Falha ao conectar. Url completa: '.$url.' | token: '.$parameters['__token'].' exception: '.$err->getMessage());
        }
        try {
            $ret = $this->translate($return->body);
            $ret->response = $return;
            return $ret;
        }catch(\Exception $e){
            return $return->body;
        }

    }

    /** Add a file to your server request.
     * @param $path (must be the root path)
     * @param $mimetype
     * @param $inputName
     * @throws ApiConnectorException
     * @return $this
     */
    public function addFile($path, $mimetype, $inputName)
    {
        if(!file_exists($path)) throw new ApiConnectorException('File '.$path.' not found!');
        if($inputName == '__token') throw new ApiConnectorException('__name is a reserved word from ApiConnector class. Please use another inputName');
        $this->files[] = new \CURLFile($path, $mimetype,$inputName);
        return $this;
    }

    /** Returns an array of CURLFile
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }


    protected function translate($token)
    {
        try{
            $trans = \JWT::decode($token, $this->ApiServer->getAppSecret(), ['HS256', 'HS384', 'ES256', 'RS384']);
        }catch(\Exception $e){
            throw new ApiConnectorException('N�o foi poss�vel decodificar a resposta ('.$e->getMessage().'). Resposta pura: '.$token);
        }
        return $trans;
    }

    public function generateToken($payload)
    {
        $header = ['AppKey' => $this->ApiServer->getAppKey()];
        if($this->usertoken) $header['UserKey'] = $this->usertoken;

        $tok = \JWT::encode($payload, $this->ApiServer->getAppSecret(), 'HS256', null, $header);
        return $tok;
    }

    public function withUser($usertoken)
    {
        $this->usertoken = $usertoken;
        return $this;
    }

    /** Execute a POST request to the service url.
     * @param $service
     * @return \anlutro\cURL\Response|null
     * @throws ApiConnectorException
     */
    public function get($service, array $parameters = [])
    {
        return $this->_request($service, 'GET', $parameters);
    }

    /** Execute a POST request to the service url.
     * @param $service
     * @param array $parameters
     * @return \anlutro\cURL\Response|null
     * @throws ApiConnectorException
     */
    public function post($service, array $parameters = [])
    {
        return $this->_request($service, 'POST', $parameters);
    }

    /**Execute a PUT request to the service url.
     * @param $service
     * @param array $parameters
     * @return \anlutro\cURL\Response|null
     * @throws ApiConnectorException
     */
    public function put($service, array $parameters = [])
    {
        return $this->_request($service, 'PUT', $parameters);
    }

    /**Execute a DELETE request to the service url.
     * @param $service
     * @param array $parameters
     * @return \anlutro\cURL\Response|null
     * @throws ApiConnectorException
     */
    public function delete($service, array $parameters = [])
    {
        return $this->_request($service, 'DELETE', $parameters);
    }

}
