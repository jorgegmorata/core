<?php

namespace PSFS\base;

use PSFS\base\Singleton;

/**
 * Class Request
 * @package PSFS
 */
class Request extends Singleton{
    protected $server;
    protected $cookies;
    protected $upload;
    protected $header;
    protected $data;

    public function __construct(){
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->upload = $_FILES;
        $this->header = $this->parseHeaders();
        $this->data = $_REQUEST;
    }

    /**
     * Método que devuelve las cabeceras de la petición
     * @return array
     */
    private function parseHeaders(){ return getallheaders(); }

    /**
     * Método que verifica si existe una cabecera concreta
     * @param $header
     *
     * @return bool
     */
    public function hasHeader($header){ return (isset($this->header[$header])); }


    /**
     * Método que indica si una petición tiene cookies
     * @return bool
     */
    public function hasCookies(){ return (!empty($this->cookies)); }

    /**
     * Método que indica si una petición tiene cookies
     * @return bool
     */
    public function hasUpload(){ return (!empty($this->upload)); }

    /**
     * Método que devuelve el TimeStamp de la petición
     * @return mixed
     */
    public static function ts($formatted = false){ return self::getInstance()->getTs($formatted); }
    public function getTs($formatted = false){ return ($formatted) ? date('Y-m-d H:i:s',$this->server["REQUEST_TIME_FLOAT"]) : $this->server["REQUEST_TIME_FLOAT"]; }

    /**
     * Método que devuelve el Método HTTP utilizado
     * @return string
     */
    public function getMethod(){ return strtoupper($this->server["REQUEST_METHOD"]); }

    /**
     * Método que devuelve una cabecera de la petición si existe
     * @param $name
     *
     * @return null
     */
    public static function header($name){ return self::getInstance()->getHeader($name); }
    public function getHeader($name)
    {
        $header = null;
        if($this->hasHeader($name))
        {
            $header = $this->header[$name];
        }
        return $header;
    }

    /**
     * Método que devuelve la url solicitada
     * @return mixed
     */
    public static function requestUri(){ return self::getInstance()->getrequestUri(); }
    public function getrequestUri(){ return $this->server["REQUEST_URI"]; }

    public function getLanguage()
    {

    }

    /**
     * Método que determina si se ha solicitado un fichero
     * @return bool
     */
    public function isFile()
    {
        $file = (preg_match("/\.(css|js|png|jpg|jpeg|woff|ttf|svg|eot|xml|bmp|gif|txt|zip)$/", $this->getrequestUri()) != 0);
        return $file;
    }

    /**
     * Método que devuelve un parámetro de la solicitud
     * @param string $param
     *
     * @return null
     */
    public function get($param)
    {
        return $this->data[$param] ?: null;
    }

    /**
     * Método que devuelve todo los datos del Request
     * @return mixed
     */
    public function getData(){ return $this->data; }

    /**
     * Método que realiza una redirección a la url dada
     * @param string $url
     */
    public function redirect($url = null)
    {
        if(empty($url)) $url = $this->server['HTTP_ORIGIN'];
        header('Location: ' . $url);
        exit;
    }

    /**
     * Devuelve un parámetro de $_SERVER
     * @param $param
     *
     * @return null
     */
    public function getServer($param)
    {
        return $this->server[$param] ?: null;
    }
}