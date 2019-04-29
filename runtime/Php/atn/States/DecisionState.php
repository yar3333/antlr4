<?php

namespace Antlr4\Atn\States;

class DecisionState extends ATNState
{
    /**
     * @var int
     */
    public $decision;

    /**
     * @var bool
     */
    public $nonGreedy;

    function __construct()
    {
        parent::__construct();

        $this->decision = -1;
        $this->nonGreedy = false;
    }
}