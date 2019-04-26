<?php

namespace Antlr4;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

use Antlr4\Token; //('./Token').Token;
use Antlr4\ParseTreeListener; //('./tree/Tree').ParseTreeListener;
use Antlr4\Recognizer; //('./Recognizer').Recognizer;
use Antlr4\DefaultErrorStrategy; //('./error/ErrorStrategy').DefaultErrorStrategy;
use Antlr4\ATNDeserializer; //('./atn/ATNDeserializer').ATNDeserializer;
use Antlr4\ATNDeserializationOptions; //('./atn/ATNDeserializationOptions').ATNDeserializationOptions;
use Antlr4\TerminalNode; //('./tree/Tree').TerminalNode;
use Antlr4\ErrorNode; //('./tree/Tree').ErrorNode;
use Antlr4\Lexer; //('./Lexer').Lexer;

class TraceListener extends ParseTreeListener
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

    function visitTerminal( $node)
    {
        //$console->log("consume " + node.symbol + " rule " . $this->parser->ruleNames[$this->parser->_ctx->ruleIndex]);
    }

    function exitEveryRule($ctx)
    {
        //$console->log("exit    " + this.parser.ruleNames[ctx.ruleIndex] + ", LT(1)=" . $this->parser->_input->LT(1).$text);
    }
}

// This is all the parsing support code essentially; most of it is error recovery stuff.
class Parser extends Recognizer
{
    /**
     * this field maps from the serialized ATN string to the deserialized {@link ATN} with bypass alternatives.
     * @see ATNDeserializationOptions//isGenerateRuleBypassTransitions()
     * @var array
     */
    static $bypassAltsAtnCache = [];

    /**
     * @var InputStream
     */
    public $_input;

    /**
     * @var \Antlr4\DefaultErrorStrategy
     */
    public $_errHandler;

    /**
     * @var array
     */
    public $_precedenceStack;
    public $_ctx;
    public $buildParseTrees;
    public $_tracer;
    public $_parseListeners;
    public $_syntaxErrors;

    function __construct(InputStream $input)
    {
        parent::__construct();

        // The input stream.
        $this->_input = null;

        // The error handling strategy for the parser. The default value is a new
        // instance of {@link DefaultErrorStrategy}.
        $this->_errHandler = new DefaultErrorStrategy();

        $this->_precedenceStack = [];

        array_push($this->_precedenceStack, 0);

        // The {@link ParserRuleContext} object for the currently executing rule.
        // this is always non-null during the parsing process.
        $this->_ctx = null;

        // Specifies whether or not the parser should construct a parse tree during
        // the parsing process. The default value is {@code true}.
        $this->buildParseTrees = true;

        // When {@link //setTrace}{@code (true)} is called, a reference to the
        // {@link TraceListener} is stored here so it can be easily removed in a
        // later call to {@link //setTrace}{@code (false)}. The listener itself is
        // implemented as a parser listener so this field is not directly used by
        // other parser methods.
        $this->_tracer = null;

        // The list of {@link ParseTreeListener} listeners registered to receive
        // events during the parse.
        $this->_parseListeners = null;

        // The number of syntax errors reported during parsing. this value is
        // incremented each time {@link //notifyErrorListeners} is called.
        $this->_syntaxErrors = 0;

        $this->setInputStream($input);
    }

    // reset the parser's state
    function reset()
    {
        if ($this->_input !== null)
        {
            $this->_input->seek(0);
        }
        $this->_errHandler->reset($this);
        $this->_ctx = null;
        $this->_syntaxErrors = 0;
        $this->setTrace(false);
        $this->_precedenceStack = [];
        array_push($this->_precedenceStack, 0);
        if ($this->_interp !== null)
        {
            $this->_interp->reset();
        }
    }

    // Match current input symbol against {@code ttype}. If the symbol type
    // matches, {@link ANTLRErrorStrategy//reportMatch} and {@link //consume} are
    // called to complete the match process.
    //
    // <p>If the symbol type does not match,
    // {@link ANTLRErrorStrategy//recoverInline} is called on the current error
    // strategy to attempt recovery. If {@link //getBuildParseTree} is
    // {@code true} and the token index of the symbol returned by
    // {@link ANTLRErrorStrategy//recoverInline} is -1, the symbol is added to
    // the parse tree by calling {@link ParserRuleContext//addErrorNode}.</p>
    //
    // @param ttype the token type to match
    // @return the matched symbol
    // @throws RecognitionException if the current input symbol did not match
    // {@code ttype} and the error strategy could not recover from the mismatched symbol
    function match($ttype)
    {
        $t = $this->getCurrentToken();
        if ($t->type === $ttype)
        {
            $this->_errHandler->reportMatch($this);
            $this->consume();
        }
        else
        {
            $t = $this->_errHandler->recoverInline($this);
            if ($this->buildParseTrees && $t->tokenIndex === -1)
            {
                // we must have conjured up a new token during single token
                // insertion
                // if it's not the current symbol
                $this->_ctx->addErrorNode($t);
            }
        }

        return $t;
    }

