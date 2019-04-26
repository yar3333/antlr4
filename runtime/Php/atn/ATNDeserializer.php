<?php

namespace Antlr4\Atn;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

use Antlr4\Token; //('./../Token').Token;
use Antlr4\ATN; //('./ATN').ATN;
use Antlr4\ATNType; //('./ATNType').ATNType;
use Antlr4\ATNStates; //('./ATNState');
ATNState = ATNStates::ATNState;
BasicState = ATNStates::BasicState;
DecisionState = ATNStates::DecisionState;
BlockStartState = ATNStates::BlockStartState;
BlockEndState = ATNStates::BlockEndState;
LoopEndState = ATNStates::LoopEndState;
RuleStartState = ATNStates::RuleStartState;
RuleStopState = ATNStates::RuleStopState;
TokensStartState = ATNStates::TokensStartState;
PlusLoopbackState = ATNStates::PlusLoopbackState;
StarLoopbackState = ATNStates::StarLoopbackState;
StarLoopEntryState = ATNStates::StarLoopEntryState;
PlusBlockStartState = ATNStates::PlusBlockStartState;
StarBlockStartState = ATNStates::StarBlockStartState;
BasicBlockStartState = ATNStates::BasicBlockStartState;
use Antlr4\Transitions; //('./Transition');
Transition = Transitions::Transition;
AtomTransition = Transitions::AtomTransition;
SetTransition = Transitions::SetTransition;
NotSetTransition = Transitions::NotSetTransition;
RuleTransition = Transitions::RuleTransition;
RangeTransition = Transitions::RangeTransition;
ActionTransition = Transitions::ActionTransition;
EpsilonTransition = Transitions::EpsilonTransition;
WildcardTransition = Transitions::WildcardTransition;
PredicateTransition = Transitions::PredicateTransition;
PrecedencePredicateTransition = Transitions::PrecedencePredicateTransition;
use Antlr4\IntervalSet; //('./../IntervalSet').IntervalSet;
use Antlr4\Interval; //('./../IntervalSet').Interval;
use Antlr4\ATNDeserializationOptions; //('./ATNDeserializationOptions').ATNDeserializationOptions;
use Antlr4\LexerActions; //('./LexerAction');
LexerActionType = LexerActions::LexerActionType;
LexerSkipAction = LexerActions::LexerSkipAction;
LexerChannelAction = LexerActions::LexerChannelAction;
LexerCustomAction = LexerActions::LexerCustomAction;
LexerMoreAction = LexerActions::LexerMoreAction;
LexerTypeAction = LexerActions::LexerTypeAction;
LexerPushModeAction = LexerActions::LexerPushModeAction;
LexerPopModeAction = LexerActions::LexerPopModeAction;
LexerModeAction = LexerActions::LexerModeAction;
// This is the earliest supported serialized UUID.
// stick to serialized version for now, we don't need a UUID instance
BASE_SERIALIZED_UUID = "AADB8D7E-AEEF-4415-AD2B-8204D6CF042E";

//
// This UUID indicates the serialized ATN contains two sets of
// IntervalSets, where the second set's values are encoded as
// 32-bit integers to support the full Unicode SMP range up to U+10FFFF.
//
ADDED_UNICODE_SMP = "59627784-3BE5-417A-B9EB-8131A7286089";

// This list contains all of the currently supported UUIDs, ordered by when
// the feature first appeared in this branch.
SUPPORTED_UUIDS = [ BASE_SERIALIZED_UUID, ADDED_UNICODE_SMP ];

SERIALIZED_VERSION = 3;

// This is the current serialized UUID.
SERIALIZED_UUID = ADDED_UNICODE_SMP;

function initArray( $length, $value) 
{
	$tmp = [];
	$tmp[$length-1] = $value;
	return $tmp->map(function($i) {return $value;});
}

function ATNDeserializer ($options) 
{
    if ( $options=== undefined || $options === null ) 
    {
        $options = ATNDeserializationOptions::defaultOptions;
    }
    $this->deserializationOptions = $options;
    $this->stateFactories = null;
    $this->actionFactories = null;

    return $this;
}

