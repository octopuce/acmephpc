<?php

namespace Octopuce\Tests\Acme\Http;

use Octopuce\Acme\Http\GuzzleClient;
use Octopuce\Acme\Ssl\PhpSecLib;

class GuzzleClientTest extends \PHPUnit_Framework_TestCase
{
    protected $client;
    protected $guzzleMock;
    protected $requestMock;
    protected $responseMock;
    protected $storageMock;

    protected static $keys = array();
    protected static $rsa;

    public static function setUpBeforeClass()
    {
        self::$rsa = function () {
            return new \phpseclib\Crypt\RSA;
        };

        $ssl = new PhpSecLib(self::$rsa);
        self::$keys = $ssl->generateRsaKey();
    }

    public function setUp()
    {
        $this->responseMock = $this->getMockBuilder('\Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getHeaders', 'get', 'offsetExists'))
            ->getMock();
        $this->responseMock->expects($this->any())
            ->method('getHeaders')
            ->will($this->returnSelf());

        $this->requestMock = $this->getMock('\Guzzle\Http\Message\RequestInterface');
        $this->requestMock->expects($this->any())
            ->method('send')
            ->will($this->returnValue($this->responseMock));

        $this->guzzleMock = $this->getMockBuilder('\Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->guzzleMock->expects($this->any())
            ->method('post')
            ->will($this->returnValue($this->requestMock));

        $this->storageMock = $this->getMockBuilder('\Octopuce\Acme\Storage\StorageInterface')
            ->setMethods(array('updateNonce'))
            ->disableOriginalConstructor()
            ->getMock();
        $this->storageMock->expects($this->any())
            ->method('updateNonce')
            ->will($this->returnSelf());

        $this->client = new GuzzleClient($this->guzzleMock, self::$rsa, $this->storageMock);
        $this->client
            ->setEndPoints(array('new-reg' => '/'))
            ->setNonce('dummy');
    }

    public function testEnumerate()
    {
        $this->guzzleMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->requestMock));

        $this->requestMock->expects($this->once())
            ->method('send');

        $this->responseMock->expects($this->once())
            ->method('offsetExists')
            ->will($this->returnValue(true));
        $this->responseMock->expects($this->exactly(2))
            ->method('getHeaders');

        $this->client->enumerate('/api');
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\ApiCallErrorException
     * @expectedExceptionCode 2
     */
    public function testEnumerateHttpException()
    {
        $this->guzzleMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->requestMock));

        $this->requestMock->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('boo')));

        $this->client->enumerate('/api');
    }

    public function testRegisterNewAccount()
    {
        $this->guzzleMock->expects($this->once())
            ->method('post');

        $this->requestMock->expects($this->once())
            ->method('send');

        $this->responseMock->expects($this->atLeastOnce())
            ->method('offsetExists')
            ->will($this->returnValue(true));
        $this->responseMock->expects($this->exactly(3))
            ->method('getHeaders');
        $this->responseMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue('dummy'));

        $this->client->registerNewAccount('me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\ApiBadResponseException
     * @expectedExceptionCode 3
     */
    public function testRegisterNewAccountApiBadResponseException()
    {
        $this->guzzleMock->expects($this->once())
            ->method('post');

        $this->requestMock->expects($this->once())
            ->method('send');

        $this->responseMock->expects($this->atLeastOnce())
            ->method('offsetExists')
            ->will($this->returnValue(false)); // This will raise the exception

        $this->client->registerNewAccount('me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\ApiCallErrorException
     * @expectedExceptionCode 2
     */
    public function testRegisterNewAccountHttpException()
    {
        $this->guzzleMock->expects($this->once())
            ->method('post');

        $this->requestMock->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('boo')));

        $this->client->registerNewAccount('me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    public function testSignContract()
    {
        $this->guzzleMock->expects($this->once())
            ->method('post');

        $this->requestMock->expects($this->once())
            ->method('send');

        $this->responseMock->expects($this->atLeastOnce())
            ->method('offsetExists')
            ->will($this->returnValue(true));
        $this->responseMock->expects($this->exactly(3))
            ->method('get')
            ->will($this->returnValue('<http://myregister.url>;rel="terms-of-service"'));

        $this->client->signContract($this->responseMock, 'me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\NoContractInResponseException
     */
    public function testSignContractNoContractException()
    {
        $this->guzzleMock->expects($this->never())
            ->method('post');
        $this->requestMock->expects($this->never())
            ->method('send');

        $this->responseMock->expects($this->atLeastOnce())
            ->method('offsetExists')
            ->will($this->returnValue(false));

        $this->client->signContract($this->responseMock, 'me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\NoContractInResponseException
     */
    public function testSignContractBadContractInfoException()
    {
        $this->guzzleMock->expects($this->never())
            ->method('post');
        $this->requestMock->expects($this->never())
            ->method('send');

        $this->responseMock->expects($this->atLeastOnce())
            ->method('offsetExists')
            ->will($this->returnValue(true));
        $this->responseMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue('Invalid value in header'));

        $this->client->signContract($this->responseMock, 'me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\ApiCallErrorException
     * @expectedExceptionCode 2
     */
    public function testSignContractHttpException()
    {
        $this->guzzleMock->expects($this->once())
            ->method('post');

        $this->requestMock->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('boo')));

        $this->responseMock->expects($this->once())
            ->method('offsetExists')
            ->will($this->returnValue(true));
        $this->responseMock->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue('<http://myregister.url>;rel="terms-of-service"'));

        $this->client->signContract($this->responseMock, 'me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\ApiBadResponseException
     */
    public function testProcessResponseException()
    {
        $this->guzzleMock->expects($this->once())
            ->method('post');

        $this->requestMock->expects($this->once())
            ->method('send');

        $this->responseMock->expects($this->atLeastOnce())
            ->method('offsetExists')
            ->will($this->onConsecutiveCalls(true, false));
        $this->responseMock->expects($this->exactly(2))
            ->method('getHeaders');
        $this->responseMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue('dummy'));

        $this->client->registerNewAccount('me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }


    /**
     * @expectedException \Octopuce\Acme\Exception\WrongOperationException
     */
    public function testGetUrlException()
    {
        $this->client
            ->setEndPoints(array())
            ->registerNewAccount('me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSignParamsException()
    {
        $this->client
            ->setNonce('')
            ->registerNewAccount('me@you.com', '+123456789', self::$keys['privatekey'], self::$keys['publickey']);
    }
}

