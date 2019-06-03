<?php

namespace Antlr4\Atn\Semanticcontexts;

use Antlr4\Recognizer;
use Antlr4\RuleContext;
use Antlr4\Utils\Hash;

class SemanticContextPredicate extends SemanticContext
{
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

    function __construct(int $ruleIndex = -1, int $predIndex = -1, bool $isCtxDependent = false)
    {
        parent::__construct();

        $this->ruleIndex = $ruleIndex;
        $this->predIndex = $predIndex;
        $this->isCtxDependent = $isCtxDependent;
    }

    function eval(Recognizer $parser, RuleContext $outerContext) : bool
    {
        $localctx = $this->isCtxDependent ? $outerContext : null;
        return $parser->sempred($localctx, $this->ruleIndex, $this->predIndex);
    }

    function updateHashCode(Hash $hash) : void
    {
        $hash->update($this->ruleIndex, $this->predIndex, $this->isCtxDependent);
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof SemanticContextPredicate)) return false;
        return $this->ruleIndex === $other->ruleIndex &&
            $this->predIndex === $other->predIndex &&
            $this->isCtxDependent === $other->isCtxDependent;
    }

    function __toString()
    {
        return "{" . $this->ruleIndex . ":" . $this->predIndex . "}?";
    }
}