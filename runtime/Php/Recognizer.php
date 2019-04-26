<?php

namespace Antlr4;

//
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
//

use Antlr4\Token; //('./Token').Token;
use Antlr4\ConsoleErrorListener; //('./error/ErrorListener').ConsoleErrorListener;
use Antlr4\ProxyErrorListener; //('./error/ErrorListener').ProxyErrorListener;

function Recognizer() 
{
    $this->_listeners = [ ConsoleErrorListener::INSTANCE ];
    $this->_interp = null;
    $this->_stateNumber = -1;
    return $this;
}

Recognizer::tokenTypeMapCache = {};
Recognizer::ruleIndexMapCache = {};


/* Recognizer */function checkVersion($toolVersion) 
{
    /*var */$runtimeVersion = "4.7.2";
    if ($runtimeVersion!==$toolVersion) 
    {
        $console->log("ANTLR runtime and generated code versions disagree: "+runtimeVersion+"!=".$toolVersion);
    }
};

/* Recognizer */function addErrorListener($listener) 
{
    array_push($this->_listeners, $listener);
};

/* Recognizer */function removeErrorListeners() 
{
    $this->_listeners = [];
};

/* Recognizer */function getTokenTypeMap() 
{
    /*var */$tokenNames = $this->getTokenNames();
    if ($tokenNames===null) 
    {
        throw("The current recognizer does not provide a list of token names.");
    }
    /*var */$result = $this->tokenTypeMapCache[$tokenNames];
    if($result===undefined) 
    {
        $result = $tokenNames->reduce(function($o, $k, $i) { $o[$k] = $i; });
        $result->EOF = Token::EOF;
        $this->tokenTypeMapCache[$tokenNames] = $result;
    }
    return $result;
};

// Get a map from rule names to rule indexes.
//
// <p>Used for XPath and tree pattern compilation.</p>
//
/* Recognizer */function getRuleIndexMap() 
{
    /*var */$ruleNames = $this->ruleNames;
    if ($ruleNames===null) 
    {
        throw("The current recognizer does not provide a list of rule names.");
    }
    /*var */$result = $this->ruleIndexMapCache[$ruleNames];
    if($result===undefined) 
    {
        $result = $ruleNames->reduce(function($o, $k, $i) { $o[$k] = $i; });
        $this->ruleIndexMapCache[$ruleNames] = $result;
    }
    return $result;
};

/* Recognizer */function getTokenType($tokenName) 
{
    /*var */$ttype = $this->getTokenTypeMap()[$tokenName];
    if ($ttype !==undefined) 
    {
        return $ttype;
    }
    else 
    {
        return Token::INVALID_TYPE;
    }
};


// What is the error header, normally line/character position information?//
/* Recognizer */function getErrorHeader($e) 
{
    /*var */$line = $e->getOffendingToken().$line;
    /*var */$column = $e->getOffendingToken().$column;
    return "line " + line + ":" . $column;
};


// How should a token be displayed in an error message? The default
//  is to display just the text, but during development you might
//  want to have a lot of information spit out.  Override in that case
//  to use t.toString() (which, for CommonToken, dumps everything about
//  the token). This is better than forcing you to override a method in
//  your token objects because you don't have to go modify your lexer
//  so that it creates a new Java type.
//
// @deprecated This method is not called by the ANTLR 4 Runtime. Specific
// implementations of {@link ANTLRErrorStrategy} may provide a similar
// feature when necessary. For example, see
// {@link DefaultErrorStrategy//getTokenErrorDisplay}.
//
/* Recognizer */function getTokenErrorDisplay($t) 
{
    if ($t===null) 
    {
        return "<no token>";
    }
    /*var */$s = $t->text;
    if ($s===null) 
    {
        if ($t->type===Token::EOF) 
        {
            $s = "<EOF>";
        }
        else 
        {
            $s = "<" + t.type + ">";
        }
    }
    $s = $s->replace("\n","\\n").replace("\r","\\r").replace("\t","\\t");
    return "'" + s + "'";
};

/* Recognizer */function getErrorListenerDispatch() 
{
    return new ProxyErrorListener($this->_listeners);
};

// subclass needs to override these if there are sempreds or actions
// that the ATN interp needs to execute
/* Recognizer */function sempred($localctx, $ruleIndex, $actionIndex) 
{
    return true;
};

/* Recognizer */function precpred($localctx , $precedence) 
{
    return true;
};

//Indicate that the recognizer has changed internal state that is
//consistent with the ATN state passed in.  This way we always know
//where we are in the ATN as the parser goes along. The rule
//context objects form a stack that lets us see the stack of
//invoking rules. Combine this and we have complete ATN
//configuration information.

Object->defineProperty(Recognizer::prototype, "state", {
	$get : function() 
	{
		return $this->_stateNumber;
	},
	$set : function($state) 
	{
		$this->_stateNumber = $state;
	}
});


$exports->Recognizer = Recognizer;
