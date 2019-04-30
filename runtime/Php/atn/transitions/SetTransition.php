<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\IntervalSet;
use Antlr4\Token;
use Antlr4\Utils\Set;

class SetTransition extends Transition
{
    public $serializationType;

    /**
     * @var Set
     */
    public $label;

    // A transition containing a set of values.
    function __construct($target, $set)
    {
        parent::__construct($target);

        $this->serializationType = Transition::SET;
        if (isset($set)) {
            $this->label = $set;
        } else {
            $this->label = new IntervalSet();
            $this->label->addOne(Token::INVALID_TYPE);
        }
    }

    function matches($symbol, $minVocabSymbol, $maxVocabSymbol)
    {
        return mb_strpos($this->label, $symbol) !== false;
    }


    function __toString()
    {
        return (string)$this->label;
    }
}