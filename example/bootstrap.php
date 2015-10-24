<?php

/*
 * Bootstrap for acme-php-client example code
 * autoload the composer-required-classes & the acmephp classes
 * create a new instance of the client to be used later  
 */

namespace Octopuce\Acme\example;

// This contains MySQL access & API URL
require_once("config.php");

// Autoload of phpseclib & JWT/JWK 
require_once("../vendor/autoload.php");

// in normal mode, the classes below would be loaded by using use Octopuce\Acme thanks to autoloader
// no require_once should be necessary.

// main Acme Library
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


// Create an instance of the Client Library, including its dependent classes: 

// SimpleHTTP validation plugin 
$validator = new Acme\ValidationApache();
// HTTP Client (here using php5-curl module)
$httpclient = new Acme\HttpClientCurl();
// SSL Security library (here using phpseclib, since we need it anyway...)
$ssl = new Acme\SslPhpseclib();

// Connect to the MySQL DB
try {
    $storage = new Acme\StoragePdo($db_dsn, $db_user, $db_pass);
} catch (PDOException $e) {
    echo "Fatal error connecting to the database : " . $e->getMessage() . "\n";
}

// And Instance the ACME PHP Client:
$client = new Acme\Client($apiroot, $storage, $httpclient, null, $ssl);
// and add simpleHTTP validation plugin
$client->setPlugin($validator);


