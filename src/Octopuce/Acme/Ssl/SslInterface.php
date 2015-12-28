<?php

/*
 * This file is part of the ACME PHP Client Library
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */

namespace Octopuce\Acme\Ssl;

/**
 * Acme SSL interface
 * @author benjamin
 */
interface SslInterface
{
    /**
     * Generate a $length bits RSA private key
     *
     * @param  int    $lentgh  Length of the key
     *
     * @return array           An array containing key pair in PEM format
     *                         array ('publickey' => '..', 'privateKey' => '..').
     */
    public function generateRsaKey($length = 4096);

    /**
     * Generate a CSR for given fqdn & altnames
     *
     * @param string $fqdn
     * @param array  $altNames
     *
     * @return string The CSR in DER format
     */
    public function generateCsr($fqdn, array $altNames = array());

}
