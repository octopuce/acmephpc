<?php

namespace Octopuce\Acme;

use Pimple\Container;
use Octopuce\Acme\Exception\ApiCallErrorException;
use Octopuce\Acme\Exception\ApiBadResponseException;

class Client extends Container implements ClientInterface
{
    /**
     * User agent string
     */
    const USER_AGENT = 'ACME PHP Client 1.0';

    /**
     * Maximum time we try to use a nonce before generating a new one.
     */
    const NONCE_MAX_AGE = 86400;

    /**
     * Default config values
     * @var array
     */
    private $defaultValues = array(
        'params' => array(
            'table_prefix' => 'acme',
            'database' => '',
            'api' => 'https://acme.api.letsencrypt.org',
            'challenge' => array(
                'type' => 'http',
                'config' => array(
                    'doc-root' => '',
                ),
            ),
        ),
    );

    /**
     * Initialized
     * @var bool
     */
    private $initialized = false;

    /**
     * Nonce
     * @var string
     */
    private $nonce;

    /**
     * @inheritDoc
     */
    public function __construct(array $values = array())
    {
        $values = array_replace_recursive($this->defaultValues, $values);

        $this['storage'] = function ($c) {
            return new Storage\DoctrineDbal($c['params']['database'], $c['params']['table_prefix']);
        };

        $this['rsa'] = function () {
            return new \phpseclib\Crypt\RSA;
        };

        $this['ssl'] = function ($c) {
            return new Ssl\PhpSecLib($c->raw('rsa'));
        };

        $this['http-client'] = function ($c) {
            return new Http\GuzzleClient(
                new \Guzzle\Http\Client,
                $c->raw('rsa'),
                $c['storage']
            );
        };

        $this['certificate'] = function ($c) {
            return new Certificate($c['storage'], $c['http-client'], $c['ssl']);
        };

        $this['account'] = function ($c) {
            return new Account($c['storage'], $c['http-client'], $c['ssl']);
        };

        $this['ownership'] = function ($c) {
            return new Ownership($c['storage'], $c['http-client'], $c['ssl']);
        };

        $this['challenge-solver-http'] = function ($c) {
            return new \Octopuce\Acme\ChallengeSolver\Http($c['params']['challenge']['config']);
        };
        /*
        $this['challenge-solver-dns'] = function () {
            return new Octopuce\Acme\ChallengeSolver\Dns;
        };
        $this['challenge-solver-dvsni'] = function () {
            return new Octopuce\Acme\ChallengeSolver\DvSni;
        };
        */

        parent::__construct($values);


    }

    /**
     * Init data by calling enumerate
     *
     * @return $this
     */
    private function init()
    {
        if (!$this->initialized) {

            // Load nonce from database and check for validity
            $status = $this['storage']->loadStatus();

            if (empty($status['nonce']) || $status['noncets'] < (time() - self::NONCE_MAX_AGE)) {
                // If nonce is expired, reload it from enumerate
                $response = $this->enumerate();

                $status['nonce'] = (string) $response->getHeader('replay-nonce');
                $status['apiurls'] = (string) $response->getBody();

                // Store the new nonce and endpoints
                $this['storage']->updateStatus($status['nonce'], $status['apiurls']);
            }

            $this['http-client']
                ->setEndPoints(
                    json_decode($status['apiurls'], true)
                )
                ->setNonce($status['nonce']);

            $this->initialized = true;
        }

        return $this;
    }

    /**
     * Enumerate api endpoints and get a nonce
     *
     * @return string The replay-nonce header value
     */
    public function enumerate()
    {
        // Call directory endpoint
        return $this['http-client']->enumerate($this['params']['api']);
    }

    /**
     * Create and register a new account then store it
     *
     * @param string $mailto     Owner email address
     * @param string $tel        Optional phone number
     * @param string $privateKey Optional private key to use otherwise a new one will be created
     * @param string $publicKey  Optional public key to use otherwise a new one will be created
     *
     * @return $this
     */
    public function newAccount($mailto, $tel = null, $privateKey = null, $publicKey = null)
    {
        $this->init();

        // Generate new key pair from ssl service if not provided
        if (null === $privateKey || null === $publicKey) {
            $keys = $this['ssl']->generateRsaKey();
            $privateKey = $keys['privatekey'];
            $publicKey = $keys['publickey'];
        }

        $this['account']
            ->setKeys($privateKey, $publicKey)
            ->register($mailto, $tel);

        return $this;
    }

    /**
     * Ask for new ownership
     *
     * @param int    $accountId  Id of account
     * @param string $value      Value of ownership (usually a fqdn)
     *
     * @return $this
     */
    public function newOwnership($accountId, $value)
    {
        $this->init();

        $account = $this['account']->load($accountId);

        $this['ownership']
            ->setKeys($account->getPrivateKey(), $account->getPublicKey())
            ->register($value);

        return $this;
    }

    /**
     * Challenge an existing ownership
     *
     * @param int    $accountId  Id of account
     * @param string $type       Challenge type
     * @param string $value      Value of ownership (usually a fqdn)
     *
     * @throws \InvalidArgumentException
     */
    public function challengeOwnership($accountId, $value)
    {
        try {
            $challengeType = $this['params']['challenge']['type'];
            $challengeSolver = $this->offsetGet('challenge-solver-'.$challengeType);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Challenge solver type %s is not supported', $challengeType));
        }

        $this->init();

        $account = $this['account']->load($accountId);

        return $this['ownership']
            ->setKeys($account->getPrivateKey(), $account->getPublicKey())
            ->challenge(
                $challengeSolver,
                $value
            );
    }

    /**
     *
     */
    public function signCertificate($fqdn)
    {
        $this->init();

        return $this['certificate']
            ->sign(
                $this['account']->load($accountId),
                $fqdn
            );
    }

    public function revokeCertificate($id)
    {
        return $this['certificate']->revoke($id);
    }

    public function updateCertificate($id)
    {

    }

}