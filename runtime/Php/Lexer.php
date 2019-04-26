<?php

namespace Antlr4;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
///

// A lexer is recognizer that draws input symbols from a character stream.
//  lexer grammars result in a subclass of this object. A Lexer object
//  uses simplified match() and error recovery mechanisms in the interest of speed.

use Antlr4\Token; //('./Token').Token;
use Antlr4\Recognizer; //('./Recognizer').Recognizer;
use Antlr4\CommonTokenFactory; //('./CommonTokenFactory').CommonTokenFactory;
use Antlr4\RecognitionException; //('./error/Errors').RecognitionException;
use Antlr4\LexerNoViableAltException; //('./error/Errors').LexerNoViableAltException;

class TokenSource
{
}

class Lexer extends Recognizer
{
    const DEFAULT_MODE = 0;
    const MORE = -2;
    const SKIP = -3;

    const DEFAULT_TOKEN_CHANNEL = Token::DEFAULT_CHANNEL;
    const HIDDEN = Token::HIDDEN_CHANNEL;
    const MIN_CHAR_VALUE = 0x0000;
    const MAX_CHAR_VALUE = 0x10FFFF;

    public $_input;
    public $_factory;
    public $_tokenFactorySourcePair;
    public $_interp;
    public $_token;
    public $_tokenStartCharIndex;
    public $_tokenStartLine;
    public $_tokenStartColumn;
    public $_hitEOF;
    public $_channel;
    public $_type;
    public $_modeStack;
    public $_mode;
    public $_text;

    function __construct($input)
    {
        parent::__construct();

        $this->_input = $input;
        $this->_factory = CommonTokenFactory::DEFAULT();
        $this->_tokenFactorySourcePair = [ $this, $input ];

        $this->_interp = null;// child classes must populate this

        // The goal of all lexer rules/methods is to create a token object.
        // this is an instance variable as multiple rules may collaborate to
        // create a single token. nextToken will return this object after
        // matching lexer rule(s). If you subclass to allow multiple token
        // emissions, then set this to the last token to be matched or
        // something nonnull so that the auto token emit mechanism will not
        // emit another token.
        $this->_token = null;

        // What character index in the stream did the current token start at?
        // Needed, for example, to get the text for current token. Set at
        // the start of nextToken.
        $this->_tokenStartCharIndex = -1;

        // The line on which the first character of the token resides///
        $this->_tokenStartLine = -1;

        // The character position of first character within the line///
        $this->_tokenStartColumn = -1;

        // Once we see EOF on char stream, next token will be EOF.
        // If you have DONE : EOF ; then you see DONE EOF.
        $this->_hitEOF = false;

        // The channel number for the current token///
        $this->_channel = Token::DEFAULT_CHANNEL;

        // The token type for the current token///
        $this->_type = Token::INVALID_TYPE;

        $this->_modeStack = [];
        $this->_mode = Lexer::DEFAULT_MODE;

        // You can set the text for the current token to override what is in
        // the input char buffer. Use setText() or can set this instance var.
        $this->_text = null;
    }

    function reset()
    {
        // wack Lexer state variables
        if ($this->_input !== null)
        {
            $this->_input->seek(0);// rewind the input
        }
        $this->_token = null;
        $this->_type = Token::INVALID_TYPE;
        $this->_channel = Token::DEFAULT_CHANNEL;
        $this->_tokenStartCharIndex = -1;
        $this->_tokenStartColumn = -1;
        $this->_tokenStartLine = -1;
        $this->_text = null;

        $this->_hitEOF = false;
        $this->_mode = Lexer::DEFAULT_MODE;
        $this->_modeStack = [];

        $this->_interp->reset();
    }

    // Return a token from this source; i.e., match a token on the char stream.
    function nextToken()
    {
        if ($this->_input === null)
        {
            throw new \Exception("nextToken requires a non-null input stream.");
        }

        // Mark start location in char stream so unbuffered streams are
        // guaranteed at least have text of current token
        $tokenStartMarker = $this->_input->mark();
        try
        {
            while (true)
            {
                if ($this->_hitEOF)
                {
                    $this->emitEOF();
                    return $this->_token;
                }
                $this->_token = null;
                $this->_channel = Token::DEFAULT_CHANNEL;
                $this->_tokenStartCharIndex = $this->_input->index;
                $this->_tokenStartColumn = $this->_interp->column;
                $this->_tokenStartLine = $this->_interp->line;
                $this->_text = null;
                $continueOuter = false;
                while (true)
                {
                    $this->_type = Token::INVALID_TYPE;
                    $ttype = Lexer::SKIP;
                    try
                    {
                        $ttype = $this->_interp->match($this->_input, $this->_mode);
                    }
                    catch (\Throwable $e)
                    {
                        if ($e instanceof RecognitionException)
                        {
                            $this->notifyListeners($e);// report error
                            $this->recover($e);
                        }
                        else
                        {
                            //$console->log($e->stack);
                            throw $e;
                        }
                    }
                    if ($this->_input->LA(1) === Token::EOF)
                    {
                        $this->_hitEOF = true;
                    }
                    if ($this->_type === Token::INVALID_TYPE)
                    {
                        $this->_type = $ttype;
                    }
                    if ($this->_type === Lexer::SKIP)
                    {
                        $continueOuter = true;
                        break;
                    }
                    if ($this->_type !== Lexer::MORE)
                    {
                        break;
                    }
                }
                if ($continueOuter)
                {
                    continue;
                }
                if ($this->_token === null)
                {
                    $this->emit();
                }
                return $this->_token;
            }
        }
        finally
        {// make sure we release marker after match or
    // unbuffered char stream will keep buffering
            $this->_input->release($tokenStartMarker);
        }
    }

