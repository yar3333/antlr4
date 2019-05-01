<?php

namespace Antlr4\Tree;

use Antlr4\Utils\Utils;

abstract class ParseTreeVisitor
{
    function __construct() {}

    function visitOne(ParseTree $ctx) : object
    {
        return $ctx->accept($this);
    }

    /**
     * @param ParseTree[] $ctx
     * @return object[]
     */
    function visitMany(array $ctx) : array
    {
        return Utils::arrayMap($ctx, function(ParseTree $child) { return $child->accept($this); });
    }

    /**
     * @param RuleNode $ctx
     * @return object[]
     */
    function visitChildren(RuleNode $ctx) : array
    {
        if (!$ctx->children()) return null;
        return $this->visitMany($ctx->children());
    }

    function visitTerminal(TerminalNode $node) : object
    {
        return null;
    }

    function visitErrorNode(ErrorNode $node) : object
    {
        return null;
    }
}