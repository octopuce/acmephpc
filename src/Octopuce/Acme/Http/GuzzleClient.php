<?php

namespace Octopuce\Acme\Http;

use Octopuce\Acme\Client as AcmePhpClient;
use Octopuce\Acme\Storage\StorageInterface;
use phpseclib\Crypt\RSA;
use Octopuce\Acme\Exception\WrongOperationException;
use Octopuce\Acme\Exception\ApiCallErrorException;
use Octopuce\Acme\Exception\ApiBadResponseException;
use Octopuce\Acme\Exception\NoContractInResponseException;
use Octopuce\Acme\Exception\CertificateNotYetAvailableException;

class GuzzleClient implements HttpClientInterface
{
    /**
     * Guzzle instance
     * @var \Guzzle\Http\Client
     */
    private $guzzle;

    /**
     * RSA closure instance
     * @var \Closure
     */
    private $rsa;

    /**
     * Methods endpoints retrieved from enumerate
     * @var array
     */
    private $endPoints = array();

    /**
     * Anti replay Nonce
     * @var string
     */
    private $nonce;

    /**
     * Constructor
     *
     * @param \Guzzle\Http\Client $guzzle   Guzzle instance
     * @param \Closure            $rsa      Closure for phpseclib RSA instance
     * @param StorageInterface    $storage  Storage instance
     */
    public function __construct(\Guzzle\Http\Client $guzzle, \Closure $rsa, StorageInterface $storage)
    {
        $this->guzzle = $guzzle;
        $this->guzzle->setUserAgent(AcmePhpClient::USER_AGENT);

        $this->rsa = $rsa;

        $this->storage = $storage;
    }

