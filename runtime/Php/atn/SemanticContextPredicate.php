<?php

namespace Antlr4\Atn;

use Antlr4\Parser;
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

    function __construct($ruleIndex = null, $predIndex = null, $isCtxDependent = null)
    {
        parent::__construct();

        $this->ruleIndex = !isset($ruleIndex) ? -1 : $ruleIndex;
        $this->predIndex = !isset($predIndex) ? -1 : $predIndex;
        $this->isCtxDependent = !isset($isCtxDependent) ? false : $isCtxDependent; // e.g., $i ref in pred
    }

    function evaluate(Parser $parser, $outerContext)
    {
        $localctx = $this->isCtxDependent ? $outerContext : null;
        return $parser->sempred($localctx, $this->ruleIndex, $this->predIndex);
    }

    function updateHashCode(Hash $hash)
    {
        $hash->update($this->ruleIndex, $this->predIndex, $this->isCtxDependent);
    }

    function equals($other)
    {
        if ($this === $other)
        {
            return true;
        }
        else if (!($other instanceof SemanticContextPredicate))
        {
            return false;
        }
        else
        {
            return $this->ruleIndex === $other->ruleIndex &&
                $this->predIndex === $other->predIndex &&
                $this->isCtxDependent === $other->isCtxDependent;
        }
    }

    function __toString()
    {
        return "{" . $this->ruleIndex . ":" . $this->predIndex . "}?";
    }
}