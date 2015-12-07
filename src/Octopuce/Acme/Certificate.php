<?php

namespace Octopuce\Acme;

use Octopuce\Acme\Storage\StorageInterface;
use Octopuce\Acme\Http\HttpClientInterface;
use Octopuce\Acme\Ssl\SslInterface;
use Octopuce\Acme\Exception\CertificateNotFoundException;


class Certificate extends AbstractEntity implements CertificateInterface
{
    /**
     * SslInterface instance
     * @var SslInterface
     */
    private $ssl;

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
     * Certificate
     * @var string
     */
    private $certificate;

    /**
     * Chain
     * @var string
     */
    private $chain;

    /**
     * Valid from
     * @var \DateTime
     */
    private $validFrom;

    /**
     * Valid until
     * @var \DateTime
     */
    private $validUntil;

    /**
     * Status
     * @var int
     */
    private $status = 0;

    /**
     * Constructor
     * Override parent to add SSL dependency
     *
     * @param StorageInterface     $storage
     * @param HttpClientInterface  $client
     * @param int                  $id
     * @param SslInterface         $ssl
     */
    public function __construct(StorageInterface $storage, HttpClientInterface $client, SslInterface $ssl)
    {
        parent::__construct($storage, $client);

        $this->ssl = $ssl;
    }

    /**
     * Sign a new certificate for a given fqdn and store it
     *
     * @param string $fqdn
     * @param array  $altnames
     *
     * @return $this
     *
     * @throws ApiCallException
     */
    public function sign($fqdn, array $altNames = array())
    {
        // Check all provided names
        $this->checkFqdn($fqdn);
        foreach ($altNames as $name) {
            $this->checkFqdn($name);
        }

        // Generate a proper CSR / KEY
        $key = $this->ssl->generateRsaKey();
        $csr = $this->ssl->generateCsr($key, $fqdn, $altNames);
        $dercsr = $this->ssl->pemToDer($csr);

        // Call API
        $response = $this->client->signCertificate($dercsr);

        var_dump($response);
        die();

        // Header 200 will be handled by Guzzle
        if (isset($headers["HTTP"])) {
            if ($headers["HTTP"][1] != "200") {
                throw new AcmeException("Error " . $headers["HTTP"][1] . " when calling the API", 2);
            }
        }

        // FIXME WHAT DO I GET BACK ??
        $cert = array(
            "key" => $key,
            "csr" => $csr,
            "crt" => $content["crt"],
            "chain" => $content["chain"]
        );

        // store it along with contact information
        $id = $this->db->setCert($cert);
        $cert["id"] = $id;
        return $cert;
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
     * Save certificate to storage
     */
    public function save()
    {
        return $this->storage->save($this);
    }
}
