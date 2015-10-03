<?php

/*
 * This file is part of the ACME PHP Client Library 
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme\Test;

/**
 * Acme HTTP GET/POST calls class using dummy code to test the library
 * @author benjamin
 */
class HttpClientTest implements Octopuce\Acme\HttpClientInterface {

    /**
     * Call a HTTP or HTTPS url using a GET method
     * and return the headers and content as a 2 elements array.
     * the HTTP Error Code must be one of the headers, named "http" 
     * @param string $url http or https url to call 
     * @return array a 2 elements array with headers as an array of key=>value and content as a string
     */
    function get($url) {
        $headers = array();
        $content = array();
        return array($headers, $content);
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
        $headers = array();
        $content = array();
        return array($headers, $content);
    }

}
