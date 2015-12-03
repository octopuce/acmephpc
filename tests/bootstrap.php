<?php

/*
 * Bootstrap for phpunit test cases
 * autoload the composer-required-classes & the acmephp classes
 * create a new instance of the client to be used in tests 
 */

namespace Octopuce\Acme\Test;

require_once("../vendor/autoload.php");

// in normal mode, the classes below would be loaded by using use Octopuce\Acme thanks to autoloader
// no require_once should be necessary.
// main Acme Library
require_once("../Client.php");

require_once("../HttpClientInterface.php");
require_once("HttpClientTest.php");

require_once("../SslInterface.php");
require_once("../SslPhpseclib.php");

require_once("../StorageInterface.php");
require_once("../StoragePdo.php");

require_once("../ValidationPluginInterface.php");
require_once("ValidationTest.php");

require_once("../AcmeException.php");

use \Octopuce\Acme;

class acmeTestCase extends \PHPUnit_Framework_TestCase {

    public $storage;
    public $client;
    
    function __construct() {
        // We create a dummy sqlite database in memory and fill it with our schema :
        $this->storage = new Acme\StoragePdo("sqlite::memory:");
        // Inject the base schema : 
        $f = fopen(__DIR__ . "/../acmephp.sqlite.sql", "rb");
        $query = "";
        while ($s = fgets($f, 1024)) {
            $s = trim($s);
            if (substr($s, 0, 2) == "--") {
                continue;
            }
            $query.=$s . "\n";
            if (substr($s, -1) == ";") {
                $this->storage->query($query);
                $query = "";
            }
        }
        fclose($f);

        $validator = new ValidationTest();
        $httpclient = new HttpClientTest();
        $apiroot = "/";
        $this->client = new Acme\Client($apiroot, $this->storage, $httpclient);
        // add simpleHTTP validation plugin
        $this->client->setPlugin($validator);
    }

}

// initialization of the schema of the database will be done when necessary
//init_schema();
