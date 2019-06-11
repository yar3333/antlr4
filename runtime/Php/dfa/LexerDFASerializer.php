<?php

namespace Antlr4\Dfa;

use Antlr4\Utils\Utils;

class LexerDFASerializer extends DFASerializer
{
    function __construct(DFA $dfa)
    {
        parent::__construct($dfa);
    }

    function getEdgeLabel(int $i) : string
    {
        return "'" . Utils::fromCharCode($i) . "'";
    }
}