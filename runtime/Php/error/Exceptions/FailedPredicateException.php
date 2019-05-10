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
    /**
     * @var int
     */
    private $ruleIndex;

    /**
     * @var int
     */
    private $predicateIndex;

    /**
     * @var string
     */
    private $predicate;

    function __construct(Parser $recognizer, string $predicate, string $message=null)
    {
        parent::__construct((object)[
            'message' => $this->formatMessage($predicate, $message),
            'recognizer' => $recognizer,
            'input' => $recognizer->getInputStream(),
            'ctx' => $recognizer->_ctx
        ]);
        $s = $recognizer->getInterpreter()->atn->states[$recognizer->getState()];
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

    function formatMessage(string $predicate, string $message)
    {
        if ($message !== null) return $message;
        return new \Exception("failed predicate: {" . $predicate . "}?");
    }
}