<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme\Ssl;

use \phpseclib\Crypt\RSA;
use \phpseclib\File\X509;

/**
 * PhpSecLib adapter
 * @author benjamin
 */
class Phpseclib implements SslInterface
{
    /**
     * RSA factory instance
     * @var \Closure
     */
    private $rsa;

    /**
     * Constructor
     *
     * @param \Closure $rsa The closure to create new rsa instance
     */
    public function __construct(\Closure $rsa)
    {
        $this->rsa = $rsa;
    }

    /**
     * @inheritDoc
     */
    public function generateRsaKey($length = 4096)
    {
        $keys = $this->getRsa()->createKey($length);

        return array(
            'privatekey' => $keys['privatekey'],
            'publickey'  => $keys['publickey'],
        );
    }

    /**
     * @inheritDoc
     */
    public function generateCsr($fqdn, array $altNames = array())
    {
        $keys = $this->generateRsaKey(2048);

        $privKey = $this->getRsa();
        $privKey->loadKey($keys['privatekey']);

        $x509 = new X509;
        $x509->setPrivateKey($privKey);
        $x509->setDNProp('commonName', $fqdn);

        $x509->loadCSR($x509->saveCSR($x509->signCSR()));

        array_unshift($altNames, $fqdn);

        $SAN = array();
        foreach ($altNames as $dnsName) {
            $SAN[] = array('dNSName' => $dnsName);
        }

        // Set extension request.
        $x509->setExtension('id-ce-subjectAltName', $SAN);

        $pem = $x509->signCSR('sha256WithRSAEncryption');

        return $x509->saveCSR($pem, X509::FORMAT_DER);
    }

    /**
     * @inheritDoc
     */
    public function checkCertificate($cert, $privKey = null)
    {

    }

    /**
     * @inheritDoc
     */
    public function getRsa()
    {
        return $this->rsa->__invoke();
    }
}
