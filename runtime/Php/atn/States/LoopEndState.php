<?php

namespace Antlr4\Atn\States;

class LoopEndState extends ATNState
{
    public $loopBackState;

    function __construct()
    {
        parent::__construct();

        $this->stateType = ATNState::LOOP_END;

        $this->loopBackState = null;
    }
}