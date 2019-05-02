<?php

namespace Antlr4\Atn\Actions;

use Antlr4\Lexer;

// Implements the {@code pushMode} lexer action by calling
// {@link Lexer//pushMode} with the assigned mode.
class LexerPushModeAction extends LexerAction
{
    private $mode;

    function __construct(int $mode)
    {
        parent::__construct(LexerActionType::PUSH_MODE);

        $this->mode = $mode;
    }

    // <p>This action is implemented by calling {@link Lexer//pushMode} with the value provided by {@link //getMode}.</p>
    function execute(Lexer $lexer)
    {
        $lexer->pushMode($this->mode);
    }

    function updateHashCode($hash)
    {
        $hash->update($this->actionType, $this->mode);
    }

    function equals(LexerAction $other)
    {
        if ($this === $other) {
            return true;
        } else if (!($other instanceof LexerPushModeAction)) {
            return false;
        } else {
            return $this->mode === $other->mode;
        }
    }

    function __toString()
    {
        return "pushMode(" . $this->mode . ")";
    }
}