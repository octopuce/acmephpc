<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme;

use \JOSE;
use \phpseclib\Crypt\RSA;

/**
 * Main ACME Client Library
 *
 * @api
 */
class Client {

    /**
     * @var string
     */
    private $apiroot;

    /** List of allowed contact fields
     * TODO: search the officially recognized / allowed fields
     * TODO: are there any mandatory ones? 
     */
    public $contactFields = array("mailto", "tel");

    /**
     * Contact statuses, may be pending (no api call yet),
     * registered (api called, key sent), contracted (contract signed)
     * or error (the api doesnt recognize us anymore!)
     */
    const CONTACT_STATUS_PENDING = 0;
    const CONTACT_STATUS_REGISTERED = 1;
    const CONTACT_STATUS_CONTRACTED = 2;
    const CONTACT_STATUS_ERROR = 99;

    /**
     * current Nonce (available)
     */
    protected $nonce;

    /**
     * Maximum time we try to use a nonce before generating a new one.
     */
    const NONCE_MAX_AGE = 86400;

    /**
     * @var array public and private part of the current user.
     * use newReg or getReg to fill this one BEFORE any other API call
     */
    protected $userKey = null;

    /**
     * ROOT URL of the API (default to LetsEncrypt one)
     * @var string 
     */
    protected $api = null;

    /**
     * all URL of the API (found by calling /directory)
     * @var string 
     */
    protected $apiUrl = null;

    /**
     * http object to do POST and GET on ACME Server
     * default to Acme_Http_Client
     * @var HttpClientInterface
     */
    protected $http = null;

    /**
     * PSR-3 logging interface where we log something.
     * default to Acme_Log_File
     * @var \Psr\Log\LoggerInterface
     */
    protected $log = null;

    /**
     * SSL Library (wrapper to generate rsa keys, csr, check certs etc.
     * default to Acme_SSL
     * @var SslInterface
     */
    protected $ssl = null;

    /**
     * Storage class. Must be already initialized
     * default to StoragePdo
     * @var StorageInterface
     */
    protected $db = null;

    /**
     * List of ACME plugins to configure and unconfigure DNS, DVSNI 
     * or SimpleHttp validation
     * default to Acme_SimpleHttp_Apache single element
     * @var ValidationPlugin
     */
    protected $plugins = array();

    /**
     * 
     * @param string $apiroot URL root of the API (default to LETSENCRYPT URL)
     * @param \Octopuce\Acme\StorageInterface $db
     * @param \Octopuce\Acme\HttpClientInterface $http
     * @param \Psr\Log\LoggerInterface $log
     * @param \Octopuce\Acme\SslInterface $ssl
     */
    function __construct($apiroot = null, StorageInterface $db = null, HttpClientInterface $http = null, \Psr\Log\LoggerInterface $log = null, SslInterface $ssl = null) {
        if (is_null($apiroot)) {
            $this->api = "https://acme-v01.api.letsencrypt.org";
        } else {
            $this->api = rtrim($apiroot, "/");
        }
        // TODO if null => initialize with default classes
        $this->db = $db;
        $this->http = $http;
        $this->log = $log;
        $this->ssl = $ssl;
    }

    /**
     * 
     * @param Octopuce\Acme\ValidationPluginInterface $plugin
     */
    function setPlugin($plugin) {
        $this->plugins[$plugin->getType()] = $plugin;
    }

    /** Enumerate the API functions from a starting point
     * store them into the storage, store Nonce too 
     * you can call this one anytime, this allows you to get a new nonce too 
     * @param boolean $nosave used internally (don't save status, only get nonce)
     * @return array return the 4 urls as a hash
     */
    function enumApi($nosave = false) {
        $endpoints = array("new-authz", "new-cert", "new-reg", "revoke-cert");
        list($h, $c) = $this->http->get($this->api . "/directory");
        $status = array();
        if (isset($h["Replay-Nonce"])) {
            $this->nonce = $status["nonce"] = $h["Replay-Nonce"][0];
        }
        /* [new-authz] => https://acme-staging.api.letsencrypt.org/acme/new-authz
         * [new-cert] => https://acme-staging.api.letsencrypt.org/acme/new-cert
         * [new-reg] => https://acme-staging.api.letsencrypt.org/acme/new-reg
         * [revoke-cert] => https://acme-staging.api.letsencrypt.org/acme/revoke-cert
         */
        $res = json_decode($c, true);
        foreach ($endpoints as $e) {
            if (isset($res[$e])) {
                $status["apiurl"][$e] = $res[$e];
            }
        }
        $status["noncets"] = time();
        if (!$nosave) {
            $this->db->setStatus($status);
        }
        return $status["apiurl"];
    }

