<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

class LexerModeAction extends LexerAction
{
    public $mode;

    function __construct($mode)
    {
        parent::__construct(LexerActionType::MODE);

        $this->mode = $mode;
    }

    // <p>This action is implemented by calling {@link Lexer//mode} with the
    // value provided by {@link //getMode}.</p>
    function execute(Lexer $lexer)
    {
        $lexer->mode($this->mode);
    }

    function updateHashCode($hash)
    {
        $hash->update($this->actionType, $this->mode);
    }

    function equals($other)
    {
        if ($this === $other) {
            return true;
        } else if (!($other instanceof LexerModeAction)) {
            return false;
        } else {
            return $this->mode === $other->mode;
        }
    }

    function __toString()
    {
        return "mode(" . $this->mode . ")";
    }
}