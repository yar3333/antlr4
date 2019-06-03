<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4;

use Antlr4\Utils\Set;

class BufferedTokenStream implements TokenStream
{
    protected $tokenSource;

    /**
     * @var Token[]
     */
    public $tokens;

    /**
     * @var int
     */
    public $index;

    /**
     * @var bool
     */
    public $fetchedEOF;

    function __construct(TokenSource $tokenSource)
    {
        // The {@link TokenSource} from which tokens for this stream are fetched.
        $this->tokenSource = $tokenSource;

        // A collection of all tokens fetched from the token source. The list is
        // considered a complete view of the input once {@link //fetchedEOF} is set
        // to {@code true}.
        $this->tokens = [];

        // The index into {@link //tokens} of the current token (next token to
        // {@link //consume}). {@link //tokens}{@code [}{@link //p}{@code ]} should
        // be
        // {@link //LT LT(1)}.
        //
        // <p>This field is set to -1 when the stream is first constructed or when
        // {@link //setTokenSource} is called, indicating that the first token has
        // not yet been fetched from the token source. For additional information,
        // see the documentation of {@link IntStream} for a description of
        // Initializing Methods.</p>
        $this->index = -1;

        // Indicates whether the {@link Token//EOF} token has been fetched from
        // {@link //tokenSource} and added to {@link //tokens}. This field improves
        // performance for the following cases:
        //
        // <ul>
        // <li>{@link //consume}: The lookahead check in {@link //consume} to
        // prevent
        // consuming the EOF symbol is optimized by checking the values of
        // {@link //fetchedEOF} and {@link //p} instead of calling {@link
        // //LA}.</li>
        // <li>{@link //fetch}: The check to prevent adding multiple EOF symbols
        // into
        // {@link //tokens} is trivial with this field.</li>
        // <ul>
        $this->fetchedEOF = false;
    }

    function mark() : int
    {
        return 0;
    }

    function release(int $marker) : void
    {
    // no resources to release
    }

    function reset() : void
    {
        $this->seek(0);
    }

    function seek(int $index) : void
    {
        $this->lazyInit();
        $this->index = $this->adjustSeekIndex($index);
    }

    function get(int $index) : Token
    {
        $this->lazyInit();
        return $this->tokens[$index];
    }

    function consume() : void
    {
        $skipEofCheck = false;

        if ($this->index >= 0)
        {
            if ($this->fetchedEOF)
            {
                // the last token in tokens is EOF. skip check if p indexes any
                // fetched token except the last.
                $skipEofCheck = $this->index < count($this->tokens) - 1;
            }
            else
            {
                // no EOF token in tokens. skip check if p indexes a fetched token.
                $skipEofCheck = $this->index < count($this->tokens);
            }
        }

        if (!$skipEofCheck && $this->LA(1) === Token::EOF)
        {
            throw new \Exception("cannot consume EOF");
        }
        if ($this->sync($this->index + 1))
        {
            $this->index = $this->adjustSeekIndex($this->index + 1);
        }
    }

    // Make sure index {@code i} in tokens has a token.
    //
    // @return {@code true} if a token is located at index {@code i}, otherwise
    // {@code false}.
    // @see //get(int i)
    // /
    function sync($i) : bool
    {
        $n = $i - count($this->tokens) + 1;// how many more elements we need?
        if ($n > 0)
        {
            $fetched = $this->fetch($n);
            return $fetched >= $n;
        }
        return true;
    }

    // Add {@code n} elements to buffer.
    //
    // @return The actual number of elements added to the buffer.
    // /
    function fetch($n)
    {
        if ($this->fetchedEOF)
        {
            return 0;
        }
        for ($i = 0; $i < $n; $i++)
        {
            $t = $this->tokenSource->nextToken();
            $t->tokenIndex = count($this->tokens);
            array_push($this->tokens, $t);
            if ($t->type === Token::EOF)
            {
                $this->fetchedEOF = true;
                return $i + 1;
            }
        }
        return $n;
    }

    // Get all tokens from start..stop inclusively
    function getTokens(int $start, int $stop, ?Set $types)
    {
        if (!isset($types))
        {
            $types = null;
        }
        if ($start < 0 || $stop < 0)
        {
            return null;
        }
        $this->lazyInit();
        $subset = [];
        if ($stop >= count($this->tokens))
        {
            $stop = count($this->tokens) - 1;
        }
        for ($i = $start; $i < $stop; $i++)
        {
            $t = $this->tokens[$i];
            if ($t->type === Token::EOF)
            {
                break;
            }
            if ($types === null || $types->contains($t->type))
            {
                array_push($subset, $t);
            }
        }
        return $subset;
    }

    function LA(int $i) : int
    {
        return $this->LT($i)->type;
    }

    function LB(int $k) : Token
    {
        if ($this->index - $k < 0)
        {
            return null;
        }
        return $this->tokens[$this->index - $k];
    }

    function LT(int $k) : Token
    {
        $this->lazyInit();

        if ($k === 0) return null;
        if ($k < 0) return $this->LB(-$k);

        $i = $this->index + $k - 1;
        $this->sync($i);
        if ($i >= count($this->tokens))
        {
            // return EOF token
            // EOF must be last token
            return $this->tokens[count($this->tokens) - 1];
        }
        return $this->tokens[$i];
    }

    // Allowed derived classes to modify the behavior of operations which change
    // the current stream position by adjusting the target token index of a seek
    // operation. The default implementation simply returns {@code i}. If an
    // exception is thrown in this method, the current stream index should not be
    // changed.
    //
    // <p>For example, {@link CommonTokenStream} overrides this method to ensure
    // that
    // the seek target is always an on-channel token.</p>
    //
    // @param i The target token index.
    // @return The adjusted target token index.