    /**
     * create a new account on the CA server for the associated contact.
     * the RSA key parameter can be omitted, in that case a 4096 bits RSA key is 
     * generated using $ssl library.
     * @param array $contact hash of contact information
     * @param string $privKey the unprotected RSA private key. 
     * must be in PEM encoded format.
     * allowed keys are tel and mailto 
     * @return integer an account number 
     * @throws AcmeException
     */
    function newReg(array $contact) {
        $contactApi = array();
        foreach ($contact as $key => $val) {
            if (!in_array($key, $this->contactFields, true)) {
                throw new AcmeException("Forbidden fields found in the contact hash", 15);
            }
            $contactApi[] = $key . ":" . $val;
        }

        $this->userKey = $this->ssl->genRsa();
        $reg["publickey"] = $this->userKey["publickey"];
        $reg["privatekey"] = $this->userKey["privatekey"];
        $reg["status"] = self::CONTACT_STATUS_PENDING;
        $reg["contact"] = json_encode($contactApi);
        // store it along with contact information
        $id = $this->db->setContact($reg);

        // now call the API to register this account
        list($headers, ) = $this->stdCall("new-reg", array("contact" => $contactApi));
        if (isset($headers["HTTP"])) {
            if ($headers["HTTP"][1] != "201") {
                throw new AcmeException("Error " . $headers["HTTP"][0] . " when calling the API", 2);
            }
        }
        if (!isset($headers["Location"])) {
            throw new AcmeException("Can't call newReg, unexpected result", 3);
        }
        $registered = array("id" => $id,
            "status" => self::CONTACT_STATUS_REGISTERED,
            "url" => $headers["Location"][0]);
        // store it along with contact information
        $this->db->setContact($registered);

        if (isset($headers["Link"])) {
            $tos = "";
            foreach ($headers["Link"] as $link) {
                $onelink = $this->extractLink($link);
                if ($onelink[1] == "terms-of-service") {
                    $tos = $onelink[0];
                }
            }
            if ($tos) {
                // try to sign any contract *right now* 
                list($headers, ) = $this->stdCall($headers["Location"][0], array('agreement' => $tos, 'contact' => $contactApi), "reg");
                if (isset($headers["HTTP"]) &&
                        $headers["HTTP"][1] == "202") {
                    $contracted = array("id" => $id, "status" => self::CONTACT_STATUS_CONTRACTED, "contract" => $tos);
                    $this->db->setContact($contracted);
                } else {
                    // TODO: what shall we do if new-reg is OKAY but we couldn't sign the contract ?
                }
            } else {
                // TODO: what if there is NOT TOS to sign??
            }
        }
        return $id;
    }

    /**
     * Connect to an existing account stored locally
     * and return the informations of this account
     * (excluding the private key by default)
     * YOU MUST CALL THIS FUNCTION (or newReg) 
     * BEFORE ANY API CALL 
     * since it chooses which account you will be using
     * to identify to ACME server.
     * @param integer $id the account number in the storage
     * @param boolean $privatekeytoo shall we return the private key too?
     * @return array the values of the account
     */
    public function getReg($id, $privatekeytoo = false) {
        $me = $this->db->getContact($id);
        if (!$me) {
            return false;
        }
        $this->userKey = $me["privatekey"];
        if (!$privatekeytoo) {
            unset($me["privatekey"]);
        }
        return $me;
    }

