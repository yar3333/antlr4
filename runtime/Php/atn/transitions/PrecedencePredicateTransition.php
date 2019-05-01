<?php

namespace Antlr4\Atn\Transitions;


use Antlr4\Atn\Semanticcontexts\PrecedencePredicate;

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

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }

    function getPredicate() : PrecedencePredicate
    {
        return new PrecedencePredicate($this->precedence);
    }

    function __toString()
    {
        return $this->precedence . " >= _p";
    }
}