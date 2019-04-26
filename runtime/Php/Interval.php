<?php

namespace Antlr4;

class Interval
{
    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $stop;

    /* stop is not included! */
    function __construct(int $start, int $stop)
    {
        $this->start = $start;
        $this->stop = $stop;
    }

    function contains(int $item)
    {
        return $item >= $this->start && $item < $this->stop;
    }

    function __toString() : string
    {
        if ($this->start === $this->stop - 1) {
            return $this->start;
        } else {
            return $this->start . ".." . ($this->stop - 1);
        }
    }

    function getLength() : int
    {
        return $this->stop - $this->start;
    }
}