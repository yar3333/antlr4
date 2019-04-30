<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Error;

use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Recognizer;

class ErrorStrategy
{
	function __construct() {}

    /**
     * @param Recognizer $recognizer
     */
    function reset($recognizer)
    {
    }

    /**
     * @param Recognizer $recognizer
     */
    function recoverInline($recognizer)
    {
    }

    /**
     * @param Recognizer $recognizer
     * @param RecognitionException $e
     */
    function recover($recognizer, $e)
    {
    }

    /**
     * @param Recognizer $recognizer
     */
    function sync($recognizer)
    {
    }

    /**
     * @param Recognizer $recognizer
     */
    function inErrorRecoveryMode($recognizer)
    {
    }

    /**
     * @param Recognizer $recognizer
     * @param RecognitionException $e
     */
    function reportError($recognizer, $e)
    {
    }
}
