<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn;

class ATNDeserializationOptions
{
    /**
     * @var ATNDeserializationOptions
     */
    private static $defaultOptions;

    public $readOnly;
    public $verifyATN;
    public $generateRuleBypassTransitions;

    public static function defaultOptions() : ATNDeserializationOptions
    {
        if (!isset(self::$defaultOptions))
        {
            self::$defaultOptions = new ATNDeserializationOptions();
            self::$defaultOptions->readOnly = true;
        }
        return self::$defaultOptions;
    }

    function __construct(ATNDeserializationOptions $copyFrom=null)
    {
        $this->readOnly = false;
        $this->verifyATN = !isset($copyFrom) ? true : $copyFrom->verifyATN;
        $this->generateRuleBypassTransitions = !isset($copyFrom) ? false : $copyFrom->generateRuleBypassTransitions;
    }

    //    def __setattr__(self, key, value):
    //        if key!="readOnly" and self.readOnly:
    //            raise Exception("The object is read only.")
    //        super(type(self), self).__setattr__(key,value)
}
