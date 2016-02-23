<?php

namespace Octopuce\Acme\ChallengeSolver;

/**
 * Solver interface
 */
interface SolverInterface
{
    /**
     * Solve challenge
     *
     * @param string $token
     * @param string $publicKey
     *
     * @return bool
     */
    public function solve($token, $publicKey);

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Get challenge info as text
     *
     * @param string $fqdn
     * @param string $token
     * @param string $publicKey
     *
     * @return array
     */
    public function getChallengeInfo($fqdn, $token, $publicKey);
}
