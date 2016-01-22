<?php

$autoload = require __DIR__.'/../vendor/autoload.php';

/**
 * Define configuration or override services here
 */
$config = array(
    'params' => array(
        'api' => 'https://acme-staging.api.letsencrypt.org',
        'storage' => array(
            // The storage type to use
            'type' => 'filesystem',
            // Storage config
            'filesystem' => __DIR__.'/../storage',
            'database' => array('dsn' => 'mysql://letsencrypt:le2015@localhost/letsencrypt'),
        ),
        'challenge' => array(
            'type' => 'http',
            'config' => array(
                // The target to store the file
                'target-path' => '/tmp/',
            ),
        ),
        // Default account to be used
        'account' => 'me@you.com',
    ),
);


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
