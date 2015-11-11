<?php

/**
 * Test case for the enumApi call and nonce management
 * @coversDefaultClass \Octopuce\Acme\Client
 * @author benjamin
 */
class enumApiTest extends PHPUnit_Framework_TestCase {

    /**
     * doesn't depends on any db status apart from a properly inserted schema
     * @covers ::enumApi
     * @global type $client
     */
    function testEnumApi() {
        global $client,$storage;

        $before = $storage->getStatus();
        $result = $client->enumApi();
        $after = $storage->getStatus();

        $this->assertNotEquals($before["nonce"],$after["nonce"]);
        $this->assertArrayHasKey('new-authz', $result);
        $this->assertArrayHasKey('new-cert', $result);
        $this->assertArrayHasKey('new-reg', $result);
        $this->assertArrayHasKey('revoke-cert', $result);
    }

    
}
