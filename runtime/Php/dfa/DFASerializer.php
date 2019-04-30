<?php

namespace Antlr4\Dfa;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

// A DFA walker that knows how to dump them to serialized strings.#/


use Antlr4\Utils\Utils;

class DFASerializer extends
{
    /**
     * @var DFA
     */
    public $dfa;

    /**
     * @var bool
     */
    public $literalNames;

    /**
     * @var bool
     */
    public $symbolicNames;

    function __construct(DFA $dfa, array $literalNames=null, array $symbolicNames=null)
	{
        $this->dfa = $dfa;
        $this->literalNames = $literalNames || [];
        $this->symbolicNames = $symbolicNames || [];
    }

    function __toString() 
    {
       if($this->dfa->s0 === null) 
       {
           return null;
       }
       $buf = "";
       $states = $this->dfa->sortedStates();
       foreach ($states as $s)
       {
           if ($s->edges !== null)
           {
                $n = count($s->edges);
                for($j = 0; $j < $n; $j++)
                {
                    $t = $s->edges[$j] || null;
                    if ($t && $t->stateNumber !== 0x7FFFFFFF)
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
       return $buf === "" ? null : $buf;
    }
    
    function getEdgeLabel($i) 
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
            return Utils::fromCharCode($i-1);
        }
    }
    
    function getStateString($s) 
    {
        $baseStateStr = ($s->isAcceptState ? ":" : "") . "s" . $s->stateNumber . ($s->requiresFullContext ? "^" : "");
        if($s->isAcceptState) 
        {
            if ($s->predicates !== null) 
            {
                return $baseStateStr . "=>" . (string)$s->predicates;
            }
            else 
            {
                return $baseStateStr . "=>" . (string)$s->prediction;
            }
        }
        else 
        {
            return $baseStateStr;
        }
    }
}
