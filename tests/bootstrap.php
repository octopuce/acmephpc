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

// Create an instance of the Client Library, including the TestPlugins and TestInterfaces

$validator=new ValidationTest();
$httpclient=new HttpClientTest();

// SSL Security library (here using phpseclib, since we need it anyway...)
$ssl = new Acme\SslPhpseclib();

// We create a dummy sqlite database in memory and fill it with our schema :
$storage = new Acme\StoragePdo("sqlite::memory:");

// Inject the base schema : 
$f=fopen("../acmephp.sqlite.sql","rb");
$query="";
while ($s=fgets($f,1024)) {
    $s=trim($s);
    if (substr($s,0,2)=="--") {
        continue;
    }
    $query.=$s."\n";
    if (substr($s,-1)==";") {
        $storage->query($query);
        $query="";
    }
}
fclose($f);

// our http client is dummy anyway ;) 
$apiroot="/";

// Instance the ACME PHP Client:
$client = new Acme\Client($apiroot, $storage, $httpclient, null, $ssl);

// add simpleHTTP validation plugin
$client->setPlugin($validator);
