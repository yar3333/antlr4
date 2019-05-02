<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\IntervalSet;
use Antlr4\Token;

class SetTransition extends Transition
{
    public $serializationType;

    /**
     * @var IntervalSet
     */
    public $label;

    // A transition containing a set of values.
    function __construct($target, IntervalSet $set)
    {
        parent::__construct($target);

        $this->serializationType = Transition::SET;
        if ($set) {
            $this->label = $set;
        } else {
            $this->label = new IntervalSet();
            $this->label->addOne(Token::INVALID_TYPE);
        }
    }

    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return mb_strpos($this->label, $symbol) !== false;
    }


    function __toString()
    {
        return (string)$this->label;
    }
}