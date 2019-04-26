<?php
<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

use Antlr4\Token; //('./Token').Token;
use Antlr4\ConsoleErrorListener; //('./error/ErrorListener').ConsoleErrorListener;
use Antlr4\ProxyErrorListener; //('./error/ErrorListener').ProxyErrorListener;

class Recognizer
{
    static $tokenTypeMapCache = [];
    static $ruleIndexMapCache = [];

    /**
     * @var ErrorListener[]
     */
    public $_listeners;

    /**
     * @var ATN
     */
    public $_interp;

    /**
     * @var int
     */
    public $_stateNumber;

    function Recognizer()
    {
        $this->_listeners = [ ConsoleErrorListener::INSTANCE ];
        $this->_interp = null;
        $this->_stateNumber = -1;
    }

    function checkVersion($toolVersion)
    {
        $runtimeVersion = "4.7.2";
        if ($runtimeVersion!==$toolVersion)
        {
            //$console->log("ANTLR runtime and generated code versions disagree: "+runtimeVersion+"!=".$toolVersion);
        }
    }

    function addErrorListener($listener)
    {
        array_push($this->_listeners, $listener);
    }

    function removeErrorListeners()
    {
        $this->_listeners = [];
    }

    function getTokenTypeMap() : array
    {
        $tokenNames = $this->getTokenNames();
        if ($tokenNames===null)
        {
            throw new \Exception("The current recognizer does not provide a list of token names.");
        }
        $result = self::$tokenTypeMapCache[$tokenNames];
        if(!isset($result))
        {
            $result = $tokenNames->reduce(function($o, $k, $i) { $o[$k] = $i; });
            $result->EOF = Token::EOF;
            self::$tokenTypeMapCache[$tokenNames] = $result;
        }
        return $result;
    }

    // Get a map from rule names to rule indexes.
    //
    // <p>Used for XPath and tree pattern compilation.</p>
    //
    function getRuleIndexMap()
    {
        $ruleNames = $this->ruleNames;
        if ($ruleNames===null)
        {
            throw new \Exception("The current recognizer does not provide a list of rule names.");
        }
        $result = self::$ruleIndexMapCache[$ruleNames];
        if(!isset($result))
        {
            //$result = $ruleNames->reduce(function($o, $k, $i) { $o[$k] = $i; });
            $result = []; foreach ($ruleNames as $i => $k) $result[$k] = $i;
            self::$ruleIndexMapCache[$ruleNames] = $result;
        }
        return $result;
    }

    function getTokenType($tokenName) : int
    {
        $ttype = $this->getTokenTypeMap()[$tokenName];
        return isset($ttype) ? $ttype : Token::INVALID_TYPE;
    }

    // What is the error header, normally line/character position information?//
    function getErrorHeader($e) : string
    {
        $line = $e->getOffendingToken()->line;
        $column = $e->getOffendingToken()->column;
        return "line " . $line . ":" . $column;
    }


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
    function getTokenErrorDisplay($t)
    {
        if ($t===null)
        {
            return "<no token>";
        }
        $s = $t->text;
        if ($s===null)
        {
            if ($t->type===Token::EOF)
            {
                $s = "<EOF>";
            }
            else
            {
                $s = "<" . $t->type . ">";
            }

        }
        $s = str_replace("\n","\\n", $s);
        $s = str_replace("\r","\\r", $s);
        $s = str_replace("\t","\\t", $s);

        return "'" . s . "'";
    }

    function getErrorListenerDispatch() : ErrorListener
    {
        return new ProxyErrorListener($this->_listeners);
    }

    // subclass needs to override these if there are sempreds or actions
    // that the ATN interp needs to execute
    function sempred($localctx, $ruleIndex, $actionIndex)
    {
        return true;
    }

    function precpred($localctx , $precedence)
    {
        return true;
    }

    //Indicate that the recognizer has changed internal state that is
    //consistent with the ATN state passed in.  This way we always know
    //where we are in the ATN as the parser goes along. The rule
    //context objects form a stack that lets us see the stack of
    //invoking rules. Combine this and we have complete ATN
    //configuration information.
    function getState() { return $this->_stateNumber; }
    function setState($state) { $this->_stateNumber = $state; }
}