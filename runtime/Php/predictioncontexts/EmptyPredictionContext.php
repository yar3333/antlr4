<?php

namespace Antlr4\Predictioncontexts;

class EmptyPredictionContext extends SingletonPredictionContext
{
    private static $_EMPTY;

    public static function EMPTY()
    {
        return self::$_EMPTY ? self::$_EMPTY : (self::$_EMPTY = new EmptyPredictionContext());
    }

    function __construct()
    {
        parent::__construct(null, PredictionContext::EMPTY_RETURN_STATE);
    }

    function isEmpty()
    {
        return true;
    }

    function getParent(int $index): PredictionContext
    {
        return null;
    }

    function getReturnState(int $index): int
    {
        return $this->returnState;
    }

    function equals($other)
    {
        return $this === $other;
    }

    function __toString()
    {
        return "$";
    }
}