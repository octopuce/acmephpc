<?php

namespace Octopuce\Acme\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Octopuce\Acme\Client;

abstract class AbstractCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Config file to be read',
            __DIR__.'/../../../config.php'
        );
    }

    /**
     * Get client instance
     *
     * @param array $config
     *
     * @return Client
     */
    protected function getClient(array $config)
    {
        return new Client($config);
    }
}
