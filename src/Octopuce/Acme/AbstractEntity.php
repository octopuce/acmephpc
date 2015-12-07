<?php

namespace Octopuce\Acme;

use Octopuce\Acme\Storage\StorageInterface;
use Octopuce\Acme\Http\HttpClientInterface;
use Octopuce\Acme\Ssl\SslInterface;
use Octopuce\Acme\Exception\InvalidDomainException;
use Octopuce\Acme\Exception\NoPublicKeyException;
use Octopuce\Acme\Exception\NoPrivateKeyException;

abstract class AbstractEntity
{
    /**
     * Storage interface instance
     * @var StorageInterface
     */
    protected $storage;

    /**
     * HTTP Client interface instance
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * SSL interface instance
     * @var SSLInterface
     */
    protected $ssl;

    /**
     * Creation date TS
     * @var int
     */
    protected $created;

    /**
     * Modification date TS
     * @var int
     */
    protected $modified;

    /**
     * Private key for request signing
     * @var string
     */
    private $privateKey;

    /**
     * Public key for request signing
     * @var string
     */
    private $publicKey;

    /**
     * Id
     * @var int
     */
    protected $id;

    /**
     * Constructor
     *
     * @param StorageInterface    $storage
     * @param HttpClientInterface $client
     * @param SslInterface        $ssl
     */
    public function __construct(StorageInterface $storage, HttpClientInterface $client, SslInterface $ssl)
    {
        $this->storage  = $storage;
        $this->client   = $client;
        $this->ssl = $ssl;

        $this->created  = new \DateTime;
        $this->modified = new \DateTime;
    }

    /**
     * Check that a string is a proper FQDN name (RFC1035)
     * FQ means that we have at least 2 members ;)
     *
     * @param string $fqdn a FQDN name to check
     *
     * @return void
     *
     * @throws InvalidDomainException
     */
    protected function checkFqdn($fqdn)
    {
        if (!preg_match("#^(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}$)$#i", $fqdn)) {
            throw new InvalidDomainException(sprintf('Provided domain name is incorrect : %s', $fqdn));
        }
    }

    /**
     * Set private / public key pair
     *
     * @param string $privateKey
     * @param string $publicKey
     *
     * @return $this
     */
    public function setKeys($privateKey, $publicKey)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Get private key
     *
     * @return string
     *
     * @throws NoPrivateKeyException
     */
    public function getPrivateKey()
    {
        if (empty($this->privateKey)) {
            throw new NoPrivateKeyException('Private key was not set');
        }

        return $this->privateKey;
    }

    /**
     * Get public key
     *
     * @return string
     *
     * @throws NoPublicKeyException
     */
    public function getPublicKey()
    {
        if (empty($this->publicKey)) {
            throw new NoPublicKeyException('Public key was not set');
        }

        return $this->publicKey;
    }

    /**
     * Set Id
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * Get Id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Save object into storage
     *
     * @param string $tableKey
     *
     * @return int Id of the saved object
     */
    public function save($tableKey)
    {
        return $this->storage->save($this, $tableKey);
    }
}