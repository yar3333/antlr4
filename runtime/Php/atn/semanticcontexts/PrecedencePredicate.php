<?php

namespace Antlr4\Atn\Semanticcontexts;

use Antlr4\Atn\Semanticcontexts\SemanticContext;
use Antlr4\Recognizer;
use Antlr4\Utils\Set;

class PrecedencePredicate extends SemanticContext
{
    /**
     * @var int
     */
    public $precedence;

    function __construct($precedence)
    {
        parent::__construct();

        $this->precedence = !isset($precedence) ? 0 : $precedence;
    }

    function evaluate(Recognizer $parser, $outerContext)
    {
        return $parser->precpred($outerContext, $this->precedence);
    }

    function evalPrecedence(Recognizer $parser, $outerContext)
    {
        if ($parser->precpred($outerContext, $this->precedence)) {
            return SemanticContext::NONE();
        } else {
            return null;
        }
    }

    function compareTo(PrecedencePredicate $other) : int
    {
        return $this->precedence - $other->precedence;
    }

    function updateHashCode($hash) : void
    {
        $hash->update(31);
    }

    function equals(object $other) : bool
    {
        if ($this === $other) {
            return true;
        } else if (!($other instanceof PrecedencePredicate)) {
            return false;
        } else {
            return $this->precedence === $other->precedence;
        }
    }

    function __toString()
    {
        return "{" . $this->precedence . ">=prec}?";
    }

    /**
     * @param Set $set
     * @return SemanticContext[]
     */
    static function filterPrecedencePredicates(Set $set) : array
    {
        $result = [];
        foreach ($set->values() as $context) {
            if ($context instanceof PrecedencePredicate) {
                array_push($result, $context);
            }
        }
        return $result;
    }
}