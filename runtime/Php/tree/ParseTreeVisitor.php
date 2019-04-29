<?php

namespace Antlr4\Tree;

class ParseTreeVisitor
{
    function __construct()
    {
    }

    function visit($ctx)
    {
        if (is_array($ctx)) {
            return $ctx->map(function ($child) {
                return $child->accept($this);
            }, $this);
        } else {
            return $ctx->accept($this);
        }
    }

    function visitChildren($ctx)
    {
        if ($ctx->children) {
            return $this->visit($ctx->children);
        } else {
            return null;
        }
    }

    function visitTerminal($node)
    {
    }

    function visitErrorNode($node)
    {
    }
}