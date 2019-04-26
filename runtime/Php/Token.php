<?php

namespace Antlr4;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
//

// A token has properties: text, type, line, character position in the line
// (so we can ignore tabs), token channel, index, and source from which
// we obtained this token.
class  Token
{
    const INVALID_TYPE = 0;

    // During lookahead operations, this "token" signifies we hit rule end ATN state
    // and did not follow it despite needing to.
    const EPSILON = -2;

    const MIN_USER_TOKEN_TYPE = 1;

    const EOF = -1;

    // All tokens go to the parser (unless skip() is called in that rule)
    // on a particular "channel". The parser tunes to a particular channel
    // so that whitespace etc... can go to the parser on a "hidden" channel.

    const DEFAULT_CHANNEL = 0;

    // Anything on different channel than DEFAULT_CHANNEL is not parsed
    // by parser.

    const HIDDEN_CHANNEL = 1;

    public $source;
    public $type;// token type of the token
    public $channel;// The parser ignores everything not on DEFAULT_CHANNEL
    public $start;// optional; return -1 if not implemented.
    public $stop;// optional; return -1 if not implemented.
    public $tokenIndex;// from 0..n-1 of the token object in the input stream
    public $line;// line=1..n of the 1st character
    public $column;// beginning of the line at which it occurs, 0..n-1
    protected $_text;// text of the token.

    function __construct()
    {
        $this->source = null;
        $this->type = null;// token type of the token
        $this->channel = null;// The parser ignores everything not on DEFAULT_CHANNEL
        $this->start = null;// optional; return -1 if not implemented.
        $this->stop = null;// optional; return -1 if not implemented.
        $this->tokenIndex = null;// from 0..n-1 of the token object in the input stream
        $this->line = null;// line=1..n of the 1st character
        $this->column = null;// beginning of the line at which it occurs, 0..n-1
        $this->_text = null;// text of the token.
        return $this;
    }

    // Explicitly set the text for this token. If {code text} is not
    // {@code null}, then {@link //getText} will return this value rather than
    // extracting the text from the input.
    function getText() { return $this->_text; }

    // @param text The explicit text of the token, or {@code null} if the text
    // should be obtained from the input along with the start and stop indexes
    // of the token.
    function setText($text) { $this->_text = $text; }

    function getTokenSource()
    {
        return $this->source[0];
    }

    function getInputStream()
    {
        return $this->source[1];
    }
}

class CommonToken extends Token
{
    function __construct($source, $type, $channel, $start, $stop)
    {
        parent::__construct();

        $this->source = isset($source) ? $source : CommonToken::EMPTY_SOURCE;
        $this->type = isset($type) ? $type : null;
        $this->channel = isset($channel) ? $channel : Token::DEFAULT_CHANNEL;
        $this->start = isset($start) ? $start : -1;
        $this->stop = isset($stop) ? $stop : -1;
        $this->tokenIndex = -1;
        if ($this->source[0] !== null)
        {
            $this->line = $source[0]->line;
            $this->column = $source[0]->column;
        }
        else
        {
            $this->column = -1;
        }
    }

    // An empty {@link Pair} which is used as the default value of
    // {@link //source} for tokens that do not have a source.
    const EMPTY_SOURCE = [ null, null ];

    // Constructs a new {@link CommonToken} as a copy of another {@link Token}.
    //
    // <p>
    // If {@code oldToken} is also a {@link CommonToken} instance, the newly
    // constructed token will share a reference to the {@link //text} field and
    // the {@link Pair} stored in {@link //source}. Otherwise, {@link //text} will
    // be assigned the result of calling {@link //getText}, and {@link //source}
    // will be constructed from the result of {@link Token//getTokenSource} and
    // {@link Token//getInputStream}.</p>
    //
    // @param oldToken The token to copy.
    //
    function clone()
    {
        $t = new CommonToken($this->source, $this->type, $this->channel, $this->start, $this->stop);
        $t->tokenIndex = $this->tokenIndex;
        $t->line = $this->line;
        $t->column = $this->column;
        $t->setText($this->getText());
        return $t;
    }

    function getText()
    {
        if ($this->_text !== null)
        {
            return $this->_text;
        }
        $input = $this->getInputStream();
        if ($input === null)
        {
            return null;
        }
        $n = $input->size;
        if ($this->start < $n && $this->stop < $n)
        {
            return $input->getText($this->start, $this->stop);
        }
        else
        {
            return "<EOF>";
        }
    }

    function setText($text)
    {
        $this->_text = $text;
    }

    function toString()
    {
        $txt = $this->getText();
        if ($txt !== null)
        {
            $txt = preg_replace('/\n/g', "\\n", $txt);
            $txt = preg_replace('/\r/g', "\\r", $txt);
            $txt = preg_replace('/\t/g', "\\t", $txt);
        }
        else
        {
            $txt = "<no text>";
        }
        return "[@" . $this->tokenIndex . "," . $this->start . ":" . $this->stop . "='" .
                $txt . "',<" . $this->type . ">" .
                ($this->channel > 0 ? ",channel=" . $this->channel : "") . "," .
                $this->line . ":" . $this->column . "]";
    }
}