    // Match current input symbol as a wildcard. If the symbol type matches
    // (i.e. has a value greater than 0), {@link ANTLRErrorStrategy//reportMatch}
    // and {@link //consume} are called to complete the match process.
    //
    // <p>If the symbol type does not match,
    // {@link ANTLRErrorStrategy//recoverInline} is called on the current error
    // strategy to attempt recovery. If {@link //getBuildParseTree} is
    // {@code true} and the token index of the symbol returned by
    // {@link ANTLRErrorStrategy//recoverInline} is -1, the symbol is added to
    // the parse tree by calling {@link ParserRuleContext//addErrorNode}.</p>
    //
    // @return the matched symbol
    // @throws RecognitionException if the current input symbol did not match
    // a wildcard and the error strategy could not recover from the mismatched symbol
    function matchWildcard()
    {
        $t = $this->getCurrentToken();
        if ($t->type > 0)
        {
            $this->_errHandler->reportMatch($this);
            $this->consume();
        }
        else
        {
            $t = $this->_errHandler->recoverInline($this);
            if ($this->_buildParseTrees && $t->tokenIndex === -1)
            {
                // we must have conjured up a new token during single token insertion if it's not the current symbol
                $this->_ctx->addErrorNode($t);
            }
        }
        return $t;
    }

    function getParseListeners()
    {
        return $this->_parseListeners || [];
    }

    // Registers {@code listener} to receive events during the parsing process.
    //
    // <p>To support output-preserving grammar transformations (including but not
    // limited to left-recursion removal, automated left-factoring, and
    // optimized code generation), calls to listener methods during the parse
    // may differ substantially from calls made by
    // {@link ParseTreeWalker//DEFAULT} used after the parse is complete. In
    // particular, rule entry and exit events may occur in a different order
    // during the parse than after the parser. In addition, calls to certain
    // rule entry methods may be omitted.</p>
    //
    // <p>With the following specific exceptions, calls to listener events are
    // <em>deterministic</em>, i.e. for identical input the calls to listener
    // methods will be the same.</p>
    //
    // <ul>
    // <li>Alterations to the grammar used to generate code may change the
    // behavior of the listener calls.</li>
    // <li>Alterations to the command line options passed to ANTLR 4 when
    // generating the parser may change the behavior of the listener calls.</li>
    // <li>Changing the version of the ANTLR Tool used to generate the parser
    // may change the behavior of the listener calls.</li>
    // </ul>
    //
    // @param listener the listener to add
    //
    // @throws NullPointerException if {@code} listener is {@code null}
    function addParseListener($listener)
    {
        if ($listener === null)
        {
            throw new \Exception("listener");
        }
        if ($this->_parseListeners === null)
        {
            $this->_parseListeners = [];
        }
        array_push($this->_parseListeners, $listener);
    }

    // Remove {@code listener} from the list of parse listeners.
    //
    // <p>If {@code listener} is {@code null} or has not been added as a parse
    // listener, this method does nothing.</p>
    // @param listener the listener to remove
    function removeParseListener($listener)
    {
        if ($this->_parseListeners !== null)
        {
            $idx = $this->_parseListeners->indexOf($listener);
            if ($idx >= 0)
            {
                $this->_parseListeners->splice($idx, 1);
            }
            if ($this->_parseListeners->length === 0)
            {
                $this->_parseListeners = null;
            }
        }
    }

    // Remove all parse listeners.
    function removeParseListeners()
    {
        $this->_parseListeners = null;
    }

    // Notify any parse listeners of an enter rule event.
    function triggerEnterRuleEvent()
    {
        if ($this->_parseListeners !== null)
        {
            $ctx = $this->_ctx;
            $this->_parseListeners->map(function($listener)
            {
                $listener->enterEveryRule($ctx);
                $ctx->enterRule($listener);
            });
        }
    }

    // Notify any parse listeners of an exit rule event.
    //
    // @see //addParseListener
    function triggerExitRuleEvent()
    {
        if ($this->_parseListeners !== null)
        {// reverse order walk of listeners
            $ctx = $this->_ctx;
            $this->_parseListeners->slice(0).reverse().map(function($listener)
            {
                $ctx->exitRule($listener);
                $listener->exitEveryRule($ctx);
            });
        }
    }

