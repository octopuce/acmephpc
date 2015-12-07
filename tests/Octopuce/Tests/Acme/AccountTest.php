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
            ->setMethods(array('save'))
            ->getMock();

        $this->clientMock = $this->getMockBuilder('\Octopuce\Acme\Http\HttpClientInterface')
            ->setMethods(array('registerNewAccount', 'signContract'))
            ->getMock();

        $this->sslMock = $this->getMockBuilder('\Octopuce\Acme\Ssl\SslInterface')->getMock();
    }

    public function testRegister()
    {
        $this->storageMock
            ->expects($this->exactly(3))
            ->method('save')
            ->will($this->returnSelf());

        $this->clientMock
            ->expects($this->once())
            ->method('registerNewAccount')
            ->will($this->returnSelf());

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

