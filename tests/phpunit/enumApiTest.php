<?php

/**
 * Test case for the enumApi call and nonce management
 * @coversDefaultClass \Octopuce\Acme\Client
 * @author benjamin
 */
class enumApiTest extends PHPUnit_Framework_TestCase {

    /**
     * @covers ::enumApi
     * @global type $client
     */
    function testEnumApi() {
        global $client;
        $result = $client->enumApi();
        $this->assertArrayHasKey('new-authz', $result);
        $this->assertArrayHasKey('new-cert', $result);
        $this->assertArrayHasKey('new-reg', $result);
        $this->assertArrayHasKey('revoke-cert', $result);
    }

}
