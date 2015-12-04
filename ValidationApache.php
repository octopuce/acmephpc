<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme;

use \phpseclib\Crypt\RSA;

/**
 * Acme Challenge installer/removal scripts, 
 * that should usually be adapted to your bind/apache/nginx ... configuration
 * @author benjamin
 */
class ValidationApache implements ValidationPluginInterface {

    /**
     * In this folder, we will put one .TXT file per validator. 
     * a PHP script or proper nginx/apache configuration should then serve them at
     * /.well-known/acme-challenge/<hash> with application/json or text/plain content-type.
     * @var string Location of the .txt files to save with challenges
     */
    public $txtroot = "/tmp/";
    protected $client;

    /**
     * store the CLIENT object in case it needs it
     */
    function setClient(&$client) {
        $this->client = $client;
    }

    /**
     * Install a validator by giving it the necessary data 
     * (the raw json_decoded structure of the Acme Auth Object)
     * @param string $fqdn
     * @param mixed $data
     */
    function installValidator($fqdn, $data) {
        if (file_put_contents($this->txtroot . "/" . $data["token"] . ".txt", 
                $data["token"].".".$this->client->getKeyId()) !== false) {
           //return array(Client::VALIDATOR_PENDING, "come back later"); // TODO REMOVE ME ;) 
            return array(Client::VALIDATOR_OK, "OK, apache ready");
        } else {
            return array(Client::VALIDATOR_ERROR, "Fatal error: can't write to file");
        }
    }

    /**
     * Remove a previously installed validator 
     * @param string $fqdn
     * @param mixed $data
     */
    function removeValidator($fqdn, $data) {
        
    }

    /**
     * Return the supported type of the validator class
     * @return string type of the validator (DVSNI DNS HttpSimple ...)
     */
    function getType() {
        return "http-01";
    }

}
