<?php

namespace Antlr4\Predictioncontexts;

use \Antlr4\Utils\Hash;

class ArrayPredictionContext extends PredictionContext
{
    /**
     * @var array
     */
    public $parents;

    /**
     * @var int[]
     */
    public $returnStates;

    /**
     * ArrayPredictionContext constructor.
     * @param array $parents
     * @param int[] $returnStates
     */
    function __construct(array $parents, array $returnStates)
    {
        // Parent can be null only if full ctx mode and we make an array
        // from {@link //EMPTY} and non-empty. We merge {@link //EMPTY}
        // by using null parent and// returnState == {@link //EMPTY_RETURN_STATE}.

        $h = new Hash();
        $h->update($parents, $returnStates);
        $hashCode = $h->finish();

        parent::__construct($hashCode);

        $this->parents = $parents;
        $this->returnStates = $returnStates;
    }

    public static function fromOne(SingletonPredictionContext $a) : self
    {
        return new ArrayPredictionContext([$a->getParent()], [$a->returnState]);
    }

    function isEmpty() : bool
    {
        // since EMPTY_RETURN_STATE can only appear in the last position, we don't need to verify that size==1
        return $this->returnStates[0] === PredictionContext::EMPTY_RETURN_STATE;
    }

    function getLength(): int
    {
        return count($this->returnStates);
    }

    function getParent(int $index=null) : PredictionContext
    {
        return $this->parents[$index];
    }

    function getReturnState(int $index): int
    {
        return $this->returnStates[$index];
    }

    /**
     * @return int[]
     */
    function getReturnStates() : array
    {
        return $this->returnStates;
    }

    function equals($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof ArrayPredictionContext)) return false;
        if ($this->hashCode() !== $other->hashCode()) return false;

        return $this->returnStates === $other->returnStates &&
               $this->parents === $other->parents;
    }

    function __toString()
    {
        if ($this->isEmpty()) return "[]";

        $s = "[";
        for ($i = 0; $i < count($this->returnStates); $i++) {
            if ($i > 0) {
                $s .= ", ";
            }
            if ($this->returnStates[$i] === PredictionContext::EMPTY_RETURN_STATE) {
                $s .= '$';
                continue;
            }
            $s .= $this->returnStates[$i];
            if ($this->parents[$i] !== null) {
                $s .= " " . $this->parents[$i];
            } else {
                $s .= "null";
            }
        }
        return $s . "]";
    }
}