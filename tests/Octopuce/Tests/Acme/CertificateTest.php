<?php

namespace Octopuce\Tests\Acme;

class CertificateTest extends \PHPUnit_Framework_TestCase
{
    protected $storageMock;
    protected $clientMock;
    protected $sslMock;

    public function setUp()
    {
        $this->storageMock = $this->getMockBuilder('\Octopuce\Acme\Storage\DoctrineDbal')
            ->setMethods(array('save'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientMock = $this->getMockBuilder('\Octopuce\Acme\Http\HttpClientInterface')
            ->setMethods(array('signCertificate'))
            ->getMock();

        $this->sslMock = $this->getMockBuilder('\Octopuce\Acme\Ssl\SslInterface')->getMock();
    }

    public function testSign()
    {
        $responseMock = $this->getMockBuilder('\Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getHeaders', 'get', 'offsetExists', 'getBody'))
            ->getMock();

        $this->clientMock
            ->expects($this->once())
            ->method('signCertificate')
            ->will($this->returnValue($responseMock));

        $certificate = new \Octopuce\Acme\Certificate($this->storageMock, $this->clientMock, $this->sslMock);
        $certificate
            ->setKeys('private', 'public')
            ->sign('my-domain.tld', array('sub.my-domain.tld'));
    }
}