// Determines if a particular serialized representation of an ATN supports
// a particular feature, identified by the {@link UUID} used for serializing
// the ATN at the time the feature was first introduced.
//
// @param feature The {@link UUID} marking the first time the feature was
// supported in the serialized ATN.
// @param actualUuid The {@link UUID} of the actual serialized ATN which is
// currently being deserialized.
// @return {@code true} if the {@code actualUuid} value represents a
// serialized ATN at or after the feature identified by {@code feature} was
// introduced; otherwise, {@code false}.

/* ATNDeserializer */function isFeatureSupported($feature, $actualUuid) 
{
    $idx1 = SUPPORTED_UUIDS->indexOf($feature);
    if ($idx1<0) 
    {
        return false;
    }
    $idx2 = SUPPORTED_UUIDS->indexOf($actualUuid);
    return $idx2 >= $idx1;
};

/* ATNDeserializer */function deserialize($data) 
{
    $this->reset($data);
    $this->checkVersion();
    $this->checkUUID();
    $atn = $this->readATN();
    $this->readStates($atn);
    $this->readRules($atn);
    $this->readModes($atn);
    $sets = [];
// First, deserialize sets with 16-bit arguments <= U+FFFF.
    $this->readSets($atn, $sets, $this->readInt->bind($this));
// Next, if the ATN was serialized with the Unicode SMP feature,
// deserialize sets with 32-bit arguments <= U+10FFFF.
    if ($this->isFeatureSupported(ADDED_UNICODE_SMP, $this->uuid)) 
    {
        $this->readSets($atn, $sets, $this->readInt32->bind($this));
    }
    $this->readEdges($atn, $sets);
    $this->readDecisions($atn);
    $this->readLexerActions($atn);
    $this->markPrecedenceDecisions($atn);
    $this->verifyATN($atn);
    if ($this->deserializationOptions->generateRuleBypassTransitions && $atn->grammarType === ATNType::PARSER ) 
    {
        $this->generateRuleBypassTransitions($atn);
// re-verify after modification
        $this->verifyATN($atn);
    }
    return $atn;
};

/* ATNDeserializer */function reset($data) 
{
	$adjust = function($c) 
	{
        $v = $c->charCodeAt(0);
        return $v>1  ? $v-2 : $v + 65533;
	};
    $temp = $data->split("").map($adjust);
// don't adjust the first value since that's the version number
    $temp[0] = $data->charCodeAt(0);
    $this->data = $temp;
    $this->pos = 0;
};

/* ATNDeserializer */function checkVersion() 
{
    $version = $this->readInt();
    if ( $version !== SERIALIZED_VERSION ) 
    {
        throw ("Could not deserialize ATN with version " + version + " (expected " + SERIALIZED_VERSION + ").");
    }
};

/* ATNDeserializer */function checkUUID() 
{
    $uuid = $this->readUUID();
    if (SUPPORTED_UUIDS->indexOf($uuid)<0) 
    {
        throw ("Could not deserialize ATN with UUID: " . $uuid .
                        " (expected " + SERIALIZED_UUID + " or a legacy UUID).", $uuid, SERIALIZED_UUID);
    }
    $this->uuid = $uuid;
};

/* ATNDeserializer */function readATN() 
{
    $grammarType = $this->readInt();
    $maxTokenType = $this->readInt();
    return new ATN($grammarType, $maxTokenType);
};

