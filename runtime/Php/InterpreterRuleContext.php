<?php

namespace Antlr4;

class InterpreterRuleContext extends ParserRuleContext
{
    function __construct($parent, $invokingStateNumber, $ruleIndex)
    {
        parent::__construct($parent, $invokingStateNumber);
        $this->ruleIndex = $ruleIndex;
    }
}