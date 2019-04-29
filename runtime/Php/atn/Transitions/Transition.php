<?php

namespace Antlr4\Atn\Transitions;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
//

//  An ATN transition between any two ATN states.  Subclasses define
//  atom, set, epsilon, action, predicate, rule transitions.
//
//  <p>This is a one way link.  It emanates from a state (usually via a list of
//  transitions) and has a target state.</p>
//
//  <p>Since we never have to change the ATN transitions once we construct it,
//  we can fix these transitions as specific classes. The DFA transitions
//  on the other hand need to update the labels as it adds transitions to
//  the states. We'll use the term Edge for the DFA to distinguish them from
//  ATN transitions.</p>

use Antlr4\Token; //('./../Token').Token;
use Antlr4\Interval; //('./../IntervalSet').Interval;
use Antlr4\IntervalSet; //('./../IntervalSet').IntervalSet;
use Antlr4\Predicate; //('./SemanticContext').Predicate;
use Antlr4\PrecedencePredicate;

class Transition
{
    const EPSILON = 1;
    const RANGE = 2;
    const RULE = 3;
    const PREDICATE = 4;// e.g., {isType(input.LT(1))}?
    const ATOM = 5;
    const ACTION = 6;
    const SET = 7;// ~(A|B) or ~atom, wildcard, which convert to next 2
    const NOT_SET = 8;
    const WILDCARD = 9;
    const PRECEDENCE = 10;

    const serializationNames = [
        "INVALID",
        "EPSILON",
        "RANGE",
        "RULE",
        "PREDICATE",
        "ATOM",
        "ACTION",
        "SET",
        "NOT_SET",
        "WILDCARD",
        "PRECEDENCE"
    ];

    const serializationTypes =
    [
        'EpsilonTransition' => Transition::EPSILON,
        'RangeTransition' => Transition::RANGE,
        'RuleTransition' => Transition::RULE,
        'PredicateTransition' => Transition::PREDICATE,
        'AtomTransition' => Transition::ATOM,
        'ActionTransition' => Transition::ACTION,
        'SetTransition' => Transition::SET,
        'NotSetTransition' => Transition::NOT_SET,
        'WildcardTransition' => Transition::WILDCARD,
        'PrecedencePredicateTransition' => Transition::PRECEDENCE
    ];

    public $target;
    public $isEpsilon;
    public $label;

    function __construct($target)
    {
        if (!isset($target)) throw new \Exception("target cannot be null.");

        $this->target = $target;

        // Are we epsilon, action, sempred?
        $this->isEpsilon = false;

        $this->label = null;
    }
}

// TODO: make all transitions sets? no, should remove set edges
class AtomTransition extends Transition
{
    public $serializationType;

    public $label_ = Transition::ATOM;

    function AtomTransition($target, $label)
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

class RuleTransition extends Transition
{
    public $serializationType;

    public $ruleIndex;
    public $precedence;
    public $followState;
    public $isEpsilon;

    function __construct($ruleStart, $ruleIndex, $precedence, $followState)
    {
        parent::__construct($ruleStart);

        $this->ruleIndex = $ruleIndex;// ptr to the rule definition object for this rule ref
        $this->precedence = $precedence;
        $this->followState = $followState;// what node to begin computations following ref to rule
        $this->serializationType = Transition::RULE;
        $this->isEpsilon = true;

        return $this;
    }

    function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return false;
    }
}

class EpsilonTransition extends Transition
{
    public $serializationType;

    public $outermostPrecedenceReturn;

    function __construct($target, $outermostPrecedenceReturn)
    {
        parent::__construct($target);

        $this->serializationType = Transition::EPSILON;
        $this->isEpsilon = true;
        $this->outermostPrecedenceReturn = $outermostPrecedenceReturn;

        return $this;
    }

