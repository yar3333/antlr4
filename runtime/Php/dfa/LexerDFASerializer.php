<?php

namespace Antlr4\Dfa;

use Antlr4\Utils\Utils;

class LexerDFASerializer extends DFASerializer
{
    function __construct(DFA $dfa)
    {
        parent::__construct($dfa);
    }

    /* LexerDFASerializer */
    function getEdgeLabel($i)
    {
        return "'" . Utils::fromCharCode($i) . "'";
    }
}