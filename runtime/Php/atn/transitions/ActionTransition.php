<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Atn\States\ATNState;

class ActionTransition extends Transition
{
    /**
     * @var int
     */
    public $serializationType;

    /**
     * @var int
     */
    public $ruleIndex;

    /**
     * @var int
     */
    public $actionIndex;

    /**
     * @var bool
     */
    public $isCtxDependent;

    function __construct(ATNState $target, int $ruleIndex, int $actionIndex=null, bool $isCtxDependent=false)
    {
        parent::__construct($target);

        $this->serializationType = Transition::ACTION;
        $this->ruleIndex = $ruleIndex;
        $this->actionIndex = $actionIndex ?? -1;
        $this->isCtxDependent = $isCtxDependent ?? false;
        $this->isEpsilon = true;
    }

    function matches($symbol, $minVocabSymbol, $maxVocabSymbol) : bool
    {
        return false;
    }

    function __toString()
    {
        return "action_" . $this->ruleIndex . ":" . $this->actionIndex;
    }
}