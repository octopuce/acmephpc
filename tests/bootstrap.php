<?php

/* 
 * Bootstrap for phpunit test cases
 * autoload the composer-required-classes & the acmephp classes
 * create a new instance of the client to be used in tests 
 */
namespace Octopuce\Acme\Test;

require_once("../vendor/autoload.php");

require_once("../Client.php");

require_once("ValidationTest.php");
require_once("StorageTest.php");
require_once("HttpClientTest.php");

// Create an instance of the Client Library, including the TestPlugins and TestInterfaces

$validator=new ValidationTest();
$storage = new StorageTest();
$httpclient=new HttpClientTest();