    function getTokenFactory()
    {
        return $this->_input->tokenSource->_factory;
    }

    // Tell our token source and error strategy about a new way to create tokens.//
    function setTokenFactory($factory)
    {
        $this->_input->tokenSource->_factory = $factory;
    }

    // The ATN with bypass alternatives is expensive to create so we create it lazily.
    // @throws UnsupportedOperationException if the current parser does not implement the {@link //getSerializedATN()} method.
    function getATNWithBypassAlts()
    {
        $serializedAtn = $this->getSerializedATN();
        if ($serializedAtn === null)
        {
            throw new \Exception("The current parser does not support an ATN with bypass alternatives.");
        }
        $result = $this->bypassAltsAtnCache[$serializedAtn];
        if ($result === null)
        {
            $deserializationOptions = new ATNDeserializationOptions();
            $deserializationOptions->generateRuleBypassTransitions = true;
            $result = (new ATNDeserializer($deserializationOptions))->deserialize($serializedAtn);
            $this->bypassAltsAtnCache[$serializedAtn] = $result;
        }
        return $result;
    }

    // The preferred method of getting a tree pattern. For example, here's a sample use:
    // <pre>
    // ParseTree t = parser.expr();
    // ParseTreePattern p = parser.compileParseTreePattern("&lt;ID&gt;+0",
    // MyParser.RULE_expr);
    // ParseTreeMatch m = p.match(t);
    // String id = m.get("ID");
    // </pre>
    function compileParseTreePattern($pattern, $patternRuleIndex, $lexer)
    {
        $lexer = $lexer || null;
        if ($lexer === null)
        {
            if ($this->getTokenStream() !== null)
            {
                $tokenSource = $this->getTokenStream()->tokenSource;
                if ($tokenSource instanceof Lexer)
                {
                    $lexer = $tokenSource;
                }
            }
        }
        if ($lexer === null)
        {
            throw new \Exception("Parser can't discover a lexer to use");
        }
        $m = new ParseTreePatternMatcher($lexer, $this);
        return $m->compile($pattern, $patternRuleIndex);
    }

    function getInputStream()
    {
        return $this->getTokenStream();
    }

    function setInputStream(InputStream $input)
    {
        $this->setTokenStream($input);
    }

    function getTokenStream()
    {
        return $this->_input;
    }

    // Set the token stream and reset the parser.//
    function setTokenStream(InputStream $input)
    {
        $this->_input = null;
        $this->reset();
        $this->_input = $input;
    }

    // Match needs to return the current input symbol, which gets put
    // into the label for the associated token ref; e.g., x=ID.
    //
    function getCurrentToken()
    {
        return $this->_input->LT(1);
    }

    function notifyErrorListeners($msg, $offendingToken, $err)
    {
        $offendingToken = $offendingToken || null;
        $err = $err || null;
        if ($offendingToken === null)
        {
            $offendingToken = $this->getCurrentToken();
        }
        $this->_syntaxErrors += 1;
        $line = $offendingToken->line;
        $column = $offendingToken->column;
        $listener = $this->getErrorListenerDispatch();
        $listener->syntaxError($this, $offendingToken, $line, $column, $msg, $err);
    }

    // Consume and return the {@linkplain //getCurrentToken current symbol}.
    //
    // <p>E.g., given the following input with {@code A} being the current
    // lookahead symbol, this function moves the cursor to {@code B} and returns
    // {@code A}.</p>
    //
    // <pre>
    // A B
    // ^
    // </pre>
    //
    // If the parser is not in error recovery mode, the consumed symbol is added
    // to the parse tree using {@link ParserRuleContext//addChild(Token)}, and
    // {@link ParseTreeListener//visitTerminal} is called on any parse listeners.
    // If the parser <em>is</em> in error recovery mode, the consumed symbol is
    // added to the parse tree using
    // {@link ParserRuleContext//addErrorNode(Token)}, and
    // {@link ParseTreeListener//visitErrorNode} is called on any parse listeners.
    function consume()
    {
        $o = $this->getCurrentToken();
        if ($o->type !== Token::EOF)
        {
            $this->getInputStream()->consume();
        }
        $hasListener = $this->_parseListeners !== null && $this->_parseListeners->length > 0;
        if ($this->buildParseTrees || $hasListener)
        {
            if ($this->_errHandler->inErrorRecoveryMode($this))
            {
                $node = $this->_ctx->addErrorNode($o);
            }
            else
            {
                $node = $this->_ctx->addTokenNode($o);
            }

            $node->invokingState = $this->state;

            if ($hasListener)
            {
                $this->_parseListeners->map(function($listener)
                {
                    if ($node instanceof ErrorNode || (isset($node->isErrorNode) && $node->isErrorNode()))
                    {
                        $listener->visitErrorNode($node);
                    }
                    else if ($node instanceof TerminalNode)
                    {
                        $listener->visitTerminal($node);
                    }
                });
            }
        }
        return $o;
    }

