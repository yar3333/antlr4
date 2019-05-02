<?php

namespace Antlr4\Atn\Semanticcontexts;

use Antlr4\Recognizer;
use Antlr4\RuleContext;
use Antlr4\Utils\Hash;
use Antlr4\Utils\Set;

// A semantic context which is true whenever at least one of the contained contexts is true.
class SemanticContextOr extends SemanticContext
{
    /**
     * @var SemanticContext[]
     */
    public $opnds;

    function __construct($a, $b)
    {
        parent::__construct();

        $operands = new Set();
        if ($a instanceof SemanticContextOr) {
            foreach ($a->opnds as $o) {
                $operands->add($o);
            }
        } else {
            $operands->add($a);
        }
        if ($b instanceof SemanticContextOr) {
            foreach ($b->opnds as $o) {
                $operands->add($o);
            }
        } else {
            $operands->add($b);
        }

        $precedencePredicates = PrecedencePredicate::filterPrecedencePredicates($operands);

        if ($precedencePredicates)
        {
            // interested in the transition with the highest precedence
            $s = usort($precedencePredicates, function (object $a, object $b) { return $a->compareTo($b); });
            $reduced = $s[$s->length - 1];
            $operands->add($reduced);
        }

        $this->opnds = $operands->values();

        return $this;
    }

    function constructor($other)
    {
        if ($this === $other)
        {
            return true;
        }
        else if (!($other instanceof SemanticContextOr))
        {
            return false;
        }
        else
        {
            return $this->opnds === $other->opnds;
        }
    }

    function updateHashCode(Hash $hash) : void
    {
        $hash->update($this->opnds, "OR");
    }

    /**
     * The evaluation of predicates by this context is short-circuiting, but unordered.
     * @param $parser
     * @param $outerContext
     * @return bool
     */
    function eval(Recognizer $parser, RuleContext $outerContext) : bool
    {
        foreach ($this->opnds as $opnd)
        {
            if ($opnd->eval($parser, $outerContext)) return true;
        }
        return false;
    }

    function evalPrecedence($parser, $outerContext)
    {
        $differs = false;
        $operands = [];
        foreach ($this->opnds as $context) {
            $evaluated = $context->evalPrecedence($parser, $outerContext);
            $differs |= ($evaluated !== $context);
            if ($evaluated === SemanticContext::NONE())
            {
                // The OR context is true if any element is true
                return SemanticContext::NONE();
            }
            else if ($evaluated !== null)
            {
                // Reduce the result by skipping false elements
                array_push($operands, $evaluated);
            }
        }
        if (!$differs) return $this;

        // all elements were false, so the OR context is false
        if (count($operands) === 0) return null;

        // TODO: BUGFIX????
        $result = null;
        foreach ($operands as $o) {
            //return result === null ? o : SemanticContext.orContext(result, o);
            $result = $result === null ? $o : SemanticContext::orContext($result, $o);
        }
        return $result;
    }

    function __toString()
    {
        $s = "";
        foreach ($this->opnds as $o) $s .= "|| " . $o;
        return strlen($s) > 3 ? substr($s, 3) : $s;
    }
}