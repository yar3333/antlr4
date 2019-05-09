<?php

namespace Antlr4;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

// A rule invocation record for parsing.
//
//  Contains all of the information about the current rule not stored in the
//  RuleContext. It handles parse tree children list, Any ATN state
//  tracing, and the default values available for rule indications:
//  start, stop, rule index, current alt number, current
//  ATN state.
//
//  Subclasses made for each rule and grammar track the parameters,
//  return values, locals, and labels specific to that rule. These
//  are the objects that are returned from rules.
//
//  Note text is not an actual field of a rule return value; it is computed
//  from start and stop using the input stream's toString() method.  I
//  could add a ctor to this so that we can pass in and store the input
//  stream, but I'm not sure we want to do that.  It would seem to be undefined
//  to get the .text property anyway if the rule matches tokens from multiple
//  input streams.
//
//  I do not use getters for fields of objects that are used simply to
//  group values such as this aggregate.  The getters/setters are there to
//  satisfy the superclass interface.
use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Tree\ErrorNodeImpl;
use Antlr4\Tree\ParseTree;
use Antlr4\Tree\TerminalNode;
use Antlr4\Tree\TerminalNodeImpl;

class ParserRuleContext extends RuleContext
{
    public $ruleIndex;

    /**
     * @var ParseTree[]
     */
    public $children;

    /**
     * @var Token
     */
    public $start;

    /**
     * @var Token
     */
    public $stop;

    /**
     * @var RecognitionException
     */
    public $exception;

    function __construct(RuleContext $parent=null, int $invokingStateNumber=null)
    {
        parent::__construct($parent, $invokingStateNumber);

        $this->ruleIndex = -1;

        // If we are debugging or building a parse tree for a visitor,
        // we need to track all of the tokens and rule invocations associated
        // with this rule's context. This is empty for parsing w/o tree constr.
        // operation because we don't the need to track the details about
        // how we parse this rule.
        $this->children = null;
        $this->start = null;
        $this->stop = null;

        // The exception that forced this rule to return. If the rule successfully
        // completed, this is {@code null}.
        $this->exception = null;
    }

    /**
     * COPY a ctx (I'm deliberately not using copy constructor)
     * @param ParserRuleContext $ctx
     */
    function copyFrom($ctx) : void
    {
        // from RuleContext
        $this->parentCtx = $ctx->parentCtx;
        $this->invokingState = $ctx->invokingState;
        $this->children = null;
        $this->start = $ctx->start;
        $this->stop = $ctx->stop;
        // copy any error nodes to alt label node
        if ($ctx->children)
        {
            $this->children = [];
            // reset parent pointer for any error nodes
            foreach ($ctx->children as $child)
            {
                if ($child instanceof ErrorNodeImpl)
                {
                    array_push($this->children, $child);
                    $child->parentCtx = $this;
                }
            }
        }
    }

    function enterRule($listener)
    {
    }

    function exitRule($listener)
    {
    }

    // Does not set parent link; other add methods do that
    function addChild($child)
    {
        if (!isset($this->children))
        {
            $this->children = [];
        }
        array_push($this->children, $child);
        return $child;
    }

    // Used by enterOuterAlt to toss out a RuleContext previously added as
    // we entered a rule. If we have // label, we will need to remove
    // generic ruleContext object.
    function removeLastChild()
    {
        if (!isset($this->children))
        {
            array_pop($this->children);
        }
    }

    function addTokenNode($token)
    {
        $node = new TerminalNodeImpl($token);
        $this->addChild($node);
        $node->parentCtx = $this;
        return $node;
    }

    function addErrorNode($badToken)
    {
        $node = new ErrorNodeImpl($badToken);
        $this->addChild($node);
        $node->parentCtx = $this;
        return $node;
    }

    function getChild(int $i, $type=null)
    {
        if ($this->children === null || $i < 0 || $i >= count($this->children))
        {
            return null;
        }
        if ($type === null) return $this->children[$i];

        foreach ($this->children as $child)
        {
            if ($child instanceof $type)
            {
                if ($i===0) return $child;
                $i -= 1;
            }
        }
        return null;
    }

    function getToken($ttype, $i)
    {
        if ($this->children === null || $i < 0 || $i >= count($this->children))
        {
            return null;
        }
        foreach ($this->children as $child)
        {
            if ($child instanceof TerminalNode)
            {
                if ($child->getSymbol()->type === $ttype)
                {
                    if($i===0)
                    {
                        return $child;
                    }
                    else
                    {
                        $i -= 1;
                    }
                }
            }
        }
        return null;
    }

    function getTokens($ttype )
    {
        if ($this->children === null)
        {
            return [];
        }
        else
        {
            $tokens = [];
            foreach ($this->children as $child)
            {
                if ($child instanceof TerminalNode)
                {
                    if ($child->getSymbol()->type === $ttype)
                    {
                        array_push($tokens, $child);
                    }
                }
            }
            return $tokens;
        }
    }

    function getTypedRuleContext($ctxType, $i)
    {
        return $this->getChild($i, $ctxType);
    }

    function getTypedRuleContexts($ctxType)
    {
        if ($this->children=== null)
        {
            return [];
        }
        else
        {
            $contexts = [];
            foreach ($this->children as $child)
            {
                if ($child instanceof $ctxType)
                {
                    array_push($contexts, $child);
                }
            }
            return $contexts;
        }
    }

    function getChildCount() : int
    {
        return !isset($this->children) ? 0 : count($this->children);
    }

    function getSourceInterval() : Interval
    {
        if ($this->start === null || $this->stop === null)
        {
            return Interval::INVALID();
        }
        else
        {
            return new Interval($this->start->tokenIndex, $this->stop->tokenIndex);
        }
    }

    /**
     * @return ParserRuleContext
     */
    public function getParent()
    {
        /** @var ParserRuleContext $r */
        $r = $this->parentCtx;
        return $r;
    }
}
