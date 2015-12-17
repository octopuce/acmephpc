<?php

namespace Octopuce\Acme;

use Octopuce\Acme\ChallengeSolver\SolverInterface;
use Octopuce\Acme\Exception\OwnershipNotFoundException;

class Ownership extends AbstractEntity implements StorableInterface, OwnershipInterface
{
    /**
     * Type
     * @var string
     */
    private $type = 'dns';

    /**
     * Value
     * @var string
     */
    private $value;

    /**
     * Url
     * @var string
     */
    private $url;

    /**
     * Challenges
     * @var array
     */
    private $challenges;

    /**
     * Register a new ownership
     *
     * @param string  $fqdn    Domain name
     *
     * @return $this
     */
    public function register($fqdn)
    {
        $this->checkFqdn($fqdn);

        $response = $this->client->registerNewOwnership(
            $fqdn,
            $this->type,
            $this->getPrivateKey(),
            $this->getPublicKey()
        );

        $responseData = json_decode((string) $response->getBody(), true);

        $this->created = $this->modified = time();
        $this->value = $fqdn;
        $this->challenges = json_encode($responseData['challenges']);

        $headers = $response->getHeaders();
        if ($headers->offsetExists('location')) {
            $this->url = (string) $headers->get('location');
        }

        $this->save('ownership');

        return $this;
    }

    /**
     * Challenge
     *
     * @param SolverInterface $solver
     * @param string          $fqdn
     *
     * @return $this
     *
     * @throws \UnexpectedValueException
     * @throws ChallengeFailException
     */
    public function challenge(SolverInterface $solver, $fqdn)
    {
        $rsa = $this->ssl->getRsa();
        $rsa->loadKey($this->getPublicKey());
        $thumbprint = \JOSE_JWK::encode($rsa)->thumbprint();

        $this->loadByDomain($fqdn);

        $token = null;
        $availableChallenges = array();

        foreach ($this->challenges as $challenge) {

            $availableChallenges[] = $challenge['type'];

            if ($challenge['type'] == $solver->getType()) {

                if ($challenge['status'] != 'pending') {
                    throw new \UnexpectedValueException('Challenge is not in pending status');
                }

                $targetUrl = $challenge['uri'];
                $token = $challenge['token'];
                break;
            }
        }

        if (null === $token || null == $targetUrl) {
            throw new \RuntimeException(sprintf(
                'Challenge type %s not found for current ownership, available challenges are : %s',
                $solver->getType(),
                implode(', ', $availableChallenges)
            ));
        }

        if (!$solver->solve($token, $thumbprint)) {
            throw new ChallengeFailException('Unable to solve challenge');
        }

        $this->client->challengeOwnership($targetUrl, $solver->getType(), $token.'.'.$thumbprint, $this->getPrivateKey(), $this->getPublicKey());

        return $this;
    }

    /**
     * Load by Id
     *
     * @param int $id
     *
     * @return $this
     *
     * @throws OwnershipNotFoundException
     */
    protected function load($id)
    {
        if (!$data = $this->storage->findById($id, 'ownership')) {
            throw new OwnershipNotFoundException(sprintf('Unable to find ownership with id %s', $id));
        }

        return $this->setData($data);
    }

    /**
     * Load by domain name
     *
     * @param string $fqdn
     *
     * @return $this
     *
     * @throws OwnershipNotFoundException
     */
    protected function loadByDomain($fqdn)
    {
        if (!$data = $this->storage->findOwnershipByDomain($fqdn)) {
            throw new OwnershipNotFoundException(sprintf('Unable to find ownership for domain %s', $fqdn));
        }

        return $this->setData($data);
    }

    /**
     * Set data from array
     *
     * @param array $data
     *
     * @return $this
     */
    private function setData(array $data)
    {
        $this->id         = $data['id'];
        $this->created    = $data['created'];
        $this->modified   = $data['modified'];
        $this->type       = $data['type'];
        $this->value      = $data['value'];
        $this->url        = $data['url'];
        $this->challenges = json_decode($data['challenges'], true);

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
            'type'       => $this->type,
            'value'      => $this->value,
            'url'        => '',
            'challenges' => $this->challenges,
        );
    }

}
