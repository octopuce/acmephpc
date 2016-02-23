<?php

namespace Octopuce\Acme;

use Octopuce\Acme\ChallengeSolver\SolverInterface;

interface OwnershipInterface
{
    /**
     * Register a new ownership
     *
     * @param string  $fqdn    Domain name
     *
     * @return self
     */
    public function register($fqdn);

    /**
     * Challenge
     *
     * @param SolverInterface $solver
     * @param string          $fqdn
     *
     * @return self
     *
     * @throws \UnexpectedValueException
     * @throws \Octopuce\Acme\Exception\ChallengeFailException
     */
    public function challenge(SolverInterface $solver, $fqdn);
}