/* ATNDeserializer */function readStates($atn) 
{
	$j, $pair, $stateNumber;
    $loopBackStateNumbers = [];
    $endStateNumbers = [];
    $nstates = $this->readInt();
    for($i=0; $i<$nstates; $i++) 
    {
        $stype = $this->readInt();
// ignore bad type of states
        if ($stype===ATNState::INVALID_TYPE) 
        {
            $atn->addState(null);
            continue;
        }
        $ruleIndex = $this->readInt();
        if ($ruleIndex === 0xFFFF) 
        {
            $ruleIndex = -1;
        }
        $s = $this->stateFactory($stype, $ruleIndex);
        if ($stype === ATNState::LOOP_END) 
        {// special case
            $loopBackStateNumber = $this->readInt();
            array_push($loopBackStateNumbers, [$s, $loopBackStateNumber]);
        }
        else if($s instanceof BlockStartState) 
        {
            $endStateNumber = $this->readInt();
            array_push($endStateNumbers, [$s, $endStateNumber]);
        }
        $atn->addState($s);
    }
// delay the assignment of loop back and end states until we know all the
// state instances have been initialized
    for ($j=0; $j<$loopBackStateNumbers->length; $j++) 
    {
        $pair = $loopBackStateNumbers[$j];
        $pair[0].$loopBackState = $atn->states[$pair[1]];
    }

    for ($j=0; $j<$endStateNumbers->length; $j++) 
    {
        $pair = $endStateNumbers[$j];
        $pair[0].$endState = $atn->states[$pair[1]];
    }

    $numNonGreedyStates = $this->readInt();
    for ($j=0; $j<$numNonGreedyStates; $j++) 
    {
        $stateNumber = $this->readInt();
        $atn->states[$stateNumber].$nonGreedy = true;
    }

    $numPrecedenceStates = $this->readInt();
    for ($j=0; $j<$numPrecedenceStates; $j++) 
    {
        $stateNumber = $this->readInt();
        $atn->states[$stateNumber].$isPrecedenceRule = true;
    }
};

/* ATNDeserializer */function readRules($atn) 
{
    $i;
    $nrules = $this->readInt();
    if ($atn->grammarType === ATNType::LEXER ) 
    {
        $atn->ruleToTokenType = initArray($nrules, 0);
    }
    $atn->ruleToStartState = initArray($nrules, 0);
    for ($i=0; $i<$nrules; $i++) 
    {
        $s = $this->readInt();
        $startState = $atn->states[$s];
        $atn->ruleToStartState[$i] = $startState;
        if ( $atn->grammarType === ATNType::LEXER ) 
        {
            $tokenType = $this->readInt();
            if ($tokenType === 0xFFFF) 
            {
                $tokenType = Token::EOF;
            }
            $atn->ruleToTokenType[$i] = $tokenType;
        }
    }
    $atn->ruleToStopState = initArray($nrules, 0);
    for ($i=0; $i<$atn->states->length; $i++) 
    {
        $state = $atn->states[$i];
        if (!($state instanceof RuleStopState)) 
        {
            continue;
        }
        $atn->ruleToStopState[$state->ruleIndex] = $state;
        $atn->ruleToStartState[$state->ruleIndex].$stopState = $state;
    }
};

/* ATNDeserializer */function readModes($atn) 
{
    $nmodes = $this->readInt();
    for ($i=0; $i<$nmodes; $i++) 
    {
        $s = $this->readInt();
        array_push($atn->modeToStartState, $atn->states[$s]);
    }
};

/* ATNDeserializer */function readSets($atn, $sets, $readUnicode) 
{
    $m = $this->readInt();
    for ($i=0; $i<$m; $i++) 
    {
        $iset = new IntervalSet();
        array_push($sets, $iset);
        $n = $this->readInt();
        $containsEof = $this->readInt();
        if ($containsEof!==0) 
        {
            $iset->addOne(-1);
        }
        for ($j=0; $j<$n; $j++) 
        {
            $i1 = readUnicode();
            $i2 = readUnicode();
            $iset->addRange($i1, $i2);
        }
    }
};

