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
     * @param  int    $lentgh Length of the key
     *
     * @return string         The PEM-encoded version of the unprotected key.
     */
    public function generateRsaKey($length = 4096);

    /**
     * Generate a CSR for the associated with the unprotected RSA private key
     * for the specified FQDN. If alternate names are given, they will be
     * added as X.509 attributes too.
     *
     * @param string   $privKey (as returned by genRsa())
     * @param string   $fqdn
     * @param array    $alternateNames
     *
     * @return string  A PEM-encoded Certificate Request
     */
    public function generateCsr($privKey, $fqdn, array $alternateNames = array());

    /**
     * Check that the $cert certificate is a valid, non-expired, proper
     * X.509 certificate. If some chained certificates are given, also check
     * the chain up to a known CA, and if the RSA private key is given, also
     * check that the cert correspond to the private key
     *
     * @param  string  $cert    The PEM-encoded X.509 certificate to check
     * @param  string  $chain   The chained certificates.
     * @param  string  $privKey The unprotected RSA key to check the cert against
     *
     * @return bool
     */
    public function checkCertificate($cert, $privKey = null);

    /**
     * Convert a PEM-encoded CSR into DER
     *
     * @param  string $pem
     *
     * @return string DER-encoded version of the same structure
     */
    public function pemToDer($pem);
}
