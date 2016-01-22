<?php

namespace Octopuce\Acme;

use Octopuce\Acme\ChallengeSolver\SolverInterface;
use Octopuce\Acme\Exception\OwnershipNotFoundException;
use Octopuce\Acme\Exception\ChallengeFailException;

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
     * Challenge url
     * @var string
     */
    private $challengeUrl;

    /**
     * Token info for challenge
     * @var string
     */
    private $challengeToken;

    /**
     * Key thumbprint for challenge
     * @var string
     */
    private $challengeThumbprint;

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
     * Get the challenge data for later use
     *
     * @param SolverInterface $solver
     * @param string          $fqdn
     *
     * @return array An array containing
     */
    public function getChallengeData(SolverInterface $solver, $fqdn)
    {
        $this->loadByDomain($fqdn);

        $token = null;
        $availableChallenges = array();

        foreach ($this->challenges as $challenge) {

            $availableChallenges[] = $challenge['type'];

            if ($challenge['type'] == $solver->getType()) {

                if ($challenge['status'] != 'pending') {
                    throw new \UnexpectedValueException('Challenge is not in pending status');
                }

                $this->challengeUrl = $challenge['uri'];
                $this->challengeToken = $challenge['token'];
                break;
            }
        }

        if (null === $this->challengeToken || null == $this->challengeUrl) {
            throw new \RuntimeException(sprintf(
                'Challenge type %s not found for current ownership, available challenges are : %s',
                $solver->getType(),
                implode(', ', $availableChallenges)
            ));
        }

        $this->challengeThumbprint = $this->ssl->getPublicKeyThumbprint($this->getPublicKey());

        return $solver->getChallengeInfo($fqdn, $this->challengeToken, $this->challengeThumbprint);
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
        $challengeData = $this->getChallengeData($solver, $fqdn);

        if (!$solver->solve($this->challengeToken, $this->challengeThumbprint)) {
            throw new ChallengeFailException('Unable to solve challenge');
        }

        $this->client->challengeOwnership(
            $this->challengeUrl,
            $solver->getType(),
            $this->challengeToken.'.'.$this->challengeThumbprint,
            $this->getPrivateKey(),
            $this->getPublicKey()
        );

        // @todo : update storage ?

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
            'url'        => $this->url,
            'challenges' => $this->challenges,
        );
    }

}