    /**
     * request validation of a new resource (usually a domain name) 
     * by calling new-authz acme api endpoint.
     * YOU MUST have called newReg or getReg before that (on the same session)
     * to choose which account to use
     * @param array $resource An hash describing the resource, must have
     * "type" and "value" keys, usually "dns" and "example.com"
     * @return array an hash containing all authz informations, including possible challenges
     * @throws AcmeException
     */
    function newAuthz(array $resource) {
        if (!isset($resource["type"]) || !isset($resource["value"])) {
            throw new AcmeException("Error, missing type or value when calling newAuthz", 4);
        }
        if (count($resource) > 2) {
            throw new AcmeException("Error, Unknown key when calling newAuthz", 5);
        }
        if ($resource["type"] != "dns") {
            throw new AcmeException("Error, unsupported type when calling newAuthz", 6);
        }
        $this->checkFqdn($resource["value"]); // may throw Exception
        // now call the API to prepare the AUTHZ
        $httpcall = $this->stdCall("new-authz", $resource);
        return $this->saveAuthz($httpcall);
    }

    /**
     * Return information of an existing Authz
     * @param integer $id the authz number in the storage
     * @param boolean $update Shall we update from the ACME Server?
     * @return array the values of the authz
     */
    public function getAuthz($id, $update = false) {
        $me = $this->db->getAuthz($id);
        if (!$me) {
            throw new AcmeException("Error Authz not found", 13);
        }
        if ($update) {
            $httpcall = $this->http->get($me["url"]);
            return $this->saveAuthz($httpcall, $id);
        }
        return $me;
    }

    /**
     * Save an Authz object from the httpcall of it (either at new-authz time 
     * or when getting up-to-date information about it
     * @param array $httpcall the two element array with headers and content
     * received from http call
     * @param integer $id the authz-id (non mandatory)
     * @return array the entire authz information including challenges and id
     * @throws AcmeException
     */
    private function saveAuthz(array $httpcall, integer $id = null) {
        list($headers, $content) = $httpcall;
        if (isset($headers["HTTP"])) {
            if ($headers["HTTP"][1] != "200") {
                throw new AcmeException("Error " . $headers["HTTP"][0] . " when calling the API", 2);
            }
        }
        if (!isset($headers["Location"])) {
            throw new AcmeException("Can't get Authz, unexpected result (missing location header)", 3);
        }
        if (!isset($content["challenges"])) {
            throw new AcmeException("Can't get Authz, unexpected result (missing challenges)", 3);
        }
        $authz = array("type" => $content["type"],
            "value" => $content["value"],
            "url" => $headers["Location"][0],
            "challenges" => $content["challenges"]
        );
        if (!isnull($id)) {
            $authz["id"] = $id;
        }
        // store it along with contact information
        $id = $this->db->setAuthz($authz);
        $authz["id"] = $id;
        return $authz;
    }

    /**
     * Solves a challenge by calling an appropriate plugin and calling the API
     * to ask for it. 
     * @param integer $authzId The authz-id we want to challenge
     * @param string $type which challenge type we use. 
     * Note: a plugin for this challenge type must be loaded before that call
     * @return array an updated authz object, telling which challenge is fine (or not)
     */
    public function solveChallenge($authzId, $type) {
        $authz = $this->db->getAuthz($authzId);
        if (!$authz) {
            throw new AcmeException("Error, Authz not found", 8);
        }
        $found = false;
        foreach ($authz["challenges"] as $challenge) {
            if ($challenge["type"] == $type) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new AcmeException("Error, Challenge type not allowed for this Authz", 9);
        }
        if ($challenge["status"] != "pending") {
            throw new AcmeException("Error, Challenge not in pending status", 10);
        }
        if (!isset($this->plugins[$type])) {
            throw new AcmeException("Error, No plugin loaded for this challenge type", 11);
        }
        // installValidator returns a 2 element array: result (constant) and answer (may be used later here)
        list($result, $answer) = $this->plugins[$challenge]->installValidator($authz["value"], $challenge);
        if ($result == ACME_CHALLENGE_OK) {
            $resource = array(
                "resource" => "challenge",
                "type" => "simpleHttp"
            );
            switch ($type) {
                case "simpleHttp":
                    $resource["token"] = $challenge["token"];
                    $resource["tls"] = $answer["tls"];
                    break;
                case "dns":
                    $resource = array(); // TODO ! IMPLEMENTS DNS VALIDATION CALL
                    break;
            }
            // call for this challenge 
            $this->stdCall($challenge["uri"], $resource, "challenge");
            // what do I get back ?
        }
        return $authz;
    }

