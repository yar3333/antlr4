<?php

namespace Antlr4\Predictioncontexts;

class EmptyPredictionContext extends SingletonPredictionContext
{
    function __construct()
    {
        parent::__construct(null, PredictionContext::EMPTY_RETURN_STATE);
    }

    function isEmpty()
    {
        return true;
    }

    function getParent(int $index=null) : PredictionContext
    {
        return null;
    }

    function getReturnState(int $index): int
    {
        return $this->returnState;
    }

    function equals($other) : bool
    {
        return $this === $other;
    }

    function __toString()
    {
        return "$";
    }
}