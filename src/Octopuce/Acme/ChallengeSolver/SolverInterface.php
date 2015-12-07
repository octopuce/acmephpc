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
}