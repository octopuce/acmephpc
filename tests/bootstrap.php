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
require_once("StorageTest.php");

require_once("../ValidationPluginInterface.php");
require_once("ValidationTest.php");

require_once("../AcmeException.php");


use \Octopuce\Acme;

// Create an instance of the Client Library, including the TestPlugins and TestInterfaces

$validator=new ValidationTest();
$storage = new StorageTest();
$httpclient=new HttpClientTest();

// SSL Security library (here using phpseclib, since we need it anyway...)
$ssl = new Acme\SslPhpseclib();

/*
  // Connect to the MySQL DB
try {
    $storage = new Acme\StoragePdo($db_dsn, $db_user, $db_pass);
} catch (PDOException $e) {
    echo "Fatal error connecting to the database : " . $e->getMessage() . "\n";
}
*/

$storage = new Acme\StoragePdo("sqlite:/tmp/acmephp-tests.".getmypid().".sqlite");
   
// And Instance the ACME PHP Client:
$client = new Acme\Client($apiroot, $storage, $httpclient, null, $ssl);
// and add simpleHTTP validation plugin
$client->setPlugin($validator);


