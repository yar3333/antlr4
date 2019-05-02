<?php

namespace Antlr4\Atn\Transitions;

class EpsilonTransition extends Transition
{
    public $serializationType;

    public $outermostPrecedenceReturn;

    function __construct($target, $outermostPrecedenceReturn=null)
    {
        parent::__construct($target);

        $this->serializationType = Transition::EPSILON;
        $this->isEpsilon = true;
        $this->outermostPrecedenceReturn = $outermostPrecedenceReturn;
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }

    function __toString()
    {
        return "epsilon";
    }
}