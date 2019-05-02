<?php

namespace Antlr4\Atn\Transitions;

class RuleTransition extends Transition
{
    public $serializationType;

    public $ruleIndex;
    public $precedence;
    public $followState;
    public $isEpsilon;

    function __construct($ruleStart, $ruleIndex, $precedence, $followState)
    {
        parent::__construct($ruleStart);

        $this->ruleIndex = $ruleIndex;// ptr to the rule definition object for this rule ref
        $this->precedence = $precedence;
        $this->followState = $followState;// what node to begin computations following ref to rule
        $this->serializationType = Transition::RULE;
        $this->isEpsilon = true;
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return false;
    }
}