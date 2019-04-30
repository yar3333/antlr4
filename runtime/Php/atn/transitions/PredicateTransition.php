<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Predicate;

class PredicateTransition extends AbstractPredicateTransition
{
    public $serializationType;

    /**
     * @var int
     */
    public $ruleIndex;

    /**
     * @var int
     */
    public $predIndex;

    /**
     * @var bool
     */
    public $isCtxDependent;

    function __construct($target, int $ruleIndex, int $predIndex, bool $isCtxDependent)
    {
        parent::__construct($target);

        $this->serializationType = Transition::PREDICATE;

        $this->ruleIndex = $ruleIndex;
        $this->predIndex = $predIndex;
        $this->isCtxDependent = $isCtxDependent;// e.g., $i ref in pred
        $this->isEpsilon = true;
    }

    function matches($symbol, $minVocabSymbol, $maxVocabSymbol)
    {
        return false;
    }

    function getPredicate()
    {
        return new Predicate($this->ruleIndex, $this->predIndex, $this->isCtxDependent);
    }

    function __toString()
    {
        return "pred_" . $this->ruleIndex . ":" . $this->predIndex;
    }
}