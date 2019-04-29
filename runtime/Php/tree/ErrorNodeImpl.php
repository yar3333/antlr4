<?php

namespace Antlr4\Tree;

// Represents a token that was consumed during resynchronization
// rather than during a valid match operation. For example,
// we will create this kind of a node during single token insertion
// and deletion as well as during "consume until error recovery set"
// upon no viable alternative exceptions.
class ErrorNodeImpl extends TerminalNodeImpl
{
    function __construct($token)
    {
        parent::__construct($token);
    }

    function isErrorNode()
    {
        return true;
    }

    function accept($visitor)
    {
        return $visitor->visitErrorNode($this);
    }
}