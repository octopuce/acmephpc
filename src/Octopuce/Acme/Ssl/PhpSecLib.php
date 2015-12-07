<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme\Ssl;

use \phpseclib\Crypt\RSA;

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
    public function generateCsr($privKey, $fqdn, array $alternateNames = array())
    {

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
    public function pemToDer($pem)
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
