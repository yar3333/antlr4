<?php

namespace Antlr4\Tree;

class ParseTreeWalker
{
    private static $_DEFAULT;

    static function DEFAULT(): ParseTreeWalker
    {
        return self::$_DEFAULT ? self::$_DEFAULT : (self::$_DEFAULT = new ParseTreeWalker());
    }

    function __construct($token)
    {
    }

    function walk($listener, $t)
    {
        $errorNode = $t instanceof ErrorNode || (method_exists($t, 'isErrorNode') && $t->isErrorNode());
        if ($errorNode) {
            $listener->visitErrorNode($t);
        } else if ($t instanceof TerminalNode) {
            $listener->visitTerminal($t);
        } else {
            $this->enterRule($listener, $t);
            for ($i = 0; $i < $t->getChildCount(); $i++) {
                $child = $t->getChild($i);
                $this->walk($listener, $child);
            }
            $this->exitRule($listener, $t);
        }
    }

    // The discovery of a rule node, involves sending two events: the generic
    // {@link ParseTreeListener//enterEveryRule} and a
    // {@link RuleContext}-specific event. First we trigger the generic and then
    // the rule specific. We to them in reverse order upon finishing the node.
    function enterRule($listener, $r)
    {
        $ctx = $r->getRuleContext();
        $listener->enterEveryRule($ctx);
        $ctx->enterRule($listener);
    }

    function exitRule($listener, $r)
    {
        $ctx = $r->getRuleContext();
        $ctx->exitRule($listener);
        $listener->exitEveryRule($ctx);
    }
}