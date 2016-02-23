<?php

namespace Octopuce\Acme;

use Octopuce\Acme\Exception\ApiCallErrorException;
use Octopuce\Acme\Exception\ApiBadResponseException;
use Octopuce\Acme\Exception\AccountNotFoundException;
use Symfony\Component\Finder\Finder;

class Client extends \Pimple implements ClientInterface
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
            'api' => 'https://acme.api.letsencrypt.org',
            'storage' => array(
                'type' => 'filesystem',
                'database' => array(
                    'dsn' => '',
                    'table_prefix' => 'acme',
                ),
            ),
            'challenge' => array(
                'type' => 'http',
                'config' => array(
                    'doc-root' => '',
                ),
            ),
            'account' => null,
        ),
    );

    /**
     * Initialized
     * @var bool
     */
    private $initialized = false;

    /**
     * @inheritDoc
     */
    public function __construct(array $values = array())
    {
        $this->defaultValues['params']['storage']['filesystem'] = __DIR__.'/../../../var';

        $values['storage'] = $this->share(function ($c) {
            $factory = new Storage\Factory(array(
                'filesystem' => function () use ($c) {
                    return new Storage\FileSystem($c['params']['storage']['filesystem'], new Finder);
                },
                'database' => function () use ($c) {
                    return new Storage\DoctrineDbal(
                        $c['params']['storage']['database']['dsn'],
                        $c['params']['storage']['database']['table_prefix']
                    );
                },
            ));

            return $factory->create($c['params']['storage']['type']);
        });

        $values['rsa'] = function () {
            return new \phpseclib\Crypt\RSA;
        };

        $values['ssl'] = $this->share(function ($c) {
            return new Ssl\PhpSecLib($c->raw('rsa'));
        });

        $values['http-client'] = $this->share(function ($c) {
            return new Http\GuzzleClient(
                new \Guzzle\Http\Client,
                $c->raw('rsa'),
                $c['storage']
            );
        });

        $values['certificate'] = $this->share(function ($c) {
            return new Certificate($c['storage'], $c['http-client'], $c['ssl']);
        });

        $values['account'] = $this->share(function ($c) {
            return new Account($c['storage'], $c['http-client'], $c['ssl']);
        });

        $values['ownership'] = function ($c) {
            return new Ownership($c['storage'], $c['http-client'], $c['ssl']);
        };

        $values['challenge-solver-http'] = $this->share(function ($c) {
            return new \Octopuce\Acme\ChallengeSolver\Http($c['params']['challenge']['config']);
        });
        /*
        $values['challenge-solver-dns'] = $this->share(function () {
            return new Octopuce\Acme\ChallengeSolver\Dns;
        });
        $values['challenge-solver-dvsni'] = $this->share(function () {
            return new Octopuce\Acme\ChallengeSolver\DvSni;
        });
        */

        // Override default values with provided config
        $values = array_replace_recursive($this->defaultValues, $values);

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

            // Load nonce from storage and check for validity
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

            if (!empty($this['params']['account'])) {
                try {
                    $this['account']->load($this['params']['account']);
                } catch (AccountNotFoundException $e) {
                    $this->newAccount($this['params']['account']);
                }
            }

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
     * Load account
     *
     * @param string $mailto Email of the account to be loaded
     *
     * @return $this
     */
    public function loadAccount($mailto)
    {
        $this['account']->load($mailto);

        return $this;
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
     * @param string $value Value of ownership (usually a fqdn)
     *
     * @return $this
     */
    public function newOwnership($value)
    {
        $this->init();

        $account = $this['account'];

        $this['ownership']
            ->setKeys($account->getPrivateKey(), $account->getPublicKey())
            ->register($value);

        return $this;
    }

    /**
     * Get challenge data to solve it manually
     *
     * @param string $fqdn                   FQDN
     * @param string $overrideChallengeType  Force this challenge type (use config if empty)
     *
     * @return array The data needed to solve the challenge
     */
    public function getChallengeData($fqdn, $overrideChallengeType = null)
    {
        $challengeSolver = $this->getChallengeSolver($overrideChallengeType);

        $this->init();

        $account = $this['account'];

        return $this['ownership']
            ->setKeys($account->getPrivateKey(), $account->getPublicKey())
            ->getChallengeData(
                $challengeSolver,
                $fqdn
            );
    }

    /**
     * Challenge an existing ownership
     *
     * @param string $fqdn                   FQDN to challenge
     * @param string $overrideChallengeType  Force this challenge type (use config if empty)
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function challengeOwnership($fqdn, $overrideChallengeType = null)
    {
        $challengeSolver = $this->getChallengeSolver($overrideChallengeType);

        $this->init();

        $account = $this['account'];

        $this['ownership']
            ->setKeys($account->getPrivateKey(), $account->getPublicKey())
            ->challenge(
                $challengeSolver,
                $fqdn
            );

        return $this;
    }

    /**
     * Get the challenge solver instance
     *
     * @param string $forceType
     *
     * @return \Octopuce\Acme\ChallengeSolver\SolverInterface
     */
    private function getChallengeSolver($forceType)
    {
        try {
            $challengeType = $this['params']['challenge']['type'];
            if (null !== $forceType) {
                $challengeType = $forceType;
            }

            $challengeSolver = $this->offsetGet('challenge-solver-'.$challengeType);

        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Challenge solver type %s is not supported', $challengeType));
        }

        return $challengeSolver;
    }

    /**
     * Sign a certificate for specified FQDN
     *
     * @param string $fqdn       FQDN to challenge
     * @param array  $altNames   Alternative names
     *
     * @return string The certificate content
     */
    public function signCertificate($fqdn, array $altNames = array())
    {
        $this->init();

        $account = $this['account'];

        return (string) $this['certificate']
            ->setKeys($account->getPrivateKey(), $account->getPublicKey())
            ->sign($fqdn);
    }

    /**
     * Get the certificate for a given FQDN
     *
     * @return string The certificate content
     */
    public function getCertificate($fqdn)
    {
        return (string) $this['certificate']->findByDomainName($fqdn);
    }

    /**
     * Revoke certificate for specified FQDN
     *
     * @param string $fqdn
     *
     * @return $this
     */
    public function revokeCertificate($fqdn)
    {
        $this->init();

        $account = $this['account'];

        $this['certificate']
            ->setKeys($account->getPrivateKey(), $account->getPublicKey())
            ->revoke($fqdn);

        return $this;
    }

    /**
     * Renew certificate for specified FQDN
     *
     * @param string $fqdn
     *
     * @return string The new certificate content
     */
    public function renewCertificate($fqdn)
    {

    }

}
