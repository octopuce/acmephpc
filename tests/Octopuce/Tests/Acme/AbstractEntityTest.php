<?php

namespace Octopuce\Tests\Acme;

class AbstractEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider badDomainProvider
     * @expectedException Octopuce\Acme\Exception\InvalidDomainException
     */
    public function testCheckFqdnExceptions($fqdn)
    {
        $concreteClass = new ConcreteEntity;
        $concreteClass->doFqdnTest($fqdn);
    }

    public function badDomainProvider()
    {
        return array(
            array('mydomain'),
            array('invalid,domain'),
            array('yet-another'),
        );
    }

    /**
     * @dataProvider goodDomainProvider
     */
    public function testCheckFqdn($fqdn)
    {
        $concreteClass = new ConcreteEntity;
        $concreteClass->doFqdnTest($fqdn);
    }

    public function goodDomainProvider()
    {
        return array(
            array('fdn.fr'),
            array('co.uk'),
            array('my.css'),
            array('along-with.another.domain.or'),
        );
    }
}

// Concrete class for fqdn tests purpose
class ConcreteEntity extends \Octopuce\Acme\AbstractEntity
{
    public function __construct()
    {
        // Disable constructor to avoid useless dependencies
    }

    public function doFqdnTest($fqdn)
    {
        return $this->checkFqdn($fqdn);
    }
}
