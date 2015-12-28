<?php

namespace Octopuce\Acme;

use Octopuce\Acme\Exception\CertificateNotFoundException;
use Octopuce\Acme\Exception\ApiBadResponseException;

class Certificate extends AbstractEntity implements CertificateInterface, StorableInterface
{
    /**
     * FQDN
     * @var string
     */
    private $fqdn;

    /**
     * Alt names
     * @var array
     */
    private $altNames = array();

    /**
     * Certificate string
     * @var string
     */
    private $certificate;

    /**
     * Sign a new certificate for a given fqdn and store it
     *
     * @param string $fqdn
     * @param array  $altnames
     *
     * @return $this
     *
     * @throws ApiBadResponseException
     */
    public function sign($fqdn, array $altNames = array())
    {
        // Check all provided names
        $this->checkFqdn($fqdn);
        foreach ($altNames as $name) {
            $this->checkFqdn($name);
        }

        // Generate a proper CSR
        $csr = $this->ssl->generateCsr(
            $fqdn,
            $altNames
        );

        // Call API
        $response = $this->client->signCertificate(
            \JOSE_URLSafeBase64::encode($csr),
            $this->getPrivateKey(),
            $this->getPublicKey()
        );

        // Certificate is available in the response
        if ($certificate = (string) $response->getBody()) {

            $this->certificate = $certificate;

        } else {

            // @todo : How to handle the postponed download ?
            // Here we should find a header location with the download url
        }

        return $this;
    }


    public function revoke($fqdn)
    {

    }

    public function update($fqdn)
    {

    }

    /**
     *
     * @throws CertificateNotFoundException
     */
    private function load()
    {
        $data = $this->storage->loadCertificate($this->id);

        if (empty($data)) {
            throw new CertificateNotFoundException('Certificate could not be found !', 12);
        }
    }

    /**
     * Get storable data
     *
     * @return array
     */
    public function getStorableData()
    {
        return array(
            'id'          => $this->id,
            'fqdn'        => $this->fqdn,
            'altNames'    => $this->altNames,
            'certificate' => $this->certificate,
        );
    }
}
