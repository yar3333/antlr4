<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\IntervalSet;

class AtomTransition extends Transition
{
    public $serializationType;

    public $label_ = Transition::ATOM;

    function __construct($target, $label)
    {
        parent::__construct($target);

        $this->label_ = $label;// The token type or character value; or, signifies special label.
        $this->label = $this->makeLabel();
        $this->serializationType = Transition::ATOM;
    }

    function makeLabel()
    {
        $s = new IntervalSet();
        $s->addOne($this->label_);
        return $s;
    }

    function matches($symbol, $minVocabSymbol, $maxVocabSymbol)
    {
        return $this->label_ === $symbol;
    }

    function __toString()
    {
        return (string)$this->label_;
    }
}