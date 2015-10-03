<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme;

/**
 * Acme Challenge installer/removal scripts, 
 * that should usually be adapted to your bind/apache/nginx ... configuration
 * @author benjamin
 */
class ValidationTest implements ValidationPluginInterface {

    /**
     * Install a validator by giving it the necessary data 
     * (the raw json_decoded structure of the Acme Auth Object)
     * @param string $fqdn
     * @param mixed $data
     */
    function installValidator($fqdn, $data) {
        
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
        return "simpleHttp";
    }
}