    function addContextToParseTree()
    {
        // add current context to parent if we have a parent
        if ($this->_ctx->parentCtx !== null)
        {
            $this->_ctx->parentCtx->addChild($this->_ctx);
        }
    }

    // Always called by generated parsers upon entry to a rule. Access field
    // {@link //_ctx} get the current context.
    function enterRule($localctx, $state, $ruleIndex)
    {
        $this->state = $state;
        $this->_ctx = $localctx;
        $this->_ctx->start = $this->_input->LT(1);
        if ($this->buildParseTrees)
        {
            $this->addContextToParseTree();
        }
        if ($this->_parseListeners !== null)
        {
            $this->triggerEnterRuleEvent();
        }
    }

    function exitRule()
    {
        $this->_ctx->stop = $this->_input->LT(-1);
        // trigger event on _ctx, before it reverts to parent
        if ($this->_parseListeners !== null)
        {
            $this->triggerExitRuleEvent();
        }
        $this->state = $this->_ctx->invokingState;
        $this->_ctx = $this->_ctx->parentCtx;
    }

    function enterOuterAlt($localctx, $altNum)
    {
        $localctx->setAltNumber($altNum);
        // if we have new localctx, make sure we replace existing ctx
        // that is previous child of parse tree
        if ($this->buildParseTrees && $this->_ctx !== $localctx)
        {
            if ($this->_ctx->parentCtx !== null)
            {
                $this->_ctx->parentCtx->removeLastChild();
                $this->_ctx->parentCtx->addChild($localctx);
            }
        }
        $this->_ctx = $localctx;
    }

    // Get the precedence level for the top-most precedence rule.
    // @return The precedence level for the top-most precedence rule, or -1 if
    // the parser context is not nested within a precedence rule.
    function getPrecedence()
    {
        if (count($this->_precedenceStack) === 0)
        {
            return -1;
        }
        else
        {
            return $this->_precedenceStack[count($this->_precedenceStack)-1];
        }
    }

    function enterRecursionRule($localctx, $state, $ruleIndex, $precedence)
    {
        $this->state = $state;
        array_push($this->_precedenceStack, $precedence);
        $this->_ctx = $localctx;
        $this->_ctx->start = $this->_input->LT(1);
        if ($this->_parseListeners !== null)
        {
            $this->triggerEnterRuleEvent();// simulates rule entry for left-recursive rules
        }
    }

    // Like {@link //enterRule} but for recursive rules.
    function pushNewRecursionContext($localctx, $state, $ruleIndex)
    {
        $previous = $this->_ctx;
        $previous->parentCtx = $localctx;
        $previous->invokingState = $state;
        $previous->stop = $this->_input->LT(-1);

        $this->_ctx = $localctx;
        $this->_ctx->start = $previous->start;
        if ($this->buildParseTrees)
        {
            $this->_ctx->addChild($previous);
        }
        if ($this->_parseListeners !== null)
        {
            $this->triggerEnterRuleEvent();// simulates rule entry for left-recursive rules
        }
    }

    function unrollRecursionContexts($parentCtx)
    {
        array_pop($this->_precedenceStack);
        $this->_ctx->stop = $this->_input->LT(-1);
        $retCtx = $this->_ctx;// save current ctx (return value)
        // unroll so _ctx is as it was before call to recursive method
        if ($this->_parseListeners !== null)
        {
            while ($this->_ctx !== $parentCtx)
            {
                $this->triggerExitRuleEvent();
                $this->_ctx = $this->_ctx->parentCtx;
            }
        }
        else
        {
            $this->_ctx = $parentCtx;
        }
        // hook into tree
        $retCtx->parentCtx = $parentCtx;
        if ($this->buildParseTrees && $parentCtx !== null)
        {
            // add return ctx into invoking rule's tree
            $parentCtx->addChild($retCtx);
        }
    }

    function getInvokingContext($ruleIndex)
    {
        $ctx = $this->_ctx;
        while ($ctx !== null)
        {
            if ($ctx->ruleIndex === $ruleIndex)
            {
                return $ctx;
            }
            $ctx = $ctx->parentCtx;
        }
        return null;
    }

