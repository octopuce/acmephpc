<?php

namespace Octopuce\Acme\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Certificate extends AbstractCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('certificate')
            ->setDescription('Certificate operations')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Desired action get|sign|revoke|renew'
            )
            ->addArgument(
                'fqdn',
                InputArgument::REQUIRED,
                'Fully qualified domain name'
            )
            ->addArgument(
                'altnames',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Alternative names for certificate (separate multiple with a space)'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = require $input->getOption('config');
        $client = $this->getClient($config);

        $fqdn = $input->getArgument('fqdn');

        switch ($input->getArgument('action'))
        {
            case 'get':
                $text = $client->getCertificate($fqdn);
                break;

            case 'sign':
                $text = $client->signCertificate($fqdn, $input->getArgument('altnames'));
                break;

            case 'revoke':
                $text = $client->revokeCertificate($fqdn);
                break;

            case 'renew':
                break;

            default:
                throw new \ÌnvalidArgmentException('action must be get, sign, revoke or renew');
        }

        $output->writeln($text);
    }
}
