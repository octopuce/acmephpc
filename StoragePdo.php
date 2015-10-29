<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme;

/**
 * Acme DB PDO and SQL based storage interface
 * @author benjamin
 */
class StoragePdo extends \PDO implements StorageInterface {

    private $prefix = "acme_";

    public function __construct($dsn, $username = null, $password = null, $options = array()) {
        // TODO: ensure we trigger *exceptions* when something goes wrong
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * save the status of the API, this includes
     * the nonce (+ the current date of it)
     * the 4 api endpoints urls: "new-authz","new-cert","new-reg","revoke-cert"
     * @param array $data
     * @return boolean true if the status has been saved. 
     */
    public function setStatus(array $data) {
        $sql = "";
        $params = array();
        if (isset($data["nonce"])) {
            $sql.=", nonce=?";
            $params[] = $data["nonce"];
        }
        if (isset($data["noncets"])) {
            $sql.=", noncets=?";
            $params[] = $data["noncets"];
        }
        if (isset($data["apiurl"])) {
            $sql.=", apiurls=?";
            $params[] = json_encode($data["apiurl"]);
        }
        $stmt = $this->prepare("UPDATE " . $this->prefix . "status SET modified=NOW() " . $sql);
        return $stmt->execute($params);
    }

    /**
     * return the status informations
     * nonce, its date (as a 64bits unix timestamp) and the 4 api endpoints url
     * @return array
     */
    public function getStatus() {
        $res = $this->query("SELECT * FROM " . $this->prefix . "status;");
        $obj = $res->fetch(\PDO::FETCH_ASSOC);
        $obj["apiurl"] = json_decode($obj["apiurls"], true);
        unset($obj["apiurls"]);
        return $obj;
    }

    /**
     * save a new (or existing) contact information
     * hash may contain id (for existing contact) contact hash (authorized only)
     * private key (X.509 PEM), public key (X.509 PEM), signed contract, status 
     * @param array $data
     * @return integer the contact id
     */
    public function setContact($data) {
        return $this->autoSet("contact", $data, array("privatekey", "publickey", "contract", "status", "url"), array("contact"));
    }

    /**
     * get informations for an existing contact 
     * hash will contain id, contact information (as a hash)
     * private key (X.509 PEM), public key (X.509 PEM), signed contract, status 
     * @param integer $id
     * @return array the contact informations
     */
    public function getContact($id) {
        return $this->autoGet("contact", $id, array("privatekey", "publickey", "contract", "status", "url"), array("contact"));
    }

    /**
     * save a new (or existing) authz information
     * hash may contain id (for existing authz) type ("dns"), value (dns name),
     * url, challenges (list of, with their values / status)
     * @param array $data
     * @return integer the authz id
     */
    public function setAuthz($data) {
        return $this->autoSet("authz", $data, array("type", "value", "url"), array("challenges"));
    }

    /**
     * get informations for an existing authz
     * hash will contain id, type, value, url, challenges
     * @param integer $id
     * @return array the authz informations
     */
    public function getAuthz($id) {
        return $this->autoGet("authz", $id, array("type", "value", "url"), array("challenges"));
    }

    /**
     * save a new (or existing) certificate information
     * hash may contain id (for existing cert) fqdn, altnames (array), 
     * privatekey, certificate, chain, validfrom, validuntil, status
     * @param array $data
     * @return integer the certificate id
     */
    public function setCert($data) {
        return $this->autoSet("cert", $data, array("fqdn", "privatekey", "certificate", "chain", "validfrom", "validuntil", "status"), array("altnames"));
    }

    /**
     * get informations for an existing Certificate
     * returns fqdn, altnames, privatekey, certificate, chain, validfrom, validuntil, status
     * @param integer $id
     * @return array the certificate informations
     */
    public function getCert($id) {
        return $this->autoGet("cert", $id, array("fqdn", "privatekey", "certificate", "chain", "validfrom", "validuntil", "status"), array("altnames"));
    }

    /**
     * lock the tables to prevent parallel launches
     */
    public function lock() {
        $this->query("LOCK TABLE " . $this->prefix . "status;");
    }

    /**
     * unlock the tables to prevent parallel launches
     */
    public function unlock() {
        $this->query("UNLOCK TABLES;");
    }

    /**
     * Insert or update record in $table whose fields are $fields
     * also store arrays in $arrays as json_encoded data
     * if ID is set in the data, UPDATE the record. If it is not, INSERT it
     * @param string $table the table name
     * @param array $data the data to store
     * @param array $fields the list of standard fields 
     * @param array $arrays the list of json_encoded fields 
     * @return boolean true if the request executed successfully
     */
    private function autoSet($table, $data, $fields, $arrays) {
        $sql = "";
        $params = array();
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $sql.=", " . $field . "=?";
                $params[] = $data[$field];
            }
        }
        foreach ($arrays as $field) {
            if (isset($data[$field])) {
                $sql.=", " . $field . "=?";
                $params[] = json_encode($data[$field]);
            }
        }
        if (isset($data["id"])) {
            $sql = "UPDATE " . $this->prefix . $table . " SET modified=UNIX_TIMESTAMP(NOW()) " . $sql . " WHERE id=?";
            $params[] = $data["id"];
        } else {
            $sql = "INSERT INTO " . $this->prefix . $table . " SET created=UNIX_TIMESTAMP(NOW()), modified=UNIX_TIMESTAMP(NOW()) " . $sql;
        }
        $stmt = $this->prepare($sql);
        if ($stmt->execute($params)) {
            if (isset($data["id"])) {
                return $data["id"];
            } else {
                return $this->lastInsertId(); // TODO: not compatible with sqlite or pgsql  
            }
        } else {
            return false;
        }
    }

    /**
     * Get record in $table for id $id.
     * get $fields and get arrays from $arrays as json_encoded data
     * @param string $table the table name
     * @param array $id the it to search for 
     * @param array $fields the list of standard fields 
     * @param array $arrays the list of json_encoded fields 
     * @return array containing the found data (or false)
     */
    private function autoGet($table, $id, $fields, $arrays) {
        $sql = "SELECT * FROM " . $this->prefix . $table . " WHERE id=".intval($id);
        $res = $this->query($sql);
        $data = $res->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return false;
        }

        $return = array();
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $return[$field] = $data[$field];
            }
        }
        foreach ($arrays as $field) {
            if (isset($data[$field])) {
                $return[$field] = json_decode($data[$field], true);
            }
        }
        return $return;
    }

}
