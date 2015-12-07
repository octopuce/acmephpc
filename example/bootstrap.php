<?php

$autoload = require __DIR__.'/../vendor/autoload.php';

/**
 * Define configuration or override services here
 */
$config = array(
    'params' => array(
        'database' => 'mysql://letsencrypt:le2015@localhost/letsencrypt',
        'api' => 'https://acme-staging.api.letsencrypt.org',
        'challenge' => array(
            'type' => 'http',
            'config' => array(
                // The target to store the file
                'doc-root' => '/tmp/',
            ),
        ),
    ),
);

// Get client
$client = new \Octopuce\Acme\Client($config);

// Make your calls !
$client->newAccount('test107@sonntag.fr');

// $client->newOwnership(23, 'sonntag.fr');

//$client->challengeOwnership(23, 'sonntag.fr');