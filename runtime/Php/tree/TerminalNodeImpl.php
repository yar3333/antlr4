<?php

namespace Antlr4\Tree;

use Antlr4\Interval;
use Antlr4\RuleContext;
use Antlr4\Token;

class TerminalNodeImpl implements TerminalNode
{
    /**
     * @var ParseTree
     */
    public $parentCtx;

    /**
     * @var Token
     */
    public $symbol;

    function __construct($symbol)
    {
        $this->parentCtx = null;
        $this->symbol = $symbol;
    }

    function getChild($i)
    {
        return null;
    }

    function getSymbol() : Token
    {
        return $this->symbol;
    }

    function setParent(RuleContext $parent) : void
    {
        $this->parentCtx = $parent;
    }

    function getParent() : ParseTree
    {
        return $this->parentCtx;
    }

    function getPayload()
    {
        return $this->symbol;
    }

    function getSourceInterval() : Interval
    {
        if ($this->symbol === null) {
            return new Interval(-1, -2);
        }
        $tokenIndex = $this->symbol->tokenIndex;
        return new Interval($tokenIndex, $tokenIndex);
    }

    function getChildCount() : int
    {
        return 0;
    }

    function accept(ParseTreeVisitor $visitor)
    {
        return $visitor->visitTerminal($this);
    }

    function getText() : string
    {
        return $this->symbol->getText();
    }

    function __toString()
    {
        if ($this->symbol->type === Token::EOF) {
            return "<EOF>";
        } else {
            return $this->symbol->getText();
        }
    }
}