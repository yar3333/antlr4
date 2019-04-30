<?php

namespace Antlr4\Atn\Transitions;

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

    function __construct($target, $ruleIndex, $actionIndex, $isCtxDependent)
    {
        parent::__construct($target);

        $this->serializationType = Transition::ACTION;
        $this->ruleIndex = $ruleIndex;
        $this->actionIndex = $actionIndex === null ? -1 : $actionIndex;
        $this->isCtxDependent = $isCtxDependent === null ? false : $isCtxDependent;// e.g., $i ref in pred
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