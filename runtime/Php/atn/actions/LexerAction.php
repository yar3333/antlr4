<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn\Actions;

use \Antlr4\Utils\Hash;
use \Antlr4\Lexer;

abstract class LexerAction
{
    public $actionType;
    public $isPositionDependent;

    function __construct(int $actionType)
    {
        $this->actionType = $actionType;
        $this->isPositionDependent = false;
    }

    function hashCode()
    {
        $hash = new Hash();
        $this->updateHashCode($hash);
        return $hash->finish();
    }

    function updateHashCode(Hash $hash)
    {
        $hash->update($this->actionType);
    }

    function equals(LexerAction $other)
    {
        return $this === $other;
    }

    abstract function execute(Lexer $lexer);
}

// Implements the {@code skip} lexer action by calling {@link Lexer//skip}.
// <p>The {@code skip} command does not have any parameters, so this action is implemented as a singleton instance exposed by {@link //INSTANCE}.</p>
class LexerSkipAction extends LexerAction
{
    function __construct()
    {
        parent::__construct(LexerActionType::SKIP);
    }

    // Provides a singleton instance of this parameterless lexer action.
    private static $_INSTANCE;
    public static function INSTANCE() { return self::$_INSTANCE ? self::$_INSTANCE : (self::$_INSTANCE = new LexerSkipAction()); }

    function execute(Lexer $lexer)
    {
        $lexer->skip();
    }

    function __toString()
    {
        return "skip";
    }
}

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
        if ($this === $other)
        {
            return true;
        }
        else if (!($other instanceof LexerTypeAction))
        {
            return false;
        }
        else
        {
            return $this->type === $other->type;
        }
    }

    function __toString()
    {
        return "type(" . $this->type . ")";
    }
}

class LexerPushModeAction extends LexerAction
{
    // Implements the {@code pushMode} lexer action by calling
    // {@link Lexer//pushMode} with the assigned mode.
    function __construct($mode)
    {
        parent::__construct(LexerActionType::PUSH_MODE);
        $this->mode = $mode;
        return $this;
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
        if ($this === $other)
        {
            return true;
        }
        else if (! ($other instanceof LexerPushModeAction))
        {
            return false;
        }
        else
        {
            return $this->mode === $other->mode;
        }
    }

    function __toString()
    {
        return "pushMode(" . $this->mode . ")";
    }
}

class LexerPopModeAction extends LexerAction
{
    // Implements the {@code popMode} lexer action by calling {@link Lexer//popMode}.
    //
    // <p>The {@code popMode} command does not have any parameters, so this action is
    // implemented as a singleton instance exposed by {@link //INSTANCE}.</p>
    function __construct()
    {
        parent::__construct(LexerActionType::POP_MODE);
        return $this;
    }

    private static $_INSTANCE;
    public static function INSTANCE() { return self::$_INSTANCE ? self::$_INSTANCE : (self::$_INSTANCE = new LexerPopModeAction()); }

    // <p>This action is implemented by calling {@link Lexer//popMode}.</p>
    function execute(Lexer $lexer)
    {
        $lexer->popMode();
    }

    function __toString()
    {
        return "popMode";
    }
}

// Implements the {@code more} lexer action by calling {@link Lexer//more}.
//
// <p>The {@code more} command does not have any parameters, so this action is
// implemented as a singleton instance exposed by {@link //INSTANCE}.</p>
class LexerMoreAction extends LexerAction
{
    function __construct()
    {
        parent::__construct(LexerActionType::MORE);
    }

    private static $_INSTANCE;
    public static function INSTANCE() { return self::$_INSTANCE ? self::$_INSTANCE : (self::$_INSTANCE = new LexerMoreAction()); }

    // <p>This action is implemented by calling {@link Lexer//popMode}.</p>
    function execute(Lexer $lexer)
    {
        $lexer->more();
    }

    function __toString()
    {
        return "more";
    }
}

// Implements the {@code mode} lexer action by calling {@link Lexer//mode} with
// the assigned mode.
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
        if ($this === $other)
        {
            return true;
        }
        else if (! ($other instanceof LexerModeAction))
        {
            return false;
        }
        else
        {
            return $this->mode === $other->mode;
        }
    }

    function __toString()
    {
        return "mode(" . $this->mode . ")";
    }
}

