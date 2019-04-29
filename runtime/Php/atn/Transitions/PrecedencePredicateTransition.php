<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\PrecedencePredicate;

class PrecedencePredicateTransition extends AbstractPredicateTransition
{
    public $serializationType;

    public $precedence;

    function __construct($target, $precedence)
    {
        parent::__construct($target);

        $this->serializationType = Transition::PRECEDENCE;
        $this->precedence = $precedence;
        $this->isEpsilon = true;
    }

    function matches($symbol, $minVocabSymbol, $maxVocabSymbol)
    {
        return false;
    }

    function getPredicate()
    {
        return new PrecedencePredicate($this->precedence);
    }

    function __toString()
    {
        return $this->precedence . " >= _p";
    }
}