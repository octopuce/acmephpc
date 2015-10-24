<?php

/*
 * Bootstrap for acme-php-client example code
 * autoload the composer-required-classes & the acmephp classes
 * create a new instance of the client to be used later  
 */

namespace Octopuce\Acme\example;

require_once("config.php");

require_once("../vendor/autoload.php");

require_once("../Client.php");

require_once("../HttpClientInterface.php");
require_once("../HttpClientCurl.php");

require_once("../SslInterface.php");
require_once("../SslPhpseclib.php");

require_once("../StorageInterface.php");
require_once("../StoragePdo.php");

require_once("../ValidationPluginInterface.php");
require_once("../ValidationApache.php");

use \Octopuce\Acme;

// Create an instance of the Client Library, including the TestPlugins and TestInterfaces

$validator = new Acme\ValidationApache();
$httpclient = new Acme\HttpClientCurl();
$ssl = new Acme\SslPhpseclib();

try {
    $storage = new Acme\StoragePdo($db_dsn, $db_user, $db_pass);
} catch (PDOException $e) {
    echo "Fatal error connecting to the database : " . $e->getMessage() . "\n";
}

$client = new Acme\Client($apiroot, $storage, $httpclient, null, $ssl);

