<?php

namespace Antlr4;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

//* A rule invocation record for parsing.
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

use Antlr4\RuleContext; //('./RuleContext').RuleContext;
use Antlr4\Tree; //('./tree/Tree');
/*var */INVALID_INTERVAL = Tree::INVALID_INTERVAL;
/*var */TerminalNode = Tree::TerminalNode;
/*var */TerminalNodeImpl = Tree::TerminalNodeImpl;
/*var */ErrorNodeImpl = Tree::ErrorNodeImpl;
use Antlr4\Interval; //("./IntervalSet").Interval;

function ParserRuleContext($parent, $invokingStateNumber) 
{
	$parent = $parent || null;
	$invokingStateNumber = $invokingStateNumber || null;
	RuleContext->call($this, $parent, $invokingStateNumber);
	$this->ruleIndex = -1;
// * If we are debugging or building a parse tree for a visitor,
// we need to track all of the tokens and rule invocations associated
// with this rule's context. This is empty for parsing w/o tree constr.
// operation because we don't the need to track the details about
// how we parse this rule.
// /
    $this->children = null;
    $this->start = null;
    $this->stop = null;
// The exception that forced this rule to return. If the rule successfully
// completed, this is {@code null}.
    $this->exception = null;
}

ParserRuleContext::prototype = Object->create(RuleContext::prototype);
ParserRuleContext::prototype->constructor = ParserRuleContext;

// * COPY a ctx (I'm deliberately not using copy constructor)///
/* ParserRuleContext */function copyFrom($ctx) 
{// from RuleContext
    $this->parentCtx = $ctx->parentCtx;
    $this->invokingState = $ctx->invokingState;
    $this->children = null;
    $this->start = $ctx->start;
    $this->stop = $ctx->stop;
// copy any error nodes to alt label node
    if($ctx->children) 
    {
        $this->children = [];
// reset parent pointer for any error nodes
    	$ctx->children->map(function($child) 
    	{
    		if ($child instanceof ErrorNodeImpl) 
    		{
                $this->children->push($child);
                $child->parentCtx = $this;
            }
		}, $this);
	}
};

// Double dispatch methods for listeners
/* ParserRuleContext */function enterRule($listener) 
{
};

/* ParserRuleContext */function exitRule($listener) 
{
};

// * Does not set parent link; other add methods do that///
/* ParserRuleContext */function addChild($child) 
{
    if ($this->children === null) 
    {
        $this->children = [];
    }
    $this->children->push($child);
    return $child;
};

// * Used by enterOuterAlt to toss out a RuleContext previously added as
// we entered a rule. If we have // label, we will need to remove
// generic ruleContext object.
// /
/* ParserRuleContext */function removeLastChild() 
{
    if ($this->children !== null) 
    {
        $this->children->pop();
    }
};

/* ParserRuleContext */function addTokenNode($token) 
{
    /*var */$node = new TerminalNodeImpl($token);
    $this->addChild($node);
    $node->parentCtx = $this;
    return $node;
};

/* ParserRuleContext */function addErrorNode($badToken) 
{
    /*var */$node = new ErrorNodeImpl($badToken);
    $this->addChild($node);
    $node->parentCtx = $this;
    return $node;
};

/* ParserRuleContext */function getChild($i, $type) 
{
	$type = $type || null;
	if ($this->children === null || $i < 0 || $i >= $this->children->length) 
	{
		return null;
	}
	if ($type === null) 
	{
		return $this->children[$i];
	}
	else 
	{
		for($j=0; $j<$this->children->length; $j++) 
		{
			/*var */$child = $this->children[$j];
			if($child instanceof $type) 
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
		return null;
    }
};


/* ParserRuleContext */function getToken($ttype, $i) 
{
	if ($this->children === null || $i < 0 || $i >= $this->children->length) 
	{
		return null;
	}
	for($j=0; $j<$this->children->length; $j++) 
	{
		/*var */$child = $this->children[$j];
		if ($child instanceof TerminalNode) 
		{
			if ($child->symbol->type === $ttype) 
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
};

/* ParserRuleContext */function getTokens($ttype ) 
{
    if ($this->children=== null) 
    {
        return [];
    }
    else 
    {
		/*var */$tokens = [];
		for($j=0; $j<$this->children->length; $j++) 
		{
			/*var */$child = $this->children[$j];
			if ($child instanceof TerminalNode) 
			{
				if ($child->symbol->type === $ttype) 
				{
					$tokens->push($child);
				}
			}
		}
		return $tokens;
    }
};

/* ParserRuleContext */function getTypedRuleContext($ctxType, $i) 
{
    return $this->getChild($i, $ctxType);
};

/* ParserRuleContext */function getTypedRuleContexts($ctxType) 
{
    if ($this->children=== null) 
    {
        return [];
    }
    else 
    {
		/*var */$contexts = [];
		for($j=0; $j<$this->children->length; $j++) 
		{
			/*var */$child = $this->children[$j];
			if ($child instanceof $ctxType) 
			{
				$contexts->push($child);
			}
		}
		return $contexts;
	}
};

/* ParserRuleContext */function getChildCount() 
{
	if ($this->children=== null) 
	{
		return 0;
	}
	else 
	{
		return $this->children->length;
	}
};

/* ParserRuleContext */function getSourceInterval() 
{
    if( $this->start === null || $this->stop === null) 
    {
        return INVALID_INTERVAL;
    }
    else 
    {
        return new Interval($this->start->tokenIndex, $this->stop->tokenIndex);
    }
};

/* RuleContext */public $EMPTY = new/ ParserRuleContext();

function InterpreterRuleContext($parent, $invokingStateNumber, $ruleIndex) 
{
	ParserRuleContext->call($parent, $invokingStateNumber);
    $this->ruleIndex = $ruleIndex;
    return $this;
}

InterpreterRuleContext::prototype = Object->create(ParserRuleContext::prototype);
InterpreterRuleContext::prototype->constructor = InterpreterRuleContext;

$exports->ParserRuleContext = ParserRuleContext;