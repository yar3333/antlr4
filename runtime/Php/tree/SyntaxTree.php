<?php

namespace Antlr4\Tree;

use \Antlr4\Interval;

interface SyntaxTree extends Tree
{
    /**
     * Return an {@link Interval} indicating the index in the
     * {@link TokenStream} of the first and last token associated with this
     * subtree. If this node is a leaf, then the interval represents a single
     * token and has interval i..i for token index i.
     *
     * <p>An interval of i..i-1 indicates an empty interval at position
     * i in the input stream, where 0 &lt;= i &lt;= the size of the input
     * token stream.  Currently, the code base can only have i=0..n-1 but
     * in concept one could have an empty interval after EOF. </p>
     *
     * <p>If source interval is unknown, this returns {@link Interval#INVALID}.</p>
     *
     * <p>As a weird special case, the source interval for rules matched after
     * EOF is unspecified.</p>
     */
    function getSourceInterval() : Interval;
}