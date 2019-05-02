<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;
use Antlr4\Utils\Hash;

//  Implements the {@code type} lexer action by calling {@link Lexer//setType} with the assigned type.
class LexerTypeAction extends LexerAction
{
    public $type;

    function __construct($type)
    {
        parent::__construct(LexerActionType::TYPE);
        $this->type = $type;
    }

    function execute(Lexer $lexer)
    {
        $lexer->setType($this->type);
    }

    function updateHashCode(Hash $hash)
    {
        $hash->update($this->actionType, $this->type);
    }

    function equals(LexerAction $other)
    {
        if ($this === $other) {
            return true;
        } else if (!($other instanceof LexerTypeAction)) {
            return false;
        } else {
            return $this->type === $other->type;
        }
    }

    function __toString()
    {
        return "type(" . $this->type . ")";
    }
}