/* ATNDeserializer */function readEdges($atn, $sets) 
{
	$i, $j, $state, $trans, $target;
    $nedges = $this->readInt();
    for ($i=0; $i<$nedges; $i++) 
    {
        $src = $this->readInt();
        $trg = $this->readInt();
        $ttype = $this->readInt();
        $arg1 = $this->readInt();
        $arg2 = $this->readInt();
        $arg3 = $this->readInt();
        $trans = $this->edgeFactory($atn, $ttype, $src, $trg, $arg1, $arg2, $arg3, $sets);
        $srcState = $atn->states[$src];
        $srcState->addTransition($trans);
    }
// edges for rule stop states can be derived, so they aren't serialized
    for ($i=0; $i<$atn->states->length; $i++) 
    {
        $state = $atn->states[$i];
        for ($j=0; $j<$state->transitions->length; $j++) 
        {
            $t = $state->transitions[$j];
            if (!($t instanceof RuleTransition)) 
            {
                continue;
            }
			$outermostPrecedenceReturn = -1;
			if ($atn->ruleToStartState[$t->target->ruleIndex].$isPrecedenceRule) 
			{
				if ($t->precedence === 0) 
				{
					$outermostPrecedenceReturn = $t->target->ruleIndex;
				}
			}

			$trans = new EpsilonTransition($t->followState, $outermostPrecedenceReturn);
            $atn->ruleToStopState[$t->target->ruleIndex].addTransition($trans);
        }
    }

    for ($i=0; $i<$atn->states->length; $i++) 
    {
        $state = $atn->states[$i];
        if ($state instanceof BlockStartState) 
        {// we need to know the end state to set its start state
            if ($state->endState === null) 
            {
                throw ("IllegalState");
            }
// block end states can only be associated to a single block start
// state
            if ( $state->endState->startState !== null) 
            {
                throw ("IllegalState");
            }
            $state->endState->startState = $state;
        }
        if ($state instanceof PlusLoopbackState) 
        {
            for ($j=0; $j<$state->transitions->length; $j++) 
            {
                $target = $state->transitions[$j].$target;
                if ($target instanceof PlusBlockStartState) 
                {
                    $target->loopBackState = $state;
                }
            }
        }
        else if ($state instanceof StarLoopbackState) 
        {
            for ($j=0; $j<$state->transitions->length; $j++) 
            {
                $target = $state->transitions[$j].$target;
                if ($target instanceof StarLoopEntryState) 
                {
                    $target->loopBackState = $state;
                }
            }
        }
    }
};

/* ATNDeserializer */function readDecisions($atn) 
{
    $ndecisions = $this->readInt();
    for ($i=0; $i<$ndecisions; $i++) 
    {
        $s = $this->readInt();
        $decState = $atn->states[$s];
        array_push($atn->decisionToState, $decState);
        $decState->decision = $i;
    }
};

/* ATNDeserializer */function readLexerActions($atn) 
{
    if ($atn->grammarType === ATNType::LEXER) 
    {
        $count = $this->readInt();
        $atn->lexerActions = initArray($count, null);
        for ($i=0; $i<$count; $i++) 
        {
            $actionType = $this->readInt();
            $data1 = $this->readInt();
            if ($data1 === 0xFFFF) 
            {
                $data1 = -1;
            }
            $data2 = $this->readInt();
            if ($data2 === 0xFFFF) 
            {
                $data2 = -1;
            }
            $lexerAction = $this->lexerActionFactory($actionType, $data1, $data2);
            $atn->lexerActions[$i] = $lexerAction;
        }
    }
};

/* ATNDeserializer */function generateRuleBypassTransitions($atn) 
{
	$i;
    $count = $atn->ruleToStartState->length;
    for($i=0; $i<$count; $i++) 
    {
        $atn->ruleToTokenType[$i] = $atn->maxTokenType + $i + 1;
    }
    for($i=0; $i<$count; $i++) 
    {
        $this->generateRuleBypassTransition($atn, $i);
    }
};

