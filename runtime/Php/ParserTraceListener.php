<?php

namespace Antlr4;

class ParserTraceListener extends ParseTreeListener
{
    /**
     * @var Parser
     */
    public $parser;

    function __construct(Parser $parser)
    {
        parent::__construct();

        $this->parser = $parser;
    }

    function enterEveryRule($ctx)
    {
        //$console->log("enter   " + this.parser.ruleNames[ctx.ruleIndex] + ", LT(1)=" . $this->parser->_input->LT(1).$text);
    }

    function visitTerminal($node)
    {
        //$console->log("consume " + node.symbol + " rule " . $this->parser->ruleNames[$this->parser->_ctx->ruleIndex]);
    }

    function exitEveryRule($ctx)
    {
        //$console->log("exit    " + this.parser.ruleNames[ctx.ruleIndex] + ", LT(1)=" . $this->parser->_input->LT(1).$text);
    }
}