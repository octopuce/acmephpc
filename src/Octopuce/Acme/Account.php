<?php

namespace Octopuce\Acme;

use Octopuce\Acme\Storage\StorageInterface;
use Octopuce\Acme\Http\HttpClientInterface;
use Octopuce\Acme\Exception\InvalidRegisterArgumentException;
use Octopuce\Acme\Exception\AccountNotFoundException;

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
    private $contact;

    /**
     * Url
     * @var string
     */
    private $url;

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

        $this->contact = json_encode(array(
            'mailto:'.$mailto,
            'tel:'.$tel,
        ));

        // Save as pending
        // @fixme : what if api call raises an exception ? orphan row ?
        $this->created = $this->modified = time();
        $this->id = $this->save('account');

        // Call api to register
        $response = $this->client->registerNewAccount($mailto, $tel, $this->getPrivateKey(), $this->getPublicKey());

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
     * @param int $id
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws AccountNotFoundException
     */
    public function load($id)
    {
        $id = (int) $id;

        if (empty($id)) {
            throw new \InvalidArgumentException('Id must be set prior to load');
        }

        if (!$data = $this->storage->findById($id, 'account')) {
            throw new AccountNotFoundException(sprintf('Account with id %d could not be found', $id));
        }

        $this->id         = $id;
        $this->contact    = $data['contact'];
        $this->setKeys($data['privatekey'], $data['publickey']);
        $this->url        = $data['url'];
        $this->status     = $data['status'];

        return $this;
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
            'created'    => $this->created,
            'modified'   => $this->modified,
            'contact'    => $this->contact,
            'privatekey' => $this->getPrivateKey(),
            'publickey'  => $this->getPublicKey(),
            'contract'   => '',
            'url'        => '',
            'status'     => $this->status,
        );
    }
}
