<?php

namespace Antlr4;

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
        if ($this->source[0] !== null) {
            $this->line = $source[0]->line;
            $this->column = $source[0]->column;
        } else {
            $this->column = -1;
        }
    }

    // An empty {@link Pair} which is used as the default value of
    // {@link //source} for tokens that do not have a source.
    const EMPTY_SOURCE = [null, null];

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
        if ($this->_text !== null) {
            return $this->_text;
        }
        $input = $this->getInputStream();
        if ($input === null) {
            return null;
        }
        $n = $input->size;
        if ($this->start < $n && $this->stop < $n) {
            return $input->getText($this->start, $this->stop);
        } else {
            return "<EOF>";
        }
    }

    function setText($text)
    {
        $this->_text = $text;
    }

    function __toString()
    {
        $txt = $this->getText();
        if ($txt !== null) {
            $txt = preg_replace('/\n/g', "\\n", $txt);
            $txt = preg_replace('/\r/g', "\\r", $txt);
            $txt = preg_replace('/\t/g', "\\t", $txt);
        } else {
            $txt = "<no text>";
        }
        return "[@" . $this->tokenIndex . "," . $this->start . ":" . $this->stop . "='" .
            $txt . "',<" . $this->type . ">" .
            ($this->channel > 0 ? ",channel=" . $this->channel : "") . "," .
            $this->line . ":" . $this->column . "]";
    }
}