<?php

$autoload = require __DIR__.'/../vendor/autoload.php';

$config = require __DIR__.'/../src/config.php';

// Get client
$client = new \Octopuce\Acme\Client($config);

// Make your calls !

// Works but needed only if no default account in config
// $client->newAccount('test107@sonntag.fr');

// Works but needed only if no default account in config
// $client->loadAccount('test107@sonntag.fr');

// Works
// $client->newOwnership('sonntag.fr');

// Works
// $client->getChallengeData('sonntag.fr');
// $client->getChallengeData('sonntag.fr', 'http-01'); /// Can override challenge type for each call

// Works
// $client->challengeOwnership('sonntag.fr');

// Works
// $client->signCertificate('sonntag.fr');

// Works
// $client->getCertificate('sonntag.fr');

// Works
// $client->revokeCertificate('sonntag.fr');
