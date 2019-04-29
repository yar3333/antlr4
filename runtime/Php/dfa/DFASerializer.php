<?php

namespace Antlr4\Dfa;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

// A DFA walker that knows how to dump them to serialized strings.#/


class DFASerializer extends 
{
	function __construct($dfa, $literalNames, $symbolicNames)
	{
	$this->dfa = $dfa;
	$this->literalNames = $literalNames || [];
	$this->symbolicNames = $symbolicNames || [];
	return $this;
}

/* DFASerializer */function __toString() 
{
   if($this->dfa->s0 === null) 
   {
       return null;
   }
   $buf = "";
   $states = $this->dfa->sortedStates();
   for($i=0;$i<$states->length;$i++) 
   {
       $s = $states[$i];
       if($s->edges!==null) 
       {
            $n = $s->edges->length;
            for($j=0;$j<$n;$j++) 
            {
                $t = $s->edges[$j] || null;
                if($t!==null && $t->stateNumber !== 0x7FFFFFFF) 
                {
                    $buf = $buf->concat($this->getStateString($s));
                    $buf = $buf->concat("-");
                    $buf = $buf->concat($this->getEdgeLabel($j));
                    $buf = $buf->concat("->");
                    $buf = $buf->concat($this->getStateString($t));
                    $buf = $buf->concat('\n');
                }
            }
       }
   }
   return $buf->length===0 ? null : $buf;
};

/* DFASerializer */function getEdgeLabel($i) 
{
    if ($i===0) 
    {
        return "EOF";
    }
    else if($this->literalNames !==null || $this->symbolicNames!==null) 
    {
        return $this->literalNames[$i-1] || $this->symbolicNames[$i-1];
    }
    else 
    {
        return String->fromCharCode($i-1);
    }
};

/* DFASerializer */function getStateString($s) 
{
    $baseStateStr = ( $s->isAcceptState ? ":" : "") + "s" + s.stateNumber + ( s.requiresFullContext ? "^" : "");
    if($s->isAcceptState) 
    {
        if ($s->predicates !== null) 
        {
            return $baseStateStr . "=>" . $s->predicates->toString();
        }
        else 
        {
            return $baseStateStr . "=>" . $s->prediction->toString();
        }
    }
    else 
    {
        return $baseStateStr;
    }
};

class LexerDFASerializer extends DFASerializer
{
	function __construct($dfa)
	{
		parent::__construct($dfa, null);
	return $this;
}

LexerDFASerializer::prototype = Object->create(DFASerializer::prototype);
LexerDFASerializer::prototype->constructor = LexerDFASerializer;

/* LexerDFASerializer */function getEdgeLabel($i) 
{
	return "'" + Utils::fromCharCode(i) + "'";
};

$exports->DFASerializer = DFASerializer;
$exports->LexerDFASerializer = LexerDFASerializer;

