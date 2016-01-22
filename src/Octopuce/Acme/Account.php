<?php

namespace Octopuce\Acme;

use Octopuce\Acme\Storage\StorageInterface;
use Octopuce\Acme\Http\HttpClientInterface;
use Octopuce\Acme\Ssl\SslInterface;
use Octopuce\Acme\Exception\InvalidRegisterArgumentException;
use Octopuce\Acme\Exception\AccountNotFoundException;
use Octopuce\Acme\Exception\AccountNotLoadedException;
use Octopuce\Acme\Exception\NoPublicKeyException;
use Octopuce\Acme\Exception\NoPrivateKeyException;
use Octopuce\Acme\Exception\ApiBadResponseException;

class Account extends AbstractEntity implements StorableInterface, AccountInterface
{
    /**
     * Contact statuses, may be pending (no api call yet),
     * registered (api called, key sent), contracted (contract signed)
     * or error (the api doesnt recognize us anymore!)
     */
    const STATUS_PENDING    = 0;
    const STATUS_REGISTERED = 1;
    const STATUS_CONTRACTED = 2;
    const STATUS_ERROR      = 99;

    /**
     * Contact
     * @var string
     */
    private $mailto;

    /**
     * Tel
     * @var string
     */
    private $tel;

    /**
     * Url
     * @var string
     */
    private $url = '';

    /**
     * Status
     * @var int
     */
    private $status = self::STATUS_PENDING;

    /**
     * Register new account from api and save it to storage
     *
     * @param string $mailto
     * @param string $tel
     *
     * @return $this
     *
     * @throws InvalidRegisterArgumentException
     */
    public function register($mailto, $tel)
    {
        // @todo : add email validation ?
        if (empty($mailto)) {
            throw new InvalidRegisterArgumentException('mailto cannot be empty !', 15);
        }

        $this->mailto = $mailto;
        $this->tel    = $tel;

        // Save as pending
        // @fixme : what if api call raises an exception ? orphan row ?
        $this->created = $this->modified = time();
        $this->id = $this->save('account');

        // Call api to register
        $response = $this->client->registerNewAccount($mailto, $tel, $this->getPrivateKey(), $this->getPublicKey());

        $headers = $response->getHeaders();
        if (!$headers->offsetExists('location')) {
            throw new ApiBadResponseException('Response does not contain location header');
        }
        $this->url = (string) $headers->get('location');

        // Save to update status
        $this->status = self::STATUS_REGISTERED;
        $this->save('account');

        // Call api to try to sign any contract
        $this->client->signContract($response, $mailto, $tel, $this->getPrivateKey(), $this->getPublicKey());

        // Save to contracted status
        $this->status = self::STATUS_CONTRACTED;
        $this->save('account');

        return $this;
    }

    /**
     * Load account from storage
     *
     * @param mixed $id
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws AccountNotFoundException
     */
    public function load($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Empty id provided');
        }

        if (!$data = $this->storage->findById($id, 'account')) {
            throw new AccountNotFoundException(sprintf('Account with id %s could not be found', $id));
        }

        $this->id         = $id;
        $this->mailto     = $data['mailto'];
        $this->tel        = $data['tel'];
        $this->url        = $data['url'];
        $this->status     = $data['status'];
        $this->setKeys($data['privatekey'], $data['publickey']);

        return $this;
    }

    /**
     * Recover account from API
     *
     */
    public function recover($id)
    {

        $this->client->recoverAccount($id);

    }

    /**
     * {@inheritDoc}
     *
     * @throws AccountNotLoadedException
     */
    public function getPrivateKey()
    {
        try {
            return parent::getPrivateKey();
        } catch (NoPrivateKeyException $e) {
            throw new AccountNotLoadedException('Account must either be defined in config or loaded prior usage');
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws AccountNotLoadedException
     */
    public function getPublicKey()
    {
        try {
            return parent::getPublicKey();
        } catch (NoPublicKeyException $e) {
            throw new AccountNotLoadedException('Account must either be defined in config or loaded prior usage');
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
            'id'         => $this->id,
            'mailto'     => $this->mailto,
            'tel'        => $this->tel,
            'created'    => $this->created,
            'modified'   => $this->modified,
            'privatekey' => $this->getPrivateKey(),
            'publickey'  => $this->getPublicKey(),
            'contract'   => '',
            'url'        => $this->url,
            'status'     => $this->status,
        );
    }
}