    /**
     * request a certificate for a domain name 
     * by calling new-cert acme api endpoint.
     * YOU MUST have called newReg or getReg before that (on the same session)
     * to choose which account to use
     * @param string $fqdn a fully qualified domain name you want a cert for
     * @param array $altNames (non-mandatory) other names to sign this certificate for
     * Please note that all fqdn or altNames must have been validated through an Authz + Challenge call before 
     * (and not too long ago, FIXME: How long is it valid? shall we validate on our side?)
     * @return array an hash containing all cert informations, including an ID from the Storage, key,csr,crt,chain as PEM strings
     * @throws AcmeException
     */
    function newCert($fqdn, $altNames = array()) {
        $this->checkFqdn($fqdn); // may throw Exception
        // Generate a proper CSR / KEY 
        $key = $this->ssl->genRsa();
        $csr = $this->ssl->genCsr($key, $fqdn, $altNames);
        $dercsr = $this->ssl->pemToDer($csr);
        $resource['csr'] = JOSE_URLSafeBase64::encode($dercsr);
        list($headers, $content) = $this->stdCall("new-cert", $resource);
        if (isset($headers["HTTP"])) {
            if ($headers["HTTP"][1] != "200") {
                throw new AcmeException("Error " . $headers["HTTP"][1] . " when calling the API", 2);
            }
        }
        // FIXME WHAT DO I GET BACK ??
        $cert = array("key" => $key,
            "csr" => $csr,
            "crt" => $content["crt"],
            "chain" => $content["chain"]
        );
        // store it along with contact information
        $id = $this->db->setCert($cert);
        $cert["id"] = $id;
        return $cert;
    }

    /**
     * Return information of an existing Certificate
     * @param integer $id the cert number in the storage
     * @return array the values of the cert
     */
    public function getCert($id) {
        $me = $this->db->getCert($id);
        if (!$me) {
            throw new AcmeException("Error Certificate not found", 12);
        }
        return $me;
    }

    /**
     * request a certificate recovation for a domain name 
     * by calling revoke-cert acme api endpoint.
     * YOU MUST have called newReg or getReg before that (on the same session)
     * to choose which account to use
     * @param integer $id the certificate-id to request revocation for
     * @return array an hash containing all cert informations, including an ID from the Storage, key,csr,crt,chain as PEM strings
     * @throws AcmeException
     */
    function revokeCert($id) {
        $cert = $this->getCert($id);
        // TODO : CODE THIS, NOT CODED YET
        $resource = array("fqdn" => $cert("fqdn"));
        list($headers, $content) = $this->stdCall("revoke-cert", $resource);
        if (isset($headers["HTTP"])) {
            if ($headers["HTTP"][1] != "200") {
                throw new AcmeException("Error " . $headers["HTTP"][1] . " when calling the API", 2);
            }
        }
        // FIXME WHAT DO I GET BACK ??
        $cert = array("key" => $key,
            "csr" => $csr,
            "crt" => $content["crt"],
            "chain" => $content["chain"]
        );
        // store it along with contact information
        $id = $this->db->setCert($cert);
        $cert["id"] = $id;
        return $cert;
    }

    /**
     * unlock the database and save the last nonce.
     * you MUST call this when you are finished with the API.
     */
    public function finish() {
        $this->db->unlock();
        $this->apiUrl = null;
    }

