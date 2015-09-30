<?php

require_once("../vendor/autoload.php");

require_once("../SslInterface.php");
require_once("../SslPhpseclib.php");

use \Octopuce\Acme\SslPhpseclib;

$ssl=new SslPhpseclib();

$ssl->genRsa();
