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

    function LexerATNConfig(object $params, object $config)
    {
        parent::__construct($params, $config);

        // This is the backing field for {@link //getLexerActionExecutor}.
        $lexerActionExecutor = $params->lexerActionExecutor || null;
        $this->lexerActionExecutor = $lexerActionExecutor || ($config !== null ? $config->lexerActionExecutor : null);
        $this->passedThroughNonGreedyDecision = $config !== null ? $this->checkNonGreedyDecision($config, $this->state) : false;
    }

    function updateHashCode(Hash $hash)
    {
        $hash->update($this->state->stateNumber, $this->alt, $this->context, $this->semanticContext, $this->passedThroughNonGreedyDecision, $this->lexerActionExecutor);
    }

    function equals($other)
    {
        return $this === $other ||
            (
                $other instanceof LexerATNConfig &&
                $this->passedThroughNonGreedyDecision == $other->passedThroughNonGreedyDecision &&
                ($this->lexerActionExecutor ? $this->lexerActionExecutor->equals($other->lexerActionExecutor) : !$other->lexerActionExecutor) &&
                parent::equals($other)
            );
    }

    function hashCodeForConfigSet()
    {
        return $this->hashCode();
    }

    function equalsForConfigSet($other)
    {
        return $this->equals($other);
    }

    function checkNonGreedyDecision(object $source, $target)
    {
        return $source->passedThroughNonGreedyDecision || ($target instanceof DecisionState) && $target->nonGreedy;
    }
}