    function precpred($localctx, $precedence)
    {
        return $precedence >= $this->_precedenceStack[count($this->_precedenceStack) - 1];
    }

    function inContext($context)
    {
        // TODO: useful in parser?
        return false;
    }

    // Checks whether or not {@code symbol} can follow the current state in the
    // ATN. The behavior of this method is equivalent to the following, but is
    // implemented such that the complete context-sensitive follow set does not
    // need to be explicitly constructed.
    //
    // <pre>
    // return getExpectedTokens().contains(symbol);
    // </pre>
    //
    // @param symbol the symbol type to check
    // @return {@code true} if {@code symbol} can follow the current state in
    // the ATN, otherwise {@code false}.
    function isExpectedToken($symbol)
    {
        $atn = $this->_interp->atn;
        $ctx = $this->_ctx;
        $s = $atn->states[$this->state];
        $following = $atn->nextTokens($s);
        if ($following->contains($symbol))
        {
            return true;
        }
        if (!$following->contains(Token::EPSILON))
        {
            return false;
        }
        while ($ctx !== null && $ctx->invokingState >= 0 && $following->contains(Token::EPSILON))
        {
            $invokingState = $atn->states[$ctx->invokingState];
            $rt = $invokingState->transitions[0];
            $following = $atn->nextTokens($rt->followState);
            if ($following->contains($symbol))
            {
                return true;
            }
            $ctx = $ctx->parentCtx;
        }
        if ($following->contains(Token::EPSILON) && $symbol === Token::EOF)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    // Computes the set of input symbols which could follow the current parser
    // state and context, as given by {@link //getState} and {@link //getContext},
    // respectively.
    //
    // @see ATN//getExpectedTokens(int, RuleContext)
    function getExpectedTokens()
    {
        return $this->_interp->atn->getExpectedTokens($this->state, $this->_ctx);
    }

    function getExpectedTokensWithinCurrentRule()
    {
        $atn = $this->_interp->atn;
        $s = $atn->states[$this->state];
        return $atn->nextTokens($s);
    }

    // Get a rule's index (i.e., {@code RULE_ruleName} field) or -1 if not found.//
    function getRuleIndex($ruleName)
    {
        $ruleIndex = $this->getRuleIndexMap()[$ruleName];
        if ($ruleIndex !== null)
        {
            return $ruleIndex;
        }
        else
        {
            return -1;
        }
    }

    // Return List&lt;String&gt; of the rule names in your parser instance
    // leading up to a call to the current rule. You could override if
    // you want more details such as the file/line info of where
    // in the ATN a rule is invoked.
    //
    // this is very useful for error messages.
    //
    function getRuleInvocationStack($p)
    {
        $p = $p || null;
        if ($p === null)
        {
            $p = $this->_ctx;
        }
        $stack = [];
        while ($p !== null)
        {// compute what follows who invoked us
            $ruleIndex = $p->ruleIndex;
            if ($ruleIndex < 0)
            {
                array_push($stack, "n/a");
            }
            else
            {
                array_push($stack, $this->ruleNames[$ruleIndex]);
            }
            $p = $p->parentCtx;
        }
        return $stack;
    }

    // For debugging and other purposes.
    function getDFAStrings()
    {
        return $this->_interp->decisionToDFA->toString();
    }

    // For debugging and other purposes.
    function dumpDFA()
    {
        $seenOne = false;
        for ($i = 0; $i < $this->_interp->decisionToDFA->length; $i++)
        {
            $dfa = $this->_interp->decisionToDFA[$i];
            if ($dfa->states->length > 0)
            {
                if ($seenOne)
                {
                    //$console->log();
                }
                $this->printer->println("Decision " + dfa.decision + ":");
                $this->printer->print($dfa->toString($this->literalNames, $this->symbolicNames));
                $seenOne = true;
            }
        }
    }

/*
"			printer = function() {\r\n" +
"				this.println = function(s) { document.getElementById('output') += s + '\\n'; }\r\n" +
"				this.print = function(s) { document.getElementById('output') += s; }\r\n" +
"			};\r\n" +
*/

    function getSourceName()
    {
        return $this->_input->sourceName;
    }

    // During a parse is sometimes useful to listen in on the rule entry and exit
    // events as well as token matches. this is for quick and dirty debugging.
    //
    function setTrace($trace)
    {
        if (!$trace)
        {
            $this->removeParseListener($this->_tracer);
            $this->_tracer = null;
        }
        else
        {
            if ($this->_tracer !== null)
            {
                $this->removeParseListener($this->_tracer);
            }
            $this->_tracer = new TraceListener($this);
            $this->addParseListener($this->_tracer);
        }
    }
}