<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Error;

class ErrorStrategy
{
	function __construct() {}

    function reset($recognizer)
    {
    }

    function recoverInline($recognizer)
    {
    }

    function recover($recognizer, $e)
    {
    }

    function sync($recognizer)
    {
    }

    function inErrorRecoveryMode($recognizer)
    {
    }

    function reportError($recognizer)
    {
    }
}
