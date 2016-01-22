<?php

namespace Octopuce\Acme\ChallengeSolver;

/**
 * HTTP Challenge solver for Apache / Nginx
 * Saves token information in a file at /.well-known/acme-challenge/{token} path
 */
class Http implements SolverInterface
{
    /**
     * File path
     */
    const FILE_PATH = '/.well-known/acme-challenge/';

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

        if (!preg_match('#\.well-known.acme-challenge#', $this->targetPath)) {

            $this->targetPath = sprintf(
                '%s'.str_replace('/', '%s', self::FILE_PATH),
                rtrim($this->targetPath, DIRECTORY_SEPARATOR),
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR
            );

        }
    }

    /**
     * Solve the challenge by placing a file in a web root folder
     *
     * @param string $token
     * @param string $key
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function solve($token, $key)
    {
        $this->createTargetDir($this->targetPath);

        $targetFile = $this->targetPath.$token;

        if (false === file_put_contents($targetFile, $token.'.'.$key)) {
            throw new \RuntimeException(sprintf('Unable to write file %s', $targetFile));
        }

        return true;
    }

    /**
     * Solve the challenge by placing a file in a web root folder
     *
     * @param string $fqdn
     * @param string $token
     * @param string $key
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    public function getChallengeInfo($fqdn, $token, $key)
    {
        $targetUrl = 'http://'.$fqdn.self::FILE_PATH.$token;

        return array(
            'info' => sprintf('Put the keyAuthorization value in a file accessible at url %s', $targetUrl),
            'keyAuthorization' => $token.'.'.$key,
        );
    }

    /**
     * Create target directory
     *
     * @param string $directory
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    private function createTargetDir($directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new \RuntimeException(sprintf('Unable to create target directory %s', $directory));
        }
    }


    /**
     * @inheritDoc
     */
    public function getType()
    {
        return 'http-01';
    }
}
