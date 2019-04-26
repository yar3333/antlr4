<?php

namespace Antlr4\Tree;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
///

// The basic notion of a tree has a parent, a payload, and a list of children.
//  It is the most abstract interface for all the trees used by ANTLR.
///

use Antlr4\Token; //('./../Token').Token;
use Antlr4\Interval; //('./../IntervalSet').Interval;
/*var */INVALID_INTERVAL = new Interval(-1, -2);
use Antlr4\Utils; //('../Utils.js');


function Tree() 
{
	return $this;
}

function SyntaxTree() 
{
	Tree->call($this);
	return $this;
}

SyntaxTree::prototype = Object->create(Tree::prototype);
SyntaxTree::prototype->constructor = SyntaxTree;

function ParseTree() 
{
	SyntaxTree->call($this);
	return $this;
}

ParseTree::prototype = Object->create(SyntaxTree::prototype);
ParseTree::prototype->constructor = ParseTree;

function RuleNode() 
{
	ParseTree->call($this);
	return $this;
}

RuleNode::prototype = Object->create(ParseTree::prototype);
RuleNode::prototype->constructor = RuleNode;

function TerminalNode() 
{
	ParseTree->call($this);
	return $this;
}

TerminalNode::prototype = Object->create(ParseTree::prototype);
TerminalNode::prototype->constructor = TerminalNode;

function ErrorNode() 
{
	TerminalNode->call($this);
	return $this;
}

ErrorNode::prototype = Object->create(TerminalNode::prototype);
ErrorNode::prototype->constructor = ErrorNode;

function ParseTreeVisitor() 
{
	return $this;
}

/* ParseTreeVisitor */function visit($ctx) 
{
 	if (is_array($ctx)) 
 	{
		return $ctx->map(function($child) 
		{
            return $child->accept($this);
        }, $this);
	}
	else 
	{
		return $ctx->accept($this);
	}
};

/* ParseTreeVisitor */function visitChildren($ctx) 
{
	if ($ctx->children) 
	{
		return $this->visit($ctx->children);
	}
	else 
	{
		return null;
	}
}

/* ParseTreeVisitor */function visitTerminal($node) 
{
};

/* ParseTreeVisitor */function visitErrorNode($node) 
{
};


function ParseTreeListener() 
{
	return $this;
}

/* ParseTreeListener */function visitTerminal($node) 
{
};

/* ParseTreeListener */function visitErrorNode($node) 
{
};

/* ParseTreeListener */function enterEveryRule($node) 
{
};

/* ParseTreeListener */function exitEveryRule($node) 
{
};

function TerminalNodeImpl($symbol) 
{
	TerminalNode->call($this);
	$this->parentCtx = null;
	$this->symbol = $symbol;
	return $this;
}

TerminalNodeImpl::prototype = Object->create(TerminalNode::prototype);
TerminalNodeImpl::prototype->constructor = TerminalNodeImpl;

/* TerminalNodeImpl */function getChild($i) 
{
	return null;
};

/* TerminalNodeImpl */function getSymbol() 
{
	return $this->symbol;
};

/* TerminalNodeImpl */function getParent() 
{
	return $this->parentCtx;
};

/* TerminalNodeImpl */function getPayload() 
{
	return $this->symbol;
};

/* TerminalNodeImpl */function getSourceInterval() 
{
	if ($this->symbol === null) 
	{
		return INVALID_INTERVAL;
	}
	/*var */$tokenIndex = $this->symbol->tokenIndex;
	return new Interval($tokenIndex, $tokenIndex);
};

/* TerminalNodeImpl */function getChildCount() 
{
	return 0;
};

/* TerminalNodeImpl */function accept($visitor) 
{
	return $visitor->visitTerminal($this);
};

/* TerminalNodeImpl */function getText() 
{
	return $this->symbol->text;
};

/* TerminalNodeImpl */function toString() 
{
	if ($this->symbol->type === Token::EOF) 
	{
		return "<EOF>";
	}
	else 
	{
		return $this->symbol->text;
	}
};

// Represents a token that was consumed during resynchronization
// rather than during a valid match operation. For example,
// we will create this kind of a node during single token insertion
// and deletion as well as during "consume until error recovery set"
// upon no viable alternative exceptions.

function ErrorNodeImpl($token) 
{
	TerminalNodeImpl->call($this, $token);
	return $this;
}

ErrorNodeImpl::prototype = Object->create(TerminalNodeImpl::prototype);
ErrorNodeImpl::prototype->constructor = ErrorNodeImpl;

/* ErrorNodeImpl */function isErrorNode() 
{
	return true;
};

/* ErrorNodeImpl */function accept($visitor) 
{
	return $visitor->visitErrorNode($this);
};

function ParseTreeWalker() 
{
	return $this;
}

/* ParseTreeWalker */function walk($listener, $t) 
{
	/*var */$errorNode = $t instanceof ErrorNode ||
			($t->isErrorNode !== undefined && $t->isErrorNode());
	if ($errorNode) 
	{
		$listener->visitErrorNode($t);
	}
	else if ($t instanceof TerminalNode) 
	{
		$listener->visitTerminal($t);
	}
	else 
	{
		$this->enterRule($listener, $t);
		for ($i = 0; $i < $t->getChildCount(); $i++) 
		{
			/*var */$child = $t->getChild($i);
			$this->walk($listener, $child);
		}
		$this->exitRule($listener, $t);
	}
};
//
// The discovery of a rule node, involves sending two events: the generic
// {@link ParseTreeListener//enterEveryRule} and a
// {@link RuleContext}-specific event. First we trigger the generic and then
// the rule specific. We to them in reverse order upon finishing the node.
//
/* ParseTreeWalker */function enterRule($listener, $r) 
{
	/*var */$ctx = $r->getRuleContext();
	$listener->enterEveryRule($ctx);
	$ctx->enterRule($listener);
};

/* ParseTreeWalker */function exitRule($listener, $r) 
{
	/*var */$ctx = $r->getRuleContext();
	$ctx->exitRule($listener);
	$listener->exitEveryRule($ctx);
};

/* ParseTreeWalker */public $DEFAULT = new/ ParseTreeWalker();

$exports->RuleNode = RuleNode;
$exports->ErrorNode = ErrorNode;
$exports->TerminalNode = TerminalNode;
$exports->ErrorNodeImpl = ErrorNodeImpl;
$exports->TerminalNodeImpl = TerminalNodeImpl;
$exports->ParseTreeListener = ParseTreeListener;
$exports->ParseTreeVisitor = ParseTreeVisitor;
$exports->ParseTreeWalker = ParseTreeWalker;
$exports->INVALID_INTERVAL = INVALID_INTERVAL;
