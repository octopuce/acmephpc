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
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientMock = $this->getMockBuilder('\Octopuce\Acme\Http\HttpClientInterface')
            ->getMock();

        $this->sslMock = $this->getMockBuilder('\Octopuce\Acme\Ssl\SslInterface')
            ->getMock();
    }

    public function testRegister()
    {
        $responseMock  = $this->getMockBuilder('\Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getBody', 'getHeaders', 'offsetExists', 'get'))
            ->getMock();

        $responseMock
            ->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnSelf());

        $responseMock
            ->expects($this->once())
            ->method('offsetExists')
            ->will($this->returnValue(true));

        $responseMock
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue('dummy url'));

        $this->clientMock
            ->expects($this->once())
            ->method('registerNewOwnership')
            ->will($this->returnValue($responseMock));

        $this->storageMock
            ->expects($this->once())
            ->method('save');

        $ownership = new \Octopuce\Acme\Ownership($this->storageMock, $this->clientMock, $this->sslMock);
        $ownership
            ->setKeys('private', 'public')
            ->register('my-domain.tld');
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\OwnershipNotFoundException
     */
    public function testLoadByDomainException()
    {
        $solverMock = $this->getMockBuilder('\Octopuce\Acme\ChallengeSolver\SolverInterface')
            ->getMock();

        $ownership = new \Octopuce\Acme\Ownership($this->storageMock, $this->clientMock, $this->sslMock);
        $ownership
            ->setKeys('private', 'public')
            ->challenge($solverMock, 'my-domain.tld');
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testChallengeNotPendingException()
    {
        $ownershipData = array(
            'id'       => 1,
            'created'  => '',
            'modified' => '',
            'type'     => 'http-01',
            'value'    => 'value',
            'url'      => 'url',
            'challenges' => json_encode(array(
                array('type' => 'http-01', 'status' => 'notPending') // Bad status
            )),
        );

        $this->storageMock
            ->expects($this->once())
            ->method('findOwnershipByDomain')
            ->will($this->returnValue($ownershipData));

        $solverMock = $this->getMockBuilder('\Octopuce\Acme\ChallengeSolver\SolverInterface')
            ->getMock();
        $solverMock->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('http-01'));

        $ownership = new \Octopuce\Acme\Ownership($this->storageMock, $this->clientMock, $this->sslMock);
        $ownership
            ->setKeys('private', 'public')
            ->challenge($solverMock, 'my-domain.tld');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testChallengeRuntimeException()
    {
        $ownershipData = array(
            'id'       => 1,
            'created'  => '',
            'modified' => '',
            'type'     => 'http-01',
            'value'    => 'value',
            'url'      => 'url',
            'challenges' => json_encode(array()), // Won't match type
        );

        $this->storageMock
            ->expects($this->once())
            ->method('findOwnershipByDomain')
            ->will($this->returnValue($ownershipData));

        $solverMock = $this->getMockBuilder('\Octopuce\Acme\ChallengeSolver\SolverInterface')
            ->getMock();

        $ownership = new \Octopuce\Acme\Ownership($this->storageMock, $this->clientMock, $this->sslMock);
        $ownership
            ->setKeys('private', 'public')
            ->challenge($solverMock, 'my-domain.tld');
    }

    /**
     * @expectedException \Octopuce\Acme\Exception\ChallengeFailException
     */
    public function testChallengeSolverFailException()
    {
        $ownershipData = array(
            'id'       => 1,
            'created'  => '',
            'modified' => '',
            'type'     => 'http-01',
            'value'    => 'value',
            'url'      => 'url',
            'challenges' => json_encode(array(
                array('type' => 'http-01', 'status' => 'pending', 'token' => 'dummyToken', 'uri' => 'dummyUri')
            )),
        );

        $this->storageMock
            ->expects($this->once())
            ->method('findOwnershipByDomain')
            ->will($this->returnValue($ownershipData));

        $this->sslMock
            ->expects($this->once())
            ->method('getPublicKeyThumbprint')
            ->will($this->returnValue('dummy thumbprint'));

        $solverMock = $this->getMockBuilder('\Octopuce\Acme\ChallengeSolver\SolverInterface')
            ->getMock();
        $solverMock->expects($this->once())
            ->method('solve')
            ->will($this->returnValue(false)); // Challenge failed
        $solverMock->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('http-01'));

        $ownership = new \Octopuce\Acme\Ownership($this->storageMock, $this->clientMock, $this->sslMock);
        $ownership
            ->setKeys('private', 'public')
            ->challenge($solverMock, 'my-domain.tld');
    }

    public function testChallenge()
    {
        $ownershipData = array(
            'id'       => 1,
            'created'  => '',
            'modified' => '',
            'type'     => 'http-01',
            'value'    => 'value',
            'url'      => 'url',
            'challenges' => json_encode(array(
                array('type' => 'http-01', 'status' => 'pending', 'token' => 'dummyToken', 'uri' => 'dummyUri')
            )),
        );

        $this->storageMock
            ->expects($this->once())
            ->method('findOwnershipByDomain')
            ->will($this->returnValue($ownershipData));

        $this->sslMock
            ->expects($this->once())
            ->method('getPublicKeyThumbprint')
            ->will($this->returnValue('dummy thumbprint'));

        $solverMock = $this->getMockBuilder('\Octopuce\Acme\ChallengeSolver\SolverInterface')
            ->getMock();
        $solverMock->expects($this->once())
            ->method('solve')
            ->will($this->returnValue(true));
        $solverMock->expects($this->exactly(2))
            ->method('getType')
            ->will($this->returnValue('http-01'));

        $ownership = new \Octopuce\Acme\Ownership($this->storageMock, $this->clientMock, $this->sslMock);
        $ownership
            ->setKeys('private', 'public')
            ->challenge($solverMock, 'my-domain.tld');
    }
}
