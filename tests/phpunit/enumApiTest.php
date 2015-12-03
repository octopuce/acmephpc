<?php

/*
 * This file is part of the ACME PHP Client Library 
 * (C) 2015 Benjamin Sonntag <benjamin@octopuce.fr>
 * distributed under LPGL 2.1+ see LICENSE file
 */
namespace Octopuce\Acme\Test;

/**
 * Test case for the enumApi call and nonce management
 * @coversDefaultClass \Octopuce\Acme\Client
 * @author benjamin
 */
class enumApiTest extends acmeTestCase {

    /**
     * doesn't depends on any db status apart from a properly inserted schema
     * @covers ::enumApi
     * @global type $client
     */
    function testEnumApi() {
        $before = $this->storage->getStatus();
        $result = $this->client->enumApi();
        $after = $this->storage->getStatus();

        $this->assertNotEquals($before["nonce"],$after["nonce"]);
        $this->assertArrayHasKey('new-authz', $result);
        $this->assertArrayHasKey('new-cert', $result);
        $this->assertArrayHasKey('new-reg', $result);
        $this->assertArrayHasKey('revoke-cert', $result);
    }
    
}
