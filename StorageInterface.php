<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme;

/**
 * Acme DB storage interface
 * @author benjamin
 */
interface StorageInterface {

    /**
     * save the status of the API, this includes
     * the nonce (+ the current date of it)
     * the 4 api endpoints urls: "new-authz","new-cert","new-reg","revoke-cert"
     * @param array $data
     * @return boolean true if the status has been saved. 
     */
    function setStatus(array $data);

    /**
     * return the status informations
     * nonce, its date (as a 64bits unix timestamp) and the 4 api endpoints url
     * @return array
     */
    function getStatus();

    /**
     * save a new (or existing) contact information
     * hash may contain id (for existing contact) contact hash (authorized only)
     * private key (X.509 PEM), public key (X.509 PEM), signed contract, status 
     * @param array $data
     * @return integer the contact id
     */
    function setContact($data);

    /**
     * get informations for an existing contact 
     * hash will contain id, contact information (as a hash)
     * private key (X.509 PEM), public key (X.509 PEM), signed contract, status 
     * @param integer $id
     * @return array the contact informations
     */
    function getContact($id);

    /**
     * save a new (or existing) authz information
     * hash may contain id (for existing authz) type ("dns"), value (dns name),
     * url, challenges (list of, with their values / status)
     * @param array $data
     * @return integer the authz id
     */
    function setAuthz($data);

    /**
     * get informations for an existing authz
     * hash will contain id, type, value, url, challenges
     * @param integer $id
     * @return array the authz informations
     */
    function getAuthz($id);

    /**
     * save a new (or existing) certificate information
     * hash may contain id (for existing cert) fqdn, altnames (array), 
     * privatekey, certificate, chain, validfrom, validuntil, status
     * @param array $data
     * @return integer the certificate id
     */
    public function setCert($data);

    /**
     * get informations for an existing Certificate
     * returns fqdn, altnames, privatekey, certificate, chain, validfrom, validuntil, status
     * @param integer $id
     * @return array the certificate informations
     */
    public function getCert($id);

    /**
     * lock the database to prevent other calls to be done in parallel
     * if using SQL, we recommend using a LOCK TABLE or BEGIN statement
     * if using another storage, flock may be advisable
     */
    function lock();

    /**
     * unlock the database to allow other calls to be done after it
     * if using SQL, we recommend using a UNLOCK TABLES or COMMIT statement
     * if using another storage, closing the flock may be advisable
     */
    function unlock();
}
