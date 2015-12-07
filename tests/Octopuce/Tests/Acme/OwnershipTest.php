<?php

namespace Octopuce\Tests\Acme;

class OwnershipTest extends \PHPUnit_Framework_TestCase
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
            ->setMethods(array('registerNewOwnerShip', 'getBody', 'getHeaders', 'get', 'offsetExists'))
            ->getMock();

        $this->sslMock = $this->getMockBuilder('\Octopuce\Acme\Ssl\SslInterface')->getMock();
    }

    public function testRegister()
    {
        $this->clientMock
            ->expects($this->once())
            ->method('registerNewOwnership')
            ->will($this->returnSelf());
        $this->clientMock
            ->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnSelf());


        $ownership = new \Octopuce\Acme\Ownership($this->storageMock, $this->clientMock, $this->sslMock);
        $ownership
            ->setKeys('private', 'public')
            ->register('my-domain.tld');
    }
}