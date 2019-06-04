<?php

namespace Antlr4\Atn;

use Antlr4\Atn\States\DecisionState;
use Antlr4\Utils\Hash;

class LexerATNConfig extends ATNConfig
{
    /**
     * @var LexerActionExecutor
     */
    public $lexerActionExecutor;

    /**
     * @var bool
     */
    public $passedThroughNonGreedyDecision;

    function __construct(?object $params, ?self $config)
    {
        parent::__construct($params, $config);

        $this->lexerActionExecutor = $params->lexerActionExecutor ?? ($config->lexerActionExecutor ?? null);
        $this->passedThroughNonGreedyDecision = $config ? $this->checkNonGreedyDecision($config, $this->state) : false;
    }

    function updateHashCode(Hash $hash) : void
    {
        $hash->update($this->state->stateNumber, $this->alt, $this->context, $this->semanticContext, $this->passedThroughNonGreedyDecision, $this->lexerActionExecutor);
    }

    function equals($other) : bool
    {
        return $this === $other ||
            (
                $other instanceof LexerATNConfig &&
                $this->passedThroughNonGreedyDecision === $other->passedThroughNonGreedyDecision &&
                ($this->lexerActionExecutor ? $this->lexerActionExecutor->equals($other->lexerActionExecutor) : !$other->lexerActionExecutor) &&
                parent::equals($other)
            );
    }

    function hashCodeForConfigSet() : int
    {
        return $this->hashCode();
    }

    function equalsForConfigSet($other) : bool
    {
        return $this->equals($other);
    }

    function checkNonGreedyDecision(object $source, $target) : bool
    {
        return $source->passedThroughNonGreedyDecision || (($target instanceof DecisionState) && $target->nonGreedy);
    }
}