<?php

namespace Antlr4\Atn\Semanticcontexts;

use Antlr4\Atn\Semanticcontexts\PrecedencePredicate;
use Antlr4\Atn\Semanticcontexts\SemanticContext;
use Antlr4\Utils\Hash;
use Antlr4\Utils\Set;

class SemanticContextAnd extends SemanticContext
{
    /**
     * @var SemanticContext[]
     */
    public $opnds;

    function __construct(SemanticContext $a, SemanticContext $b)
    {
        parent::__construct();

        $operands = new Set();

        if ($a instanceof SemanticContextAnd)
        {
            foreach ($a->opnds as $o)
            {
                $operands->add($o);
            }
        }
        else
        {
            $operands->add($a);
        }

        if ($b instanceof SemanticContextAnd)
        {
            foreach ($b->opnds as $o)
            {
                $operands->add($o);
            }
        }
        else
        {
            $operands->add($b);
        }

        /**
         * @var  SemanticContext[] $precedencePredicates
         */
        $precedencePredicates = PrecedencePredicate::filterPrecedencePredicates($operands);
        if ($precedencePredicates->length > 0)
        {
            // interested in the transition with the lowest precedence
            $reduced = null;
            foreach ($precedencePredicates as $p)
            {
                /**
                 * @var  SemanticContextAnd $p
                 */
                if ($reduced === null || $p->precedence < $reduced->precedence)
                {
                    $reduced = $p;
                }
            }
            $operands->add($reduced);
        }
        $this->opnds = $operands->values();
    }

    function equals($other)
    {
        if ($this === $other)
        {
            return true;
        }
        else if (!($other instanceof SemanticContextAnd))
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
        $hash->update($this->opnds, "AND");
    }

    // {@inheritDoc}
    // <p>The evaluation of predicates by this context is short-circuiting, but unordered.</p>
    function eval($parser, $outerContext)
    {
        for ($i = 0; $i < count($this->opnds); $i++)
        {
            if (!$this->opnds[$i]->eval($parser, $outerContext)) return false;
        }
        return true;
    }

    function evalPrecedence($parser, $outerContext)
    {
        $differs = false;
        $operands = [];
        for ($i = 0; $i < count($this->opnds); $i++)
        {
            $context = $this->opnds[$i];
            $evaluated = $context->evalPrecedence($parser, $outerContext);
            $differs |= ($evaluated !== $context);
            if ($evaluated === null)
            {
                // The AND context is false if any element is false
                return null;
            }
            else if ($evaluated !== SemanticContext::NONE())
            {
                // Reduce the result by skipping true elements
                array_push($operands, $evaluated);
            }
        }
        if (!$differs) return $this;

        // all elements were true, so the AND context is true
        if (count($operands) === 0) return SemanticContext::NONE();

        $result = null;
        foreach ($operands as $o)
        {
            $result = $result === null ? $o : self::andContext($result, $o);
        }
        return $result;
    }

    function __toString()
    {
        $s = "";
        foreach ($this->opnds as $o) {
            $s .= "&& " . $o;
        }
        return strlen($s) > 3 ? substr($s, 3) : $s;
    }
}