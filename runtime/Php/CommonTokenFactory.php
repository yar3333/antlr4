<?php
//
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

// This default implementation of {@link TokenFactory} creates {@link CommonToken} objects.
use Antlr4\Utils\Pair;

class TokenFactory
{
    function __construct() {}
}

class CommonTokenFactory extends TokenFactory
{
    public $copyText;

    function __construct(string $copyText)
    {
        parent::__construct();
        // Indicates whether {@link CommonToken//setText} should be called after
        // constructing tokens to explicitly set the text. This is useful for cases
        // where the input stream might not be able to provide arbitrary substrings
        // of text from the input after the lexer creates a token (e.g. the
        // implementation of {@link CharStream//getText} in
        // {@link UnbufferedCharStream} throws an
        // {@link UnsupportedOperationException}). Explicitly setting the token text
        // allows {@link Token//getText} to be called at any time regardless of the
        // input stream implementation.
        //
        // <p>
        // The default value is {@code false} to avoid the performance and memory
        // overhead of copying text for every token unless explicitly requested.</p>
        //
        $this->copyText = !isset($copyText) ? false : $copyText;
    }

    //
    // The default {@link CommonTokenFactory} instance.
    //
    // <p>
    // This token factory does not explicitly copy token text when constructing
    // tokens.</p>
    //
    /**
     * @var CommonTokenFactory
     */
    private static $DEFAULT;
    public static function DEFAULT() { return self::$DEFAULT ? self::$DEFAULT : (self::$DEFAULT = new CommonTokenFactory(null)); }


    /**
     * @param Pair<TokenSource, CharStream> $source
     * @param int $type
     * @param string $text
     * @param int $channel
     * @param int $start
     * @param int $stop
     * @param int $line
     * @param int $column
     * @return CommonToken
     */
    function create(Pair $source, int $type, string $text, int $channel, int $start, int $stop, int $line, int $column) : CommonToken
    {
        $t = new CommonToken($source, $type, $channel, $start, $stop);

        $t->line = $line;
        $t->column = $column;

        if ($text !==null)
        {
            $t->setText($text);
        }
        else if ($this->copyText && $source->b !==null)
        {
            /** @var CharStream $b */
            $b = $source->b;
            $t->setText($b->getText(new Interval($start, $stop)));
        }

        return $t;
    }

    function createThin($type, $text)
    {
        $t = new CommonToken(null, $type);
        $t->setText($text);
        return $t;
    }
}