// Executes a custom lexer action by calling {@link Recognizer//action} with the
// rule and action indexes assigned to the custom action. The implementation of
// a custom action is added to the generated code for the lexer in an override
// of {@link Recognizer//action} when the grammar is compiled.
//
// <p>This class may represent embedded actions created with the <code>{...}</code>
// syntax in ANTLR 4, as well as actions created for lexer commands where the
// command argument could not be evaluated when the grammar was compiled.</p>


// Constructs a custom lexer action with the specified rule and action
// indexes.
//
// @param ruleIndex The rule index to use for calls to
// {@link Recognizer//action}.
// @param actionIndex The action index to use for calls to
// {@link Recognizer//action}.
class LexerCustomAction extends LexerAction
{
    public $ruleIndex;
    public $actionIndex;

    function __construct($ruleIndex, $actionIndex)
    {
        parent::__construct(LexerActionType::CUSTOM);

        $this->ruleIndex = $ruleIndex;
        $this->actionIndex = $actionIndex;
        $this->isPositionDependent = true;
    }

    // <p>Custom actions are implemented by calling {@link Lexer//action} with the
    // appropriate rule and action indexes.</p>
    function execute(Lexer $lexer)
    {
        $lexer->action(null, $this->ruleIndex, $this->actionIndex);
    }

    function updateHashCode($hash)
    {
        $hash->update($this->actionType, $this->ruleIndex, $this->actionIndex);
    }

    function equals($other)
    {
        if ($this === $other)
        {
            return true;
        }
        else if (! ($other instanceof LexerCustomAction))
        {
            return false;
        }
        else
        {
            return $this->ruleIndex === $other->ruleIndex && $this->actionIndex === $other->actionIndex;
        }
    }
}

// Implements the {@code channel} lexer action by calling
// {@link Lexer//setChannel} with the assigned channel.
// Constructs a new {@code channel} action with the specified channel value.
// @param channel The channel value to pass to {@link Lexer//setChannel}.
class LexerChannelAction extends LexerAction
{
    public $channel;

    function __construct($channel)
    {
        parent::__construct(LexerActionType::CHANNEL);

        $this->channel = $channel;
    }

    // <p>This action is implemented by calling {@link Lexer//setChannel} with the value provided by {@link //getChannel}.</p>
    function execute($lexer)
    {
        $lexer->_channel = $this->channel;
    }

    function updateHashCode($hash)
    {
        $hash->update($this->actionType, $this->channel);
    }

    function equals($other)
    {
        if ($this === $other)
        {
            return true;
        }
        else if (! ($other instanceof LexerChannelAction))
        {
            return false;
        }
        else
        {
            return $this->channel === $other->channel;
        }
    }

    function __toString()
    {
        return "channel(" . $this->channel . ")";
    }
}

// This implementation of {@link LexerAction} is used for tracking input offsets
// for position-dependent actions within a {@link LexerActionExecutor}.
//
// <p>This action is not serialized as part of the ATN, and is only required for
// position-dependent lexer actions which appear at a location other than the
// end of a rule. For more information about DFA optimizations employed for
// lexer actions, see {@link LexerActionExecutor//append} and
// {@link LexerActionExecutor//fixOffsetBeforeMatch}.</p>

// Constructs a new indexed custom action by associating a character offset
// with a {@link LexerAction}.
//
// <p>Note: This class is only required for lexer actions for which
// {@link LexerAction//isPositionDependent} returns {@code true}.</p>
//
// @param offset The offset into the input {@link CharStream}, relative to
// the token start index, at which the specified lexer action should be
// executed.
// @param action The lexer action to execute at a particular offset in the
// input {@link CharStream}.
class LexerIndexedCustomAction extends LexerAction
{
    /**
     * @var int
     */
    public $offset;

    /**
     * @var LexerAction
     */
    public $action;

    function __construct(int $offset, LexerAction $action)
    {
        parent::__construct($action->actionType);

        $this->offset = $offset;
        $this->action = $action;
        $this->isPositionDependent = true;
    }

    // <p>This method calls {@link //execute} on the result of {@link //getAction} using the provided {@code lexer}.</p>
    function execute(Lexer $lexer)
    {
        // assume the input stream position was properly set by the calling code
        $this->action->execute($lexer);
    }

    function updateHashCode(Hash $hash)
    {
        $hash->update($this->actionType, $this->offset, $this->action);
    }

    function equals(LexerAction $other)
    {
        if ($this === $other)
        {
            return true;
        }
        else if (!($other instanceof LexerIndexedCustomAction))
        {
            return false;
        }
        else
        {
            return $this->offset === $other->offset && $this->action === $other->action;
        }
    }
}
