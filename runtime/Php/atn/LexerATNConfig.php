<?php

namespace Antlr4\Atn;

class LexerATNConfig extends ATNConfig
{
    public $lexerActionExecutor;

    public $passedThroughNonGreedyDecision;

    function LexerATNConfig($params, $config)
    {
        parent::__construct($params, $config);

        // This is the backing field for {@link //getLexerActionExecutor}.
        $lexerActionExecutor = $params->lexerActionExecutor || null;
        $this->lexerActionExecutor = $lexerActionExecutor || ($config !== null ? $config->lexerActionExecutor : null);
        $this->passedThroughNonGreedyDecision = $config !== null ? $this->checkNonGreedyDecision($config, $this->state) : false;
    }

    function updateHashCode($hash)
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

    function checkNonGreedyDecision($source, $target)
    {
        return $source->passedThroughNonGreedyDecision || ($target instanceof DecisionState) && $target->nonGreedy;
    }
}