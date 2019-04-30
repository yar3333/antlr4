<?php

namespace Antlr4\Predictioncontexts;

use Antlr4\Utils\Hash;

// Used to cache {@link PredictionContext} objects. Its used for the shared
// context cash associated with contexts in DFA states. This cache
// can be used for both lexers and parsers.
class SingletonPredictionContext extends PredictionContext
{
    /**
     * @var self
     */
    public $parentCtx;

    /**
     * @var int
     */
    public $returnState;

    function __construct(self $parent, int $returnState)
    {
        $hashCode = 0;
        if ($parent !== null) {
            $hash = new Hash();
            $hash->update($parent, $returnState);
            $hashCode = $hash->finish();
        }

        parent::__construct($hashCode);

        $this->parentCtx = $parent;
        $this->returnState = $returnState;
    }

    static function create($parent, $returnState)
    {
        if ($returnState === PredictionContext::EMPTY_RETURN_STATE && $parent === null) {
            // someone can pass in the bits of an array ctx that mean $
            return PredictionContext::EMPTY;
        } else {
            return new SingletonPredictionContext($parent, $returnState);
        }
    }

    function getLength(): int
    {
        return 1;
    }

    function getParent(int $index): PredictionContext
    {
        return $this->parentCtx;
    }

    function getReturnState(int $index): int
    {
        return $this->returnState;
    }

    function equals(?PredictionContext $other)
    {
        if ($this === $other) {
            return true;
        } else if (!($other instanceof SingletonPredictionContext)) {
            return false;
        } else if ($this->hashCode() !== $other->hashCode()) {
            return false;// can't be same if hash is different
        } else {
            if ($this->returnState !== $other->returnState) return false;
            else if ($this->parentCtx == null) return $other->parentCtx == null;
            else return $this->parentCtx->equals($other->parentCtx);
        }
    }

    function __toString()
    {
        $up = $this->parentCtx === null ? "" : (string)$this->parentCtx;
        if ($up === "") {
            if ($this->returnState === PredictionContext::EMPTY_RETURN_STATE) {
                return "$";
            } else {
                return "" . $this->returnState;
            }
        } else {
            return "" . $this->returnState . " " . $up;
        }
    }
}