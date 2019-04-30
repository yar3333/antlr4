<?php

namespace Antlr4\Atn\Transitions;

class AbstractPredicateTransition extends Transition
{
    public $serializationType;

    function __construct($target)
    {
        parent::__construct($target);
    }
}