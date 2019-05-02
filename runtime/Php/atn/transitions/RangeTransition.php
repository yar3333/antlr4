<?php

namespace Antlr4\Atn\Transitions;

use Antlr4\Interval;
use Antlr4\IntervalSet;
use Antlr4\Utils\Utils;

class RangeTransition extends Transition
{
    public $serializationType;

    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $stop;

    function __construct($target, int $start, int $stop)
    {
        parent::__construct($target);

        $this->serializationType = Transition::RANGE;
        $this->start = $start;
        $this->stop = $stop;
    }

    function label() : IntervalSet { return IntervalSet::fromRange($this->start, $this->stop); }

    /* RangeTransition */
    function matches(int $symbol, int $minVocabSymbol, int $maxVocabSymbol) : bool
    {
        return $symbol >= $this->start && $symbol <= $this->stop;
    }

    /* RangeTransition */
    function __toString()
    {
        return "'" . Utils::fromCharCode($this->start) . "'..'" . Utils::fromCharCode($this->stop) . "'";
    }
}