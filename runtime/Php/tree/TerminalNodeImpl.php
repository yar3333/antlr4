<?php

namespace Antlr4\Tree;

use Antlr4\Interval;
use Antlr4\Token;

class TerminalNodeImpl extends TerminalNode
{
    public $parentCtx;
    public $symbol;

    function __construct($symbol)
    {
        parent::__construct();

        $this->parentCtx = null;
        $this->symbol = $symbol;
    }

    function getChild($i)
    {
        return null;
    }

    function getSymbol()
    {
        return $this->symbol;
    }

    function getParent()
    {
        return $this->parentCtx;
    }

    function getPayload()
    {
        return $this->symbol;
    }

    function getSourceInterval()
    {
        if ($this->symbol === null) {
            return new Interval(-1, -2);
        }
        $tokenIndex = $this->symbol->tokenIndex;
        return new Interval($tokenIndex, $tokenIndex);
    }

    function getChildCount()
    {
        return 0;
    }

    function accept($visitor)
    {
        return $visitor->visitTerminal($this);
    }

    function getText()
    {
        return $this->symbol->text;
    }

    function __toString()
    {
        if ($this->symbol->type === Token::EOF) {
            return "<EOF>";
        } else {
            return $this->symbol->text;
        }
    }
}