    // Instruct the lexer to skip creating a token for current lexer rule
    // and look for another token. nextToken() knows to keep looking when
    // a lexer rule finishes with token set to SKIP_TOKEN. Recall that
    // if token==null at end of any token rule, it creates one for you
    // and emits it.
    // /
    function skip()
    {
        $this->_type = Lexer::SKIP;
    }

    function more()
    {
        $this->_type = Lexer::MORE;
    }

    function mode($m)
    {
        $this->_mode = $m;
    }

    function pushMode($m)
    {
        if ($this->_interp->debug)
        {
            //$console->log("pushMode " . $m);
        }
        array_push($this->_modeStack, $this->_mode);
        $this->mode($m);
    }

    function popMode()
    {
        if ($this->_modeStack->length === 0)
        {
            throw new \Exception("Empty Stack");
        }
        if ($this->_interp->debug)
        {
            //$console->log("popMode back to " . $this->_modeStack->slice(0, -1));
        }
        $this->mode(array_pop($this->_modeStack));
        return $this->_mode;
    }

    // Set the char stream and reset the lexer
    function getInputStream() { return $this->_input; }
    function setInputStream($input)
    {
            $this->_input = null;
            $this->_tokenFactorySourcePair = [ $this, $this->_input ];
            $this->reset();
            $this->_input = $input;
            $this->_tokenFactorySourcePair = [ $this, $this->_input ];
    }

    function getSourceName() { return $this->_input->sourceName; }

    // By default does not support multiple emits per nextToken invocation
    // for efficiency reasons. Subclass and override this method, nextToken,
    // and getToken (to push tokens into a list and pull from that list
    // rather than a single variable as this implementation does).
    // /
    function emitToken($token)
    {
        $this->_token = $token;
    }

    // The standard method called to automatically emit a token at the
    // outermost lexical rule. The token object should point into the
    // char buffer start..stop. If there is a text override in 'text',
    // use that to set the token's text. Override this method to emit
    // custom Token objects or provide a new factory.
    // /
    function emit()
    {
        $t = $this->_factory->create(
            $this->_tokenFactorySourcePair,
            $this->_type,
            $this->_text,
            $this->_channel,
            $this->_tokenStartCharIndex,
            $this->getCharIndex() - 1,
            $this->_tokenStartLine,
            $this->_tokenStartColumn
        );
        $this->emitToken($t);
        return $t;
    }

    function emitEOF()
    {
        $cpos = $this->column;
        $lpos = $this->line;
        $eof = $this->_factory->create($this->_tokenFactorySourcePair, Token::EOF,
                null, Token::DEFAULT_CHANNEL, $this->_input->index,
                $this->_input->index - 1, $lpos, $cpos);
        $this->emitToken($eof);
        return $eof;
    }

    // LOOKS LIKE ERROR: function getType() { return $this->type; }
    // FIXED `type` TO `_type`
    function getType() { return $this->_type; }
    function setType($type) { $this->_type = $type; }

    function getLine() { return $this->_interp->line; }
    function setLine($line) { $this->_interp->line = $line; }

    function getColumn() { return $this->_interp->column; }
    function setColumn($column) { $this->_interp->column = $column; }

    // What is the index of the current character of lookahead?///
    function getCharIndex()
    {
        return $this->_input->index;
    }

    // Return the text matched so far for the current token or any text override.
    //Set the complete text of this token; it wipes any previous changes to the text.
    function getText()
    {
        if ($this->_text !== null)
        {
            return $this->_text;
        }
        else
        {
            return $this->_interp->getText($this->_input);
        }
    }
    function setText($text)
    {
        $this->_text = $text;
    }

    // Return a list of all Token objects in input char stream.
    // Forces load of all tokens. Does not include EOF token.
    // /
    function getAllTokens()
    {
        $tokens = [];
        $t = $this->nextToken();
        while ($t->type !== Token::EOF)
        {
            array_push($tokens, $t);
            $t = $this->nextToken();
        }
        return $tokens;
    }

    function notifyListeners($e)
    {
        $start = $this->_tokenStartCharIndex;
        $stop = $this->_input->index;
        $text = $this->_input->getText($start, $stop);
        $msg = "token recognition error at: '" + this.getErrorDisplay(text) + "'";
        $listener = $this->getErrorListenerDispatch();
        $listener->syntaxError($this, null, $this->_tokenStartLine, $this->_tokenStartColumn, $msg, $e);
    }

    function getErrorDisplay($s)
    {
        $d = [];
        for ($i = 0; $i < $s->length; $i++)
        {
            array_push($d, $s[$i]);
        }
        return $d->join('');
    }

    function getErrorDisplayForChar($c)
    {
        if ($c->charCodeAt(0) === Token::EOF)
        {
            return "<EOF>";
        }
        else if ($c === '\n') {
            return "\\n";
        }
        else if ($c === '\t') {
            return "\\t";
        }
        else if ($c === '\r') {
            return "\\r";
        }
        else
        {
            return $c;
        }
    }

    function getCharErrorDisplay($c)
    {
        return "'" + this.getErrorDisplayForChar(c) + "'";
    }

    // Lexers can normally match any char in it's vocabulary after matching
    // a token, so do the easy thing and just kill a character and hope
    // it all works out. You can instead use the rule invocation stack
    // to do sophisticated error recovery if you are in a fragment rule.
    // /
    function recover($re)
    {
        if ($this->_input->LA(1) !== Token::EOF)
        {
            if ($re instanceof LexerNoViableAltException)
            {
                // skip a char and try again
                $this->_interp->consume($this->_input);
            }
            else
            {
                // TODO: Do we lose character or line position information?
                $this->_input->consume();
            }
        }
    }
}