/* ATNDeserializer */function generateRuleBypassTransition($atn, $idx) 
{
	$i, $state;
    $bypassStart = new BasicBlockStartState();
    $bypassStart->ruleIndex = $idx;
    $atn->addState($bypassStart);

    $bypassStop = new BlockEndState();
    $bypassStop->ruleIndex = $idx;
    $atn->addState($bypassStop);

    $bypassStart->endState = $bypassStop;
    $atn->defineDecisionState($bypassStart);

    $bypassStop->startState = $bypassStart;

    $excludeTransition = null;
    $endState = null;

    if ($atn->ruleToStartState[$idx].$isPrecedenceRule) 
    {// wrap from the beginning of the rule to the StarLoopEntryState
        $endState = null;
        for($i=0; $i<$atn->states->length; $i++) 
        {
            $state = $atn->states[$i];
            if ($this->stateIsEndStateFor($state, $idx)) 
            {
                $endState = $state;
                $excludeTransition = $state->loopBackState->transitions[0];
                break;
            }
        }
        if ($excludeTransition === null) 
        {
            throw ("Couldn't identify final state of the precedence rule prefix section.");
        }
    }
    else 
    {
        $endState = $atn->ruleToStopState[$idx];
    }

// all non-excluded transitions that currently target end state need to
// target blockEnd instead
    for($i=0; $i<$atn->states->length; $i++) 
    {
        $state = $atn->states[$i];
        for($j=0; $j<$state->transitions->length; $j++) 
        {
            $transition = $state->transitions[$j];
            if ($transition === $excludeTransition) 
            {
                continue;
            }
            if ($transition->target === $endState) 
            {
                $transition->target = $bypassStop;
            }
        }
    }

// all transitions leaving the rule start state need to leave blockStart
// instead
    $ruleToStartState = $atn->ruleToStartState[$idx];
    $count = $ruleToStartState->transitions->length;
    while ( $count > 0) 
    {
        $bypassStart->addTransition($ruleToStartState->transitions[$count-1]);
        $ruleToStartState->transitions = $ruleToStartState->transitions->slice(-1);
    }
// link the new states
    $atn->ruleToStartState[$idx].addTransition(new EpsilonTransition($bypassStart));
    $bypassStop->addTransition(new EpsilonTransition($endState));

    $matchState = new BasicState();
    $atn->addState($matchState);
    $matchState->addTransition(new AtomTransition($bypassStop, $atn->ruleToTokenType[$idx]));
    $bypassStart->addTransition(new EpsilonTransition($matchState));
};

/* ATNDeserializer */function stateIsEndStateFor($state, $idx) 
{
    if ( $state->ruleIndex !== $idx) 
    {
        return null;
    }
    if (!( $state instanceof StarLoopEntryState)) 
    {
        return null;
    }
    $maybeLoopEndState = $state->transitions[$state->transitions->length - 1].$target;
    if (!( $maybeLoopEndState instanceof LoopEndState)) 
    {
        return null;
    }
    if ($maybeLoopEndState->epsilonOnlyTransitions &&
        ($maybeLoopEndState->transitions[0].$target instanceof RuleStopState)) 
        {
        return $state;
    }
    else 
    {
        return null;
    }
};

//
// Analyze the {@link StarLoopEntryState} states in the specified ATN to set
// the {@link StarLoopEntryState//isPrecedenceDecision} field to the
// correct value.
//
// @param atn The ATN.
//
/* ATNDeserializer */function markPrecedenceDecisions($atn) 
{
	for($i=0; $i<$atn->states->length; $i++) 
	{
		$state = $atn->states[$i];
		if (!( $state instanceof StarLoopEntryState)) 
		{
            continue;
        }
// We analyze the ATN to determine if this ATN decision state is the
// decision for the closure block that determines whether a
// precedence rule should continue or complete.
//
        if ( $atn->ruleToStartState[$state->ruleIndex].$isPrecedenceRule) 
        {
            $maybeLoopEndState = $state->transitions[$state->transitions->length - 1].$target;
            if ($maybeLoopEndState instanceof LoopEndState) 
            {
                if ( $maybeLoopEndState->epsilonOnlyTransitions &&
                        ($maybeLoopEndState->transitions[0].$target instanceof RuleStopState)) 
                        {
                    $state->isPrecedenceDecision = true;
                }
            }
        }
	}
};

