<?php

namespace Octopuce\Acme\Http;

interface HttpClientInterface
{
    /**
     * Register new account
     *
     * @param string $mailto
     * @param string $tel
     * @param string $privateKey
     * @param string $publicKey
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function registerNewAccount($mailto, $tel, $privateKey, $publicKey);

    /**
     * Sign a contract using a previous registration response
     *
     * @param \Guzzle\Http\Message\Response  $response
     * @param string                         $mailto
     * @param string                         $tel
     * @param string                         $privateKey
     * @param string                         $publicKey
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function signContract($response, $mailto, $tel, $privateKey, $publicKey);

    /**
     * Register new ownership
     *
     * @param string $value
     * @param string $type
     * @param string $privateKey
     * @param string $publicKey
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function registerNewOwnership($value, $type, $privateKey, $publicKey);

    /**
     * Challenge Ownership
     *
     * @param string $url
     * @param string $type
     * @param string $keyAuth
     * @param string $privateKey
     * @param string $publicKey
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function challengeOwnership($url, $type, $keyAuth, $privateKey, $publicKey);

    /**
     * Request signing a certificate
     *
     * @param string $dercsr
     * @param string $privateKey
     * @param string $publicKey
     *
     * @return string
     */
    public function signCertificate($dercsr, $privateKey, $publicKey);
}
