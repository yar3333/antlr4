<?php

namespace Antlr4;

/** A source of characters for an ANTLR lexer. */
interface CharStream extends IntStream
{
    /**
     * This method returns the text for a range of characters within this input
     * stream. This method is guaranteed to not throw an exception if the
     * specified {@code interval} lies entirely within a marked range. For more
     * information about marked ranges, see {@link IntStream#mark}.
     *
     * @param Interval $interval an interval within the stream
     * @return string the text of the specified interval
     */
    public function getText(Interval $interval) : string;
}
