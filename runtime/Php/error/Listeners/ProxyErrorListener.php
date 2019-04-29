<?php

namespace Antlr4\Error\Listeners;

class ProxyErrorListener extends ErrorListener
{
    /**
     * @var ErrorListener[]
     */
    public $delegates;

    function __construct(array $delegates)
    {
        parent::__construct();

        if ($delegates === null) throw new \Exception("delegates");

        $this->delegates = $delegates;
    }

    function syntaxError($recognizer, $offendingSymbol, $line, $column, $msg, $e)
    {
        foreach ($this->delegates as $d)
        {
            $d->syntaxError($recognizer, $offendingSymbol, $line, $column, $msg, $e);
        }
    }

    function reportAmbiguity($recognizer, $dfa, $startIndex, $stopIndex, $exact, $ambigAlts, $configs)
    {
        foreach ($this->delegates as $d)
        {
            $d->reportAmbiguity($recognizer, $dfa, $startIndex, $stopIndex, $exact, $ambigAlts, $configs);
        }
    }

    function reportAttemptingFullContext($recognizer, $dfa, $startIndex, $stopIndex, $conflictingAlts, $configs)
    {
        foreach ($this->delegates as $d)
        {
            $d->reportAttemptingFullContext($recognizer, $dfa, $startIndex, $stopIndex, $conflictingAlts, $configs);
        }
    }

    function reportContextSensitivity($recognizer, $dfa, $startIndex, $stopIndex, $prediction, $configs)
    {
        foreach ($this->delegates as $d)
        {
            $d->reportContextSensitivity($recognizer, $dfa, $startIndex, $stopIndex, $prediction, $configs);
        }
    }
}