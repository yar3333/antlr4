<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Error\Listeners;

// Provides an empty default implementation of {@link ANTLRErrorListener}.
// The default implementation of each method does nothing, but can be overridden as necessary.
class ErrorListener
{
    function __construct()
    {
    }

    function syntaxError($recognizer, $offendingSymbol, $line, $column, $msg, $e)
    {
    }

    function reportAmbiguity($recognizer, $dfa, $startIndex, $stopIndex, $exact, $ambigAlts, $configs)
    {
    }

    function reportAttemptingFullContext($recognizer, $dfa, $startIndex, $stopIndex, $conflictingAlts, $configs)
    {
    }

    function reportContextSensitivity($recognizer, $dfa, $startIndex, $stopIndex, $prediction, $configs)
    {
    }
}
