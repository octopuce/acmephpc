<?php

namespace Octopuce\Tests\Acme;

class AccountTest extends \PHPUnit_Framework_TestCase
{
    protected $storageMock;
    protected $clientMock;
    protected $sslMock;

    public function setUp()
    {
        $this->storageMock = $this->getMockBuilder('\Octopuce\Acme\Storage\StorageInterface')
            ->getMock();

        $this->clientMock = $this->getMockBuilder('\Octopuce\Acme\Http\HttpClientInterface')
            ->getMock();

        $this->sslMock = $this->getMockBuilder('\Octopuce\Acme\Ssl\SslInterface')
            ->getMock();
    }

    public function testRegister()
    {
        $responseMock = $this->getMockBuilder('\Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getHeaders', 'get', 'offsetExists', 'getBody'))
            ->getMock();

        $responseMock->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnSelf());
        $responseMock->expects($this->once())
            ->method('offsetExists')
            ->will($this->returnValue(true));

        $this->storageMock
            ->expects($this->exactly(3))
            ->method('save')
            ->will($this->returnSelf());

        $this->clientMock
            ->expects($this->once())
            ->method('registerNewAccount')
            ->will($this->returnValue($responseMock));

        $this->clientMock
            ->expects($this->once())
            ->method('signContract')
            ->will($this->returnSelf());

        $account = new \Octopuce\Acme\Account($this->storageMock, $this->clientMock, $this->sslMock);
        $account
            ->setKeys('private', 'public')
            ->register('dummy', 'dummy');
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\InvalidRegisterArgumentException
     */
    public function testRegisterException()
    {
        $account = new \Octopuce\Acme\Account($this->storageMock, $this->clientMock, $this->sslMock);

        $account->register('', '', '', '', '');
    }


}