/* ATNDeserializer */function verifyATN($atn) 
{
    if (!$this->deserializationOptions->verifyATN) 
    {
        return;
    }
// verify assumptions
	for($i=0; $i<$atn->states->length; $i++) 
	{
        $state = $atn->states[$i];
        if ($state === null) 
        {
            continue;
        }
        $this->checkCondition($state->epsilonOnlyTransitions || $state->transitions->length <= 1);
        if ($state instanceof PlusBlockStartState) 
        {
            $this->checkCondition($state->loopBackState !== null);
        }
        else  if ($state instanceof StarLoopEntryState) 
        {
            $this->checkCondition($state->loopBackState !== null);
            $this->checkCondition($state->transitions->length === 2);
            if ($state->transitions[0].$target instanceof StarBlockStartState) 
            {
                $this->checkCondition($state->transitions[1].$target instanceof LoopEndState);
                $this->checkCondition(!$state->nonGreedy);
            }
            else if ($state->transitions[0].$target instanceof LoopEndState) 
            {
                $this->checkCondition($state->transitions[1].$target instanceof StarBlockStartState);
                $this->checkCondition($state->nonGreedy);
            }
            else 
            {
                throw("IllegalState");
            }
        }
        else if ($state instanceof StarLoopbackState) 
        {
            $this->checkCondition($state->transitions->length === 1);
            $this->checkCondition($state->transitions[0].$target instanceof StarLoopEntryState);
        }
        else if ($state instanceof LoopEndState) 
        {
            $this->checkCondition($state->loopBackState !== null);
        }
        else if ($state instanceof RuleStartState) 
        {
            $this->checkCondition($state->stopState !== null);
        }
        else if ($state instanceof BlockStartState) 
        {
            $this->checkCondition($state->endState !== null);
        }
        else if ($state instanceof BlockEndState) 
        {
            $this->checkCondition($state->startState !== null);
        }
        else if ($state instanceof DecisionState) 
        {
            $this->checkCondition($state->transitions->length <= 1 || $state->decision >= 0);
        }
        else 
        {
            $this->checkCondition($state->transitions->length <= 1 || ($state instanceof RuleStopState));
        }
	}
};

/* ATNDeserializer */function checkCondition($condition, $message) 
{
    if (!$condition) 
    {
        if ($message === undefined || $message===null) 
        {
            $message = "IllegalState";
        }
        throw ($message);
    }
};

/* ATNDeserializer */function readInt() 
{
    return $this->data[$this->pos++];
};

/* ATNDeserializer */function readInt32() 
{
    $low = $this->readInt();
    $high = $this->readInt();
    return $low | ($high << 16);
};

/* ATNDeserializer */function readLong() 
{
    $low = $this->readInt32();
    $high = $this->readInt32();
    return ($low & 0x00000000FFFFFFFF) | ($high << 32);
};

function createByteToHex() 
{
	$bth = [];
	for ($i = 0; $i < 256; $i++) 
	{
		$bth[$i] = ($i + 0x100).toString(16).substr(1).toUpperCase();
	}
	return $bth;
}

$byteToHex = createByteToHex();

/* ATNDeserializer */function readUUID() 
{
	$bb = [];
	for($i=7;$i>=0;$i--) 
	{
		$int = $this->readInt();
/* jshint bitwise: false */
		$bb[(2*$i)+1] = $int & 0xFF;
		$bb[2*$i] = ($int >> 8) & 0xFF;
	}
    return $byteToHex[$bb[0]] + $byteToHex[$bb[1]] +
    $byteToHex[$bb[2]] + $byteToHex[$bb[3]] + '-' .
    $byteToHex[$bb[4]] + $byteToHex[$bb[5]] + '-' .
    $byteToHex[$bb[6]] + $byteToHex[$bb[7]] + '-' .
    $byteToHex[$bb[8]] + $byteToHex[$bb[9]] + '-' .
    $byteToHex[$bb[10]] + $byteToHex[$bb[11]] +
    $byteToHex[$bb[12]] + $byteToHex[$bb[13]] +
    $byteToHex[$bb[14]] + $byteToHex[$bb[15]];
};