    /**
     * Call a ACME standard URL using JWS encoding signing for $this->userKey
     * @param string $api api url to call (short name, like "new-reg" or starting by http)
     * @param array $params list of key=>value to sent as a json object or array.
     * @return array the api call result (header + decoded content)
     */
    private function stdCall($api, $params, $resource = null) {
        $this->init();

        $public_key = new RSA();
        $public_key->loadKey($this->userKey["publickey"]);
        $jwk = \JOSE_JWK::encode($public_key); // => JOSE_JWK instance

        if (substr($api, 0, 4) == "http") {
            $url = $api;
            if (is_null($resource)) {
                throw new AcmeException("stdCall with URL api MUST include resource name", 14);
            }
        } else {
            $url = $this->apiUrl[$api];
            if (is_null($resource)) {
                $resource = $api;
            }
        }
        $params["resource"] = $resource;

        $jwt = new \JOSE_JWT($params);

        $jwt->header['jwk'] = $jwk->components;
        $jwt->header['nonce'] = $this->nonce;

        $jws = $jwt->sign($this->userKey["privatekey"], 'RS512');


        // call the API
        $httpResult = $this->http->post($url, $jws->toJson());
        // save the new Nonce
        if (isset($httpResult[0]["Replay-Nonce"])) {
            $this->nonce = $httpResult[0]["Replay-Nonce"][0];
        } else {
            $this->nonce = null;
        }

        $httpResult[1] = json_decode($httpResult[1]);
        return $httpResult;
    }

    /**
     * get the api information from the storage, (only once) and get a nonce.
     * if necessary (nonce too old) get a new one
     * lock the database
     */
    private function init() {
        if (!is_null($this->apiUrl)) {
            return;
        }
        if (is_null($this->userKey)) {
            throw new AcmeException("You must call newReg or getReg before any API call!", 1);
        }
        $this->db->lock();
        $status = $this->db->getStatus();
        $this->apiUrl = $status["apiurl"];
        if (
                !$status["nonce"] ||
                $status["noncets"] < (time() - self::NONCE_MAX_AGE)
        ) {
            // generate a new nonce, no need to save it => we consume it right now
            $this->enumApi(true);
        } else {
            // or use the current and mark it as used
            $this->nonce = $status["nonce"];
            $this->db->setStatus(array("nonce" => ""));
        }
    }

    /**
     * Return the link and rel attribute of an HTTP Link: header                                                                                                                                 
     * example: [Link] => <https://letsencrypt.org/documents/LE-SA-v1.0-June-23-2015.pdf>;rel="terms-of-service"                                                                                 
     * @param $header string the header content
     * @return array the link and rel attributes in a 2-elements array, or FALSE if the syntax looks wrong.                                                                                      
     */
    private function extractLink($header) {
        $mat = array();
        if (preg_match('#<([^>]*)>;rel="([^"]*)"#', $header, $mat)) {
            return array($mat[1], $mat[2]);
        } else {
            return false;
        }
    }

    /**
     * Check that a string is a proper FQDN name (RFC1035)
     * FQ means that we have at least 2 members ;) 
     * @throws AcmeException
     * @param string $fqdn a FQDN name to check
     * TODO: Is there an official PHP std call for that? maybe from i18n/l10n/punnycode pecl?
     */
    private function checkFqdn($fqdn) {
        if (strlen($fqdn) > 255) {
            throw new AcmeException("FQDN name is incorrect (name too long)", 7);
        }
        $members = explode(".", $fqdn);
        if (count($members) <= 1) {
            throw new AcmeException("FQDN name is incorrect (no dot)", 7);
        }
        while (list ($key, $val) = each($members)) {
            if (strlen($val) > 63) {
                throw new AcmeException("FQDN name is incorrect (one member is too long)", 7);
            }
            // Note: RFC1035 tells us that a domain should not start by a digit, but every registrar allows such a domain to be created... too bad.                                             
            // added - at the beginning (for punnycode-encoded strings)
            if (!preg_match("#^[a-z0-9-_]([a-z0-9-_]*[a-z0-9_])?$#i", $val)) {
                throw new AcmeException("FQDN name is incorrect (unauthorized characters)", 7);
            }
        }
    }

}
