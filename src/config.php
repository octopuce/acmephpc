<?php

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
            //'database' => array('dsn' => 'mysql://letsencrypt:le2015@localhost/letsencrypt'),
        ),
        'challenge' => array(
            'type' => 'http',
            'config' => array(
                // The target to store the file
                'target-path' => '/tmp/',
            ),
        ),
        // Default account to be used
        'account' => 'mathieu@solocreation.com',
    ),
);


return $config;
