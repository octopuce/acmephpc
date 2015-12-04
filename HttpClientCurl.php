<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme;

/**
 * Acme HTTP GET/POST calls class using CURL and no proxy. 
 * (curl options can be customized using setOption() call
 * before using the class)
 * @author benjamin
 */
class HttpClientCurl implements HttpClientInterface {

    protected $curlopts = array();
    protected $curlHandle = null;
    public $verbose = false;

    /**
     * Call a HTTP or HTTPS url using a GET method
     * and return the headers and content as a 2 elements array.
     * the HTTP Error Code must be one of the headers, named "http" 
     * @param string $url http or https url to call 
     * @return array a 2 elements array with headers as an array of key=>value and content as a string
     */
    function get($url) {
        $this->_init();
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, null);
        curl_setopt($this->curlHandle, CURLOPT_POST, false);
        return $this->_curlCall($url);
    }

    /**
     * Call a HTTP or HTTPS url using a POST method, 
     * and return the headers and content as a 2 elements array.
     * the HTTP Error Code must be one of the headers, named "http" 
     * @param string $url http or https url to call 
     * @param array $post hash of posted data
     * @return array a 2 elements array with headers as an array of key=>value and content as a string
     */
    function post($url, $post) {
        $this->_init();
        curl_setopt($this->curlHandle, CURLOPT_POST, true);
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $post);
        if ($this->verbose) echo "POST:" . $post . "\n";
        return $this->_curlCall($url);
    }

    /**
     * Set options to use when doing CURL http calls.
     * @param type $key a key allowed in curl_setopt()
     * @param mixed $value a value for curl_setopt()
     */
    function setOption($key, $value) {
        $this->curlopts[$key] = $value;
    }

    /**
     * set default curl options
     */
    private function _init() {
        $this->curlHandle = curl_init();
        if ($this->verbose) curl_setopt($this->curlHandle, CURLOPT_VERBOSE, true);

        //  $header = array();  curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $header);                                                                                                                                                                    
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, 'ACME PHP Client 1.0');
        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, 15);
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->curlHandle, CURLOPT_HEADER, true);

        //curl_setopt($this->curlHandle, CURLOPT_VERBOSE, true);
        foreach ($this->curlopts as $key => $value) {
            curl_setopt($this->curlHandle, $key, $value);
        }
    }

    private function _curlCall($url) {
        curl_setopt($this->curlHandle, CURLOPT_URL, $url);
        $res = curl_exec($this->curlHandle);
        $lines = explode("\n", $res);
        $inheaders = true;
        $firstline = true;
        $headers = array();
        $content = "";
        foreach ($lines as $line) {
            if (!$inheaders) {
                $content.=$line;
            }
            if ($firstline) {
                $firstline = false;
                $headers["HTTP"] = explode(" ", $line);
            } else {
                if (!trim($line) && $inheaders) {
                    $inheaders = false;
                }
                if ($inheaders) {
                    list($h, $v) = explode(":", trim($line), 2);
                    $headers[$h][] = trim($v);
                }
            }
        }
        if ($this->verbose) echo "Headers:".print_r($headers,true)."\nContent:".print_r($content,true)."\n";
        return array($headers, $content);
    }

}
