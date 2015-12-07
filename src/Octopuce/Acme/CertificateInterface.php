<?php

namespace Octopuce\Acme;

interface CertificateInterface
{
    /**
     * Sign a certificate using api
     *
     * @param string $fqdn      The fully qualified domain name
     * @param array  $altNames  Alternative names
     *
     * @return void
     */
    public function sign($fqdn, array $altNames = array());

    /**
     * Revoke all certificates for given fqdn
     *
     * @param string $fqdn The fully qualified domain name
     *
     * @return void
     */
    public function revoke($fqdn);

    /**
     * Update an existing certificate
     *
     * @param string $fqdn The fully qualified domain name
     *
     * @return void
     */
    public function update($fqdn);
}