    /**
     * Enumerate API endpoints
     *
     * @param string $baseUrl
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function enumerate($baseUrl)
    {
        try {
            // Call directory endpoint
            $response = $this->guzzle->get($baseUrl . '/directory')->send();
        } catch (\Exception $e) {
            throw new ApiCallErrorException($e->getMessage(), 2);
        }

        return $this->processResponse($response);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ApiBadResponseException
     */
    public function registerNewOwnership($value, $type, $privateKey, $publicKey)
    {
        $params = array(
            'resource' => 'new-authz',
            'identifier' => array(
                'type' => $type,
                'value' => $value,
            )
        );

        $response = $this->sendPostRequest($this->getUrl('new-authz'), $params, $privateKey, $publicKey);

        $responseData = json_decode((string) $response->getBody(), true);
        if (!array_key_exists('challenges', $responseData)) {
            throw new ApiBadResponseException('Response body does not contain challenges', 3);
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function challengeOwnership($url, $type, $keyAuth, $privateKey, $publicKey)
    {
        $params = array(
            'resource'         => 'challenge',
            'type'             => $type,
            'keyAuthorization' => $keyAuth,
        );

        return $this->sendPostRequest($url, $params, $privateKey, $publicKey);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ApiBadResponseException
     */
    public function signCertificate($dercsr, $privateKey, $publicKey)
    {
        $params = array(
            'resource' => 'new-cert',
            'csr' => $dercsr,
        );

        $response = $this->sendPostRequest($this->getUrl('new-cert'), $params, $privateKey, $publicKey);

        if (!$output = (string) $response->getBody()) {

            $headers = $response->getHeaders();

            if (!$headers->offsetExists('location')) {
                throw new ApiBadResponseException('No certificate in the response and no url received for download');
            } else {
                throw new CertificateNotYetAvailableException($headers->get('location'));
            }
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ApiBadResponseException
     */
    public function revokeCertificate($cert, $privateKey, $publicKey)
    {
        $params = array(
            'resource' => 'revoke-cert',
            'certificate' => $cert,
        );

        return $this->sendPostRequest($this->getUrl('revoke-cert'), $params, $privateKey, $publicKey);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ApiBadResponseException
     */
    public function registerNewAccount($mailto, $tel, $privateKey, $publicKey)
    {
        $params = array(
            'resource' => 'new-reg',
            'contact' => array(
                'mailto:'.$mailto,
            ),
        );

        if (!empty($tel)) {
            $params['contact'][] = 'tel:'.$tel;
        }

        $response = $this->sendPostRequest($this->getUrl('new-reg'), $params, $privateKey, $publicKey);

        // Check response has location header
        if (!$response->getHeaders()->offsetExists('location')) {
            throw new ApiBadResponseException('Response does not contain "Location" header', 3);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function recoverAccount($mailto, $tel, $privateKey, $publicKey)
    {
        $params = array(
            'resource' => 'recover-reg',
            'method'   => 'contact',
            'base'     => '',
            'contact' => array(
                'mailto:'.$mailto,
            ),
        );

        if (!empty($tel)) {
            $params['contact'][] = 'tel:'.$tel;
        }

        $response = $this->sendPostRequest('https://acme-staging.api.letsencrypt.org/recover-reg', $params, $privateKey, $publicKey);

    }

    /**
     * {@inheritdoc}
     *
     * @throws NoContractInResponseException
     */
    public function signContract($response, $mailto, $tel, $privateKey, $publicKey)
    {
        $headers = $response->getHeaders();

        // Get terms of service and try to sign
        if ($headers->offsetExists('link')
            && preg_match('#<([^>]+)>;rel="terms-of-service"#', (string) $headers->get('link'), $match)) {

            $signUrl = (string) $headers->get('location');

            $params = array(
                'resource'  => 'reg',
                'agreement' => $match[1],
                'contact' => array(
                    'mailto:'.$mailto,
                ),
            );

            if (!empty($tel)) {
                $params['contact'][] = 'tel:'.$tel;
            }

            return $this->sendPostRequest($signUrl, $params, $privateKey, $publicKey);
        }

        // TODO: what shall we do if new-reg is OKAY but we couldn't sign the contract ?
        throw new NoContractInResponseException('Reponse does not contains terms of service information');
    }

    /**
     * Sign an array of parameters using provided keys and nonce
     *
     * @param array   $params
     * @param string  $privateKey
     * @param string  $publicKey
     * @param string  $nonce
     *
     * @return string Json encoded signed params
     *
     * @throws \InvalidArgumentException
     */
    protected function signParams(array $params, $privateKey, $publicKey, $nonce)
    {
        if (empty($nonce)) {
            throw new \InvalidArgumentException('Empty nonce provided');
        }

        $RsaPublicKey = $this->getRsa();
        $RsaPublicKey->loadKey($publicKey);

        $jwt = new \JOSE_JWT($params);
        $jwt->header['jwk'] = \JOSE_JWK::encode($RsaPublicKey)->components;
        $jwt->header['nonce'] = $nonce;

        // as of 20151203, boulder doesn't support SHA512
        return $jwt->sign($privateKey, 'RS256')->toJson();
    }

    /**
     * Send a signed post request
     *
     * @param string $url
     * @param array  $params
     *
     * @return \Guzzle\Http\Message\Response
     *
     * @throws ApiCallErrorException
     */
    protected function sendPostRequest($url, $params, $privateKey, $publicKey)
    {
        $signedParams = $this->signParams($params, $privateKey, $publicKey, $this->nonce);

        try {

            $response =  $this->guzzle->post(
                $url,
                null,
                $signedParams
            )->send();

            return $this->processResponse($response);

        } catch (\Exception $e) {

            $error = $e->getMessage();

            if ($e instanceof \Guzzle\Http\Exception\HttpException) {
                $errorDetails = json_decode($e->getResponse()->getBody(true), true);
                $error .= "\n[detail] ".$errorDetails['detail'];

                $this->processResponse($e->getResponse());
            }

            throw new ApiCallErrorException($error, 2);
        }
    }

    /**
     * Process response
     *
     * @param \Guzzle\Http\Message\Response $response
     *
     * @return \Guzzle\Http\Message\Response
     *
     * @throws ApiBadResponseException
     */
    protected function processResponse(\Guzzle\Http\Message\Response $response)
    {
        // Get the nonce from headers
        $headers = $response->getHeaders();
        if (!$headers->offsetExists('replay-nonce')) {
            throw new ApiBadResponseException('Response does not contain "replay-nonce" header', 16);
        }

        // Update the nonce in DB & memory
        $nonce = (string) $headers->get('replay-nonce');

        $this->storage->updateNonce($nonce);
        $this->nonce = $nonce;

        return $response;
    }

    /**
     * Get url for a given operation
     *
     * @param string $operation
     *
     * @return string
     *
     * @throws WrongOperationException
     */
    protected function getUrl($operation)
    {
        if (!array_key_exists($operation, $this->endPoints)) {
            throw new WrongOperationException(sprintf('Unknown operation : %s', $operation), 16);
        }

        return $this->endPoints[$operation];
    }

    /**
     * Set endPoints
     *
     * @param array $endPoints
     *
     * @return $this
     */
    public function setEndPoints(array $endPoints)
    {
        $this->endPoints = $endPoints;

        return $this;
    }

    /**
     * Call RSA closure to get a new RSA instance
     *
     * @return \phpseclib\Crypt\RSA
     */
    public function getRsa()
    {
        return $this->rsa->__invoke();
    }

    /**
     * Set nonce
     *
     * @param string $nonce
     *
     * @return $this
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }
}