    function adjustSeekIndex($i)
    {
        return $i;
    }

    function lazyInit() : void
    {
        if ($this->index === -1)
        {
            $this->setup();
        }
    }

    function setup() : void
    {
        $this->sync(0);
        $this->index = $this->adjustSeekIndex(0);
    }

    // Reset this token stream by setting its token source.///
    function setTokenSource($tokenSource) : void
    {
        $this->tokenSource = $tokenSource;
        $this->tokens = [];
        $this->index = -1;
        $this->fetchedEOF = false;
    }


    // Given a starting index, return the index of the next token on channel.
    // Return i if tokens[i] is on channel. Return -1 if there are no tokens
    // on channel between i and EOF.
    function nextTokenOnChannel(int $i, int $channel) : int
    {
        $this->sync($i);

        if ($i >= count($this->tokens)) return $this->size() - 1;

        $token = $this->tokens[$i];
        while ($token->channel !== $channel)
        {
            if ($token->type === Token::EOF) return $i;
            $i++;
            $this->sync($i);
            $token = $this->tokens[$i];
        }
        return $i;
    }

    // Given a starting index, return the index of the previous token on channel.
    // Return i if tokens[i] is on channel. Return -1 if there are no tokens
    // on channel between i and 0.
    function previousTokenOnChannel($i, $channel)
    {
        while ($i >= 0 && $this->tokens[$i]->channel !== $channel)
        {
            $i--;
        }
        return $i;
    }

    // Collect all tokens on specified channel to the right of
    // the current token up until we see a token on DEFAULT_TOKEN_CHANNEL or
    // EOF. If channel is -1, find any non default channel token.
    function getHiddenTokensToRight($tokenIndex, $channel)
    {
        if (!isset($channel))
        {
            $channel = -1;
        }
        $this->lazyInit();
        if ($tokenIndex < 0 || $tokenIndex >= count($this->tokens))
        {
            throw new \Exception( $tokenIndex . " not in 0.." . (count($this->tokens) - 1));
        }
        $nextOnChannel = $this->nextTokenOnChannel($tokenIndex + 1, Lexer::DEFAULT_TOKEN_CHANNEL);
        $from_ = $tokenIndex + 1;
        // if none onchannel to right, nextOnChannel=-1 so set to = last token
        $to = $nextOnChannel === -1 ? count($this->tokens) - 1 : $nextOnChannel;
        return $this->filterForChannel($from_, $to, $channel);
    }

    // Collect all tokens on specified channel to the left of
    // the current token up until we see a token on DEFAULT_TOKEN_CHANNEL.
    // If channel is -1, find any non default channel token.
    function getHiddenTokensToLeft(int $tokenIndex, int $channel)
    {
        if (!isset($channel)) $channel = -1;

        $this->lazyInit();

        if ($tokenIndex < 0 || $tokenIndex >= count($this->tokens))
        {
            throw new \Exception($tokenIndex . " not in 0.." . (count($this->tokens) - 1));
        }

        $prevOnChannel = $this->previousTokenOnChannel($tokenIndex - 1, Lexer::DEFAULT_TOKEN_CHANNEL);
        if ($prevOnChannel === $tokenIndex - 1) return null;

        // if none on channel to left, prevOnChannel=-1 then from=0
        $from_ = $prevOnChannel + 1;
        $to = $tokenIndex - 1;

        return $this->filterForChannel($from_, $to, $channel);
    }

    function filterForChannel($left, $right, $channel)
    {
        $hidden = [];
        for ($i = $left; $i < $right + 1; $i++)
        {
            $t = $this->tokens[$i];
            if ($channel === -1)
            {
                if ($t->channel !== Lexer::DEFAULT_TOKEN_CHANNEL)
                {
                    array_push($hidden, $t);
                }
            }
            else if ($t->channel === $channel)
            {
                array_push($hidden, $t);
            }
        }
        if (count($hidden) === 0)
        {
            return null;
        }
        return $hidden;
    }

    function getSourceName() : string
    {
        return $this->tokenSource->getSourceName();
    }

    // Get the text of all tokens in this buffer.
    function getTextByInterval(Interval $interval) : string
    {
        $this->lazyInit();
        $this->fill();

        $start = $interval->start;
        if ($start instanceof Token)
        {
            $start = $start->tokenIndex;
        }

        $stop = $interval->stop;
        if ($stop instanceof Token)
        {
            $stop = $stop->tokenIndex;
        }

        if ($start === null || $stop === null || $start < 0 || $stop < 0)
        {
            return "";
        }

        if ($stop >= count($this->tokens))
        {
            $stop = count($this->tokens) - 1;
        }

        $s = "";
        for ($i = $start; $i < $stop + 1; $i++)
        {
            $t = $this->tokens[$i];
            if ($t->type === Token::EOF)
            {
                break;
            }
            $s .= $t->text;
        }
        return $s;
    }

    function getText() : string { return $this->getTextByInterval(new Interval(0, count($this->tokens) - 1)); }

    function getTextByTokens(Token $start, Token $stop) : string
    {
        if ($start!==null && $stop!==null )
        {
            return $this->getTextByInterval(new Interval($start->tokenIndex, $stop->tokenIndex));
        }

        return "";
    }

    function getTextByContext(RuleContext $ctx): string { return $this->getTextByInterval($ctx->getSourceInterval()); }

    // Get all tokens from lexer until EOF
    function fill() : void
    {
        $this->lazyInit();
        while ($this->fetch(1000) === 1000) continue;
    }

    function tokenSource(): TokenSource { return $this->tokenSource; }

    function index() : int { return $this->index; }

    function size() : int { return count($this->tokens); }
}