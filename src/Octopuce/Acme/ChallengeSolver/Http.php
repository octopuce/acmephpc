<?php

namespace Octopuce\Acme\ChallengeSolver;

/**
 * HTTP Challenge solver for Apache / Nginx
 * Saves token information in a file at /.well-known/acme-challenge/{token} path
 */
class Http implements SolverInterface
{
    /**
     * Target path
     * @var string
     */
    private $targetPath;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->targetPath = $config['target-path'];
    }

    /**
     * Solve the challenge by placing a file in a web root folder
     *
     * @param string $token
     * @param string $publicKey
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function solve($token, $publicKey)
    {
        $targetFile = $this->targetPath.$token.'.txt';

        if (false === file_put_contents($targetFile, $token.$publicKey)) {
            throw new \RuntimeException(sprintf('Unable to write file %s', $targetFile));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return 'http-01';
    }
}