    function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return false;
    }

    function __toString()
    {
        return "epsilon";
    }
}

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

    function RangeTransition($target, int $start, int $stop)
    {
        parent::__construct($target);

        $this->serializationType = Transition::RANGE;
        $this->start = $start;
        $this->stop = $stop;
        $this->label = $this->makeLabel();
    }

    /* RangeTransition */function makeLabel()
    {
        $s = new IntervalSet();
        $s->addRange($this->start, $this->stop);
        return $s;
    }

    /* RangeTransition */function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return $symbol >= $this->start && $symbol <= $this->stop;
    }

    /* RangeTransition */function __toString()
    {
        return "'" . String.fromCharCode($this->start) . "'..'" . String.fromCharCode($this->stop) . "'";
    }
}

class AbstractPredicateTransition extends Transition
{
    public $serializationType;

    function AbstractPredicateTransition($target)
    {
        parent::__construct($target);
    }
}

class PredicateTransition extends AbstractPredicateTransition
{
    public $serializationType;

    function PredicateTransition($target, $ruleIndex, $predIndex, $isCtxDependent)
    {
        parent::__construct($target);

        $this->serializationType = Transition::PREDICATE;
        $this->ruleIndex = $ruleIndex;
        $this->predIndex = $predIndex;
        $this->isCtxDependent = $isCtxDependent;// e.g., $i ref in pred
        $this->isEpsilon = true;
    }

    /* PredicateTransition */function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return false;
    }

    /* PredicateTransition */function getPredicate()
    {
        return new Predicate($this->ruleIndex, $this->predIndex, $this->isCtxDependent);
    }

    /* PredicateTransition */function __toString()
    {
        return "pred_" + this.ruleIndex + ":" . $this->predIndex;
    }
}

class ActionTransition extends Transition
{
    public $serializationType;

    function ActionTransition($target, $ruleIndex, $actionIndex, $isCtxDependent)
    {
        parent::__construct($target);

        $this->serializationType = Transition::ACTION;
        $this->ruleIndex = $ruleIndex;
        $this->actionIndex = $actionIndex===undefined ? -1 : $actionIndex;
        $this->isCtxDependent = $isCtxDependent===undefined ? false : $isCtxDependent;// e.g., $i ref in pred
        $this->isEpsilon = true;
    }

    /* ActionTransition */function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return false;
    }

    /* ActionTransition */function __toString()
    {
        return "action_" + this.ruleIndex + ":" . $this->actionIndex;
    }
}

class SetTransition extends Transition
{
    public $serializationType;

    // A transition containing a set of values.
    function SetTransition($target, $set)
    {
        parent::__construct($target);

        $this->serializationType = Transition::SET;
        if ($set!==undefined && $set!==null)
        {
            $this->label = $set;
        }
        else
        {
            $this->label = new IntervalSet();
            $this->label->addOne(Token::INVALID_TYPE);
        }
    }

    function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return $this->label->contains($symbol);
    }


    function __toString()
    {
        return (string)$this->label();
    }
}

class NotSetTransition extends SetTransition
{
    public $serializationType;

    function NotSetTransition($target, $set)
    {
        parent::__construct($target, $set);

        $this->serializationType = Transition::NOT_SET;
    }

    function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return $symbol >= $minVocabSymbol && $symbol <= $maxVocabSymbol &&
                !SetTransition::prototype->matches->call($this, $symbol, $minVocabSymbol, $maxVocabSymbol);
    }

    function __toString()
    {
        return '~' + SetTransition::prototype->toString->call($this);
    }
}

class WildcardTransition extends Transition
{
    public $serializationType;

    function WildcardTransition($target)
    {
        parent::__construct($target);

        $this->serializationType = Transition::WILDCARD;
    }

    function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return $symbol >= $minVocabSymbol && $symbol <= $maxVocabSymbol;
    }

    function __toString()
    {
        return ".";
    }
}

class PrecedencePredicateTransition extends AbstractPredicateTransition
{
    public $serializationType;

    public $precedence;

    function PrecedencePredicateTransition($target, $precedence)
    {
        parent::__construct($target);

        $this->serializationType = Transition::PRECEDENCE;
        $this->precedence = $precedence;
        $this->isEpsilon = true;
    }

    function matches($symbol, $minVocabSymbol,  $maxVocabSymbol)
    {
        return false;
    }

    function getPredicate()
    {
        return new PrecedencePredicate($this->precedence);
    }

    function __toString()
    {
        return $this->precedence . " >= _p";
    }
}
