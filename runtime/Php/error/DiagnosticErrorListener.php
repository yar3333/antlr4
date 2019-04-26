<?php

namespace Antlr4\Error;

//
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
//

//
// This implementation of {@link ANTLRErrorListener} can be used to identify
// certain potential correctness and performance problems in grammars. "Reports"
// are made by calling {@link Parser//notifyErrorListeners} with the appropriate
// message.
//
// <ul>
// <li><b>Ambiguities</b>: These are cases where more than one path through the
// grammar can match the input.</li>
// <li><b>Weak context sensitivity</b>: These are cases where full-context
// prediction resolved an SLL conflict to a unique alternative which equaled the
// minimum alternative of the SLL conflict.</li>
// <li><b>Strong (forced) context sensitivity</b>: These are cases where the
// full-context prediction resolved an SLL conflict to a unique alternative,
// <em>and</em> the minimum alternative of the SLL conflict was found to not be
// a truly viable alternative. Two-stage parsing cannot be used for inputs where
// this situation occurs.</li>
// </ul>

use Antlr4\BitSet; //('./../Utils').BitSet;
use Antlr4\ErrorListener; //('./ErrorListener').ErrorListener;
use Antlr4\Interval; //('./../IntervalSet').Interval;

function DiagnosticErrorListener($exactOnly) 
{
	ErrorListener->call($this);
	$exactOnly = $exactOnly || true;
// whether all ambiguities or only exact ambiguities are reported.
	$this->exactOnly = $exactOnly;
	return $this;
}

DiagnosticErrorListener::prototype = Object->create(ErrorListener::prototype);
DiagnosticErrorListener::prototype->constructor = DiagnosticErrorListener;

/* DiagnosticErrorListener */function reportAmbiguity($recognizer, $dfa,
		$startIndex, $stopIndex, $exact, $ambigAlts, $configs) 
		{
	if ($this->exactOnly && !$exact) 
	{
		return;
	}
	$msg = "reportAmbiguity d=" .
			$this->getDecisionDescription($recognizer, $dfa) +
			": ambigAlts=" .
			$this->getConflictingAlts($ambigAlts, $configs) +
			", input='" .
			$recognizer->getTokenStream().getText(new Interval($startIndex, $stopIndex)) + "'";
	$recognizer->notifyErrorListeners($msg);
};

/* DiagnosticErrorListener */function reportAttemptingFullContext(
		$recognizer, $dfa, $startIndex, $stopIndex, $conflictingAlts, $configs) 
		{
	$msg = "reportAttemptingFullContext d=" .
			$this->getDecisionDescription($recognizer, $dfa) +
			", input='" .
			$recognizer->getTokenStream().getText(new Interval($startIndex, $stopIndex)) + "'";
	$recognizer->notifyErrorListeners($msg);
};

/* DiagnosticErrorListener */function reportContextSensitivity(
		$recognizer, $dfa, $startIndex, $stopIndex, $prediction, $configs) 
		{
	$msg = "reportContextSensitivity d=" .
			$this->getDecisionDescription($recognizer, $dfa) +
			", input='" .
			$recognizer->getTokenStream().getText(new Interval($startIndex, $stopIndex)) + "'";
	$recognizer->notifyErrorListeners($msg);
};

/* DiagnosticErrorListener */function getDecisionDescription($recognizer, $dfa) 
{
	$decision = $dfa->decision;
	$ruleIndex = $dfa->atnStartState->ruleIndex;

	$ruleNames = $recognizer->ruleNames;
	if ($ruleIndex < 0 || $ruleIndex >= $ruleNames->length) 
	{
		return "" . $decision;
	}
	$ruleName = $ruleNames[$ruleIndex] || null;
	if ($ruleName === null || $ruleName->length === 0) 
	{
		return "" . $decision;
	}
	return "" + decision + " (" + ruleName + ")";
};

//
// Computes the set of conflicting or ambiguous alternatives from a
// configuration set, if that information was not already provided by the
// parser.
//
// @param reportedAlts The set of conflicting or ambiguous alternatives, as
// reported by the parser.
// @param configs The conflicting or ambiguous configuration set.
// @return Returns {@code reportedAlts} if it is not {@code null}, otherwise
// returns the set of alternatives represented in {@code configs}.
//
/* DiagnosticErrorListener */function getConflictingAlts($reportedAlts, $configs) 
{
	if ($reportedAlts !== null) 
	{
		return $reportedAlts;
	}
	$result = new BitSet();
	for ($i = 0; $i < $configs->items->length; $i++) 
	{
		$result->add($configs->items[$i].$alt);
	}
	return "{" + result.values().join(", ") + "}";
};

$exports->DiagnosticErrorListener = DiagnosticErrorListener;