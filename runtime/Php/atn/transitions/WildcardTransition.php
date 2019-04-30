<?php

namespace Antlr4\Atn\Transitions;

class WildcardTransition extends Transition
{
    public $serializationType;

    function __construct($target)
    {
        parent::__construct($target);

        $this->serializationType = Transition::WILDCARD;
    }

    function matches($symbol, $minVocabSymbol, $maxVocabSymbol)
    {
        return $symbol >= $minVocabSymbol && $symbol <= $maxVocabSymbol;
    }

    function __toString()
    {
        return ".";
    }
}