<?php

namespace Antlr4\Error\Listeners;

class ConsoleErrorListener extends ErrorListener
{
    private static $_INSTANCE;

    public static function INSTANCE(): ConsoleErrorListener
    {
        return self::$_INSTANCE ? self::$_INSTANCE : (self::$_INSTANCE = new ConsoleErrorListener());
    }

    function __construct()
    {
        parent::__construct();
    }

    // {@inheritDoc}
    //
    // <p>
    // This implementation prints messages to {@link System//err} containing the
    // values of {@code line}, {@code charPositionInLine}, and {@code msg} using
    // the following format.</p>
    //
    // <pre>
    // line <em>line</em>:<em>charPositionInLine</em> <em>msg</em>
    // </pre>
    function syntaxError($recognizer, $offendingSymbol, $line, $column, $msg, $e)
    {
        //$console->error("line " + line + ":" + column + " " . $msg);
    }
}