/* ATNDeserializer */function edgeFactory($atn, $type, $src, $trg, $arg1, $arg2, $arg3, $sets) 
{
    $target = $atn->states[$trg];
    switch($type) 
    {
    case Transition::EPSILON:
        return new EpsilonTransition($target);
    case Transition::RANGE:
        return $arg3 !== 0 ? new RangeTransition($target, Token::EOF, $arg2) : new RangeTransition($target, $arg1, $arg2);
    case Transition::RULE:
        return new RuleTransition($atn->states[$arg1], $arg2, $arg3, $target);
    case Transition::PREDICATE:
        return new PredicateTransition($target, $arg1, $arg2, $arg3 !== 0);
    case Transition::PRECEDENCE:
        return new PrecedencePredicateTransition($target, $arg1);
    case Transition::ATOM:
        return $arg3 !== 0 ? new AtomTransition($target, Token::EOF) : new AtomTransition($target, $arg1);
    case Transition::ACTION:
        return new ActionTransition($target, $arg1, $arg2, $arg3 !== 0);
    case Transition::SET:
        return new SetTransition($target, $sets[$arg1]);
    case Transition::NOT_SET:
        return new NotSetTransition($target, $sets[$arg1]);
    case Transition::WILDCARD:
        return new WildcardTransition($target);
    $default:
        throw "The specified transition type: " + type + " is not valid.";
    }
};

/* ATNDeserializer */function stateFactory($type, $ruleIndex) 
{
    if ($this->stateFactories === null) 
    {
        $sf = [];
        $sf[ATNState::INVALID_TYPE] = null;
        $sf[ATNState::BASIC] = function() { return new BasicState(); };
        $sf[ATNState::RULE_START] = function() { return new RuleStartState(); };
        $sf[ATNState::BLOCK_START] = function() { return new BasicBlockStartState(); };
        $sf[ATNState::PLUS_BLOCK_START] = function() { return new PlusBlockStartState(); };
        $sf[ATNState::STAR_BLOCK_START] = function() { return new StarBlockStartState(); };
        $sf[ATNState::TOKEN_START] = function() { return new TokensStartState(); };
        $sf[ATNState::RULE_STOP] = function() { return new RuleStopState(); };
        $sf[ATNState::BLOCK_END] = function() { return new BlockEndState(); };
        $sf[ATNState::STAR_LOOP_BACK] = function() { return new StarLoopbackState(); };
        $sf[ATNState::STAR_LOOP_ENTRY] = function() { return new StarLoopEntryState(); };
        $sf[ATNState::PLUS_LOOP_BACK] = function() { return new PlusLoopbackState(); };
        $sf[ATNState::LOOP_END] = function() { return new LoopEndState(); };
        $this->stateFactories = $sf;
    }
    if ($type>$this->stateFactories->length || $this->stateFactories[$type] === null) 
    {
        throw("The specified state type " + type + " is not valid.");
    }
    else 
    {
        $s = $this->stateFactories[$type]();
        if ($s!==null) 
        {
            $s->ruleIndex = $ruleIndex;
            return $s;
        }
    }
};

/* ATNDeserializer */function lexerActionFactory($type, $data1, $data2) 
{
    if ($this->actionFactories === null) 
    {
        $af = [];
        $af[LexerActionType::CHANNEL] = function($data1, $data2) { return new LexerChannelAction($data1); };
        $af[LexerActionType::CUSTOM] = function($data1, $data2) { return new LexerCustomAction($data1, $data2); };
        $af[LexerActionType::MODE] = function($data1, $data2) { return new LexerModeAction($data1); };
        $af[LexerActionType::MORE] = function($data1, $data2) { return LexerMoreAction::INSTANCE; };
        $af[LexerActionType::POP_MODE] = function($data1, $data2) { return LexerPopModeAction::INSTANCE; };
        $af[LexerActionType::PUSH_MODE] = function($data1, $data2) { return new LexerPushModeAction($data1); };
        $af[LexerActionType::SKIP] = function($data1, $data2) { return LexerSkipAction::INSTANCE; };
        $af[LexerActionType::TYPE] = function($data1, $data2) { return new LexerTypeAction($data1); };
        $this->actionFactories = $af;
    }
    if ($type>$this->actionFactories->length || $this->actionFactories[$type] === null) 
    {
        throw("The specified lexer action type " + type + " is not valid.");
    }
    else 
    {
        return $this->actionFactories[$type]($data1, $data2);
    }
};


$exports->ATNDeserializer = ATNDeserializer;