<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Atn;

use \Antlr4\Atn\Semanticcontexts\SemanticContext;
use \Antlr4\Atn\States\ATNState;
use \Antlr4\Predictioncontexts\PredictionContext;
use \Antlr4\Recognizer;
use \Antlr4\Utils\Hash;

// A tuple: (ATN state, predicted alt, syntactic, semantic context).
// The syntactic context is a graph-structured stack node whose
// path(s) to the root is the rule invocation(s)
// chain used to arrive at the state.  The semantic context is
// the tree of semantic predicates encountered before reaching an ATN state.
class ATNConfig
{
 	const SUPPRESS_PRECEDENCE_FILTER = 0x40000000;

    /**
     * @var ATNState
     */
    public $state;

    /**
     * @var int
     */
    public $alt;

    /**
     * @var PredictionContext
     */
    public $context;

    /**
     * @var SemanticContext
     */
    public $semanticContext;

    /**
     * @var int
     */
    public $reachesIntoOuterContext;

    public $precedenceFilterSuppressed;

    private static function checkParams(?object $params, $isCfg=false) : object
    {
        if (!$params)
        {
            $result = (object)[ 'state'=>null, 'alt'=>null, 'context'=>null, 'semanticContext'=>null ];
            if ($isCfg) $result->reachesIntoOuterContext = 0;
            return $result;
        }

        $props = (object)[];
        $props->state = $params->state ?? null;
        $props->alt = $params->alt ?? null;
        $props->context = $params->context ?? null;
        $props->semanticContext = $params->semanticContext ?? null;
        if ($isCfg)
        {
            $props->reachesIntoOuterContext = $params->reachesIntoOuterContext ?? 0;
            $props->precedenceFilterSuppressed = $params->precedenceFilterSuppressed ?? false;
        }
        return $props;
    }

    function __construct(?object $params, ?ATNConfig $_config)
    {
        $params = self::checkParams($params);
        $config = self::checkParams($_config, true);

        // The ATN state associated with this configuration///
        $this->state = $params->state ?: $config->state;

        // What alt (or lexer rule) is predicted by this configuration///
        $this->alt = $params->alt ?: $config->alt;

        // The stack of invoking states leading to the rule/states associated
        //  with this config.  We track only those contexts pushed during
        //  execution of the ATN simulator.
        $this->context = $params->context ?: $config->context;

        $this->semanticContext = $params->semanticContext ?: ($config->semanticContext ?: SemanticContext::NONE());

        // We cannot execute predicates dependent upon local context unless
        // we know for sure we are in the correct context. Because there is
        // no way to do this efficiently, we simply cannot evaluate
        // dependent predicates unless we are in the rule that initially
        // invokes the ATN simulator.
        //
        // closure() tracks the depth of how far we dip into the
        // outer context: depth &gt; 0.  Note that it may not be totally
        // accurate depth since I don't ever decrement. TODO: make it a boolean then
        $this->reachesIntoOuterContext = $config->reachesIntoOuterContext;
        $this->precedenceFilterSuppressed = $config->precedenceFilterSuppressed ?? false;
    }

    function hashCode() : int
    {
        $hash = new Hash();
        $this->updateHashCode($hash);
        return $hash->finish();
    }


    function updateHashCode(Hash $hash) : void
    {
        $hash->update($this->state->stateNumber, $this->alt, $this->context, $this->semanticContext);
    }

    // An ATN configuration is equal to another if both have
    // the same state, they predict the same alternative, and
    // syntactic/semantic contexts are the same.
    function equals($other) : bool
    {
        if ($this === $other) return true;

        if (!($other instanceof ATNConfig)) return false;

        return $this->state->stateNumber === $other->state->stateNumber &&
            $this->alt === $other->alt &&
            ($this->context === null ? $other->context === null : $this->context->equals($other->context)) &&
            $this->semanticContext->equals($other->semanticContext) &&
            $this->precedenceFilterSuppressed === $other->precedenceFilterSuppressed;
    }


    function hashCodeForConfigSet() : int
    {
        $hash = new Hash();
        $hash->update($this->state->stateNumber, $this->alt, $this->semanticContext);
        return $hash->finish();
    }


    function equalsForConfigSet($other) : bool
    {
        if ($this === $other) return true;
        if (!($other instanceof ATNConfig)) return false;

        return $this->state->stateNumber === $other->state->stateNumber &&
            $this->alt === $other->alt &&
            $this->semanticContext->equals($other->semanticContext);
    }
	/**
	 * This method gets the value of the {@link #reachesIntoOuterContext} field
	 * as it existed prior to the introduction of the
	 * {@link #isPrecedenceFilterSuppressed} method.
	 */
	function getOuterContextDepth() : int
    {
		return $this->reachesIntoOuterContext & ~self::SUPPRESS_PRECEDENCE_FILTER;
	}

	function toString(Recognizer $recog, bool $showAlt) : string
	{
		$buf = '(';
		$buf .= $this->state;
		if ($showAlt) $buf .= "," . $this->alt;
        if ($this->context) $buf .= ",[" . $this->context . "]";
        if ($this->semanticContext && $this->semanticContext !== SemanticContext::NONE())
        {
            $buf .= "," . $this->semanticContext;
        }
        if ($this->getOuterContextDepth() > 0)
        {
            $buf .= ",up=" . $this->getOuterContextDepth();
        }
		$buf .= ')';
		return $buf;
    }

    function __toString()
    {
        return
            "(" . $this->state . "," . $this->alt .
                ($this->context ? ",[" . $this->context . "]" : "") .
                ($this->semanticContext && $this->semanticContext !== SemanticContext::NONE() ? "," . $this->semanticContext : "") .
                ($this->reachesIntoOuterContext > 0 ? ",up=" . $this->reachesIntoOuterContext : "") .
            ")";
    }
}
