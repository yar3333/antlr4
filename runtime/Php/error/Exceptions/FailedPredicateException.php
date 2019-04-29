<?php

namespace Antlr4\Error\Exceptions;

use Antlr4\Atn\Transitions\PredicateTransition;
use Antlr4\Parser;

// A semantic predicate failed during validation. Validation of predicates
// occurs when normally parsing the alternative just like matching a token.
// Disambiguating predicate evaluation occurs when we test a predicate during
// prediction.
class FailedPredicateException extends RecognitionException
{
    function __construct(Parser $recognizer, $predicate, $message)
    {
        parent::__construct((object)[
            'message' => $this->formatMessage($predicate, $message || null),
            'recognizer' => $recognizer,
            'input' => $recognizer->getInputStream(),
            'ctx' => $recognizer->_ctx
        ]);
        $s = $recognizer->_interp->atn->states[$recognizer->getState()];
        $trans = $s->transitions[0];
        if ($trans instanceof PredicateTransition) {
            $this->ruleIndex = $trans->ruleIndex;
            $this->predicateIndex = $trans->predIndex;
        } else {
            $this->ruleIndex = 0;
            $this->predicateIndex = 0;
        }
        $this->predicate = $predicate;
        $this->offendingToken = $recognizer->getCurrentToken();
        return $this;
    }

    function formatMessage($predicate, $message)
    {
        if ($message !== null) {
            return $message;
        } else {
            return new \Exception("failed predicate: {" . $predicate . "}?");
        }
    }
}