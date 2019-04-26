<?php

namespace Antlr4;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

/*jslint smarttabs:true */

use Antlr4\Token; //('./Token').Token;

class Interval
{
    public $start;
    public $stop;

    /* stop is not included! */
    function __construct($start, $stop)
    {
        $this->start = $start;
        $this->stop = $stop;
    }

    function contains($item)
    {
        return $item >= $this->start && $item < $this->stop;
    }

    function toString()
    {
        if($this->start===$this->stop-1)
        {
            return $this->start->toString();
        }
        else
        {
            return $this->start->toString() . ".." . ($this->stop - 1);
        }
    }

    function getLength() { return $this->stop - $this->start; }
}

class IntervalSet
{
    public $intervals;
    public $readOnly;

    function __construct()
    {
        $this->intervals = null;
        $this->readOnly = false;
    }

    function first($v)
    {
        if ($this->intervals === null || $this->intervals->length===0)
        {
            return Token::INVALID_TYPE;
        }
        else
        {
            return $this->intervals[0]->start;
        }
    }

    function addOne($v)
    {
        $this->addInterval(new Interval($v, $v + 1));
    }

    function addRange($l, $h)
    {
        $this->addInterval(new Interval($l, $h + 1));
    }

    function addInterval($v)
    {
        if ($this->intervals === null)
        {
            $this->intervals = [];
            array_push($this->intervals, $v);
        }
        else
        {
            // find insert pos
            for ($k = 0; $k < $this->intervals->length; $k++)
            {
                /*var */$i = $this->intervals[$k];
                // distinct range -> insert
                if ($v->stop < $i->start)
                {
                    $this->intervals->splice($k, 0, $v);
                    return;
                }
                // contiguous range -> adjust
                else if ($v->stop === $i->start)
                {
                    $this->intervals[$k]->start = $v->start;
                    return;
                }
                // overlapping range -> adjust and reduce
                else if ($v->start <= $i->stop)
                {
                    $this->intervals[$k] = new Interval(min($i->start, $v->start), max($i->stop, $v->stop));
                    $this->reduce($k);
                    return;
                }
            }
            // greater than any existing
            array_push($this->intervals, $v);
        }
    }

    function addSet($other)
    {
        if ($other->intervals !== null)
        {
            for ($k = 0; $k < $other->intervals->length; $k++)
            {
                /*var */$i = $other->intervals[$k];
                $this->addInterval(new Interval($i->start, $i->stop));
            }
        }
        return $this;
    }

    function reduce($k)
    {
        // only need to reduce if k is not the last
        if ($k < $this->intervalslength - 1)
        {
            /*var */$l = $this->intervals[$k];
            /*var */$r = $this->intervals[$k + 1];
            // if r contained in l
            if ($l->stop >= $r->stop)
            {
                array_pop($this->intervals, $k + 1);
                $this->reduce($k);
            }
            else if ($l->stop >= $r->start)
            {
                $this->intervals[$k] = new Interval($l->start, $r->stop);
                array_pop($this->intervals, $k + 1);
            }
        }
    }

    function complement($start, $stop)
    {
        /*var */$result = new IntervalSet();
        $result->addInterval(new Interval($start,$stop+1));
        for($i=0; $i<$this->intervals->length; $i++)
        {
            $result->removeRange($this->intervals[$i]);
        }
        return $result;
    }

    function contains($item)
    {
        if ($this->intervals === null)
        {
            return false;
        }
        else
        {
            for ($k = 0; $k < $this->intervals->length; $k++)
            {
                if($this->intervals[$k].contains($item))
                {
                    return true;
                }
            }
            return false;
        }
    }

    function getLength()
    {
        $len = 0;
        foreach ($this->intervals as $i) $len += $i->length;
        return $len;
    }

    function removeRange($v)
    {
        if($v->start===$v->stop-1)
        {
            $this->removeOne($v->start);
        }
        else if ($this->intervals!==null)
        {
            /*var */$k = 0;
            for($n=0; $n<$this->intervals->length; $n++)
            {
                /*var */$i = $this->intervals[$k];
                // intervals are ordered
                if ($v->stop<=$i->start)
                {
                    return;
                }
                // check for including range, split it
                else if($v->start>$i->start && $v->stop<$i->stop)
                {
                    $this->intervals[$k] = new Interval($i->start, $v->start);
                    /*var */$x = new Interval($v->stop, $i->stop);
                    $this->intervals->splice($k, 0, $x);
                    return;
                }
                // check for included range, remove it
                else if($v->start<=$i->start && $v->stop>=$i->stop)
                {
                    $this->intervals->splice($k, 1);
                    $k = $k - 1;// need another pass
                }
                // check for lower boundary
                else if($v->start<$i->stop)
                {
                    $this->intervals[$k] = new Interval($i->start, $v->start);
                }
                // check for upper boundary
                else if($v->stop<$i->stop)
                {
                    $this->intervals[$k] = new Interval($v->stop, $i->stop);
                }
                $k += 1;
            }
        }
    }

    function removeOne($v)
    {
        if ($this->intervals !== null)
        {
            for ($k = 0; $k < $this->intervals->length; $k++)
            {
                /*var */$i = $this->intervals[$k];
                // intervals is ordered
                if ($v < $i->start)
                {
                    return;
                }
                // check for single value range
                else if ($v === $i->start && $v === $i->stop - 1)
                {
                    $this->intervals->splice($k, 1);
                    return;
                }
                // check for lower boundary
                else if ($v === $i->start)
                {
                    $this->intervals[$k] = new Interval($i->start + 1, $i->stop);
                    return;
                }
                // check for upper boundary
                else if ($v === $i->stop - 1)
                {
                    $this->intervals[$k] = new Interval($i->start, $i->stop - 1);
                    return;
                }
                // split existing range
                else if ($v < $i->stop - 1)
                {
                    /*var */$x = new Interval($i->start, $v);
                    $i->start = $v + 1;
                    $this->intervals->splice($k, 0, $x);
                    return;
                }
            }
        }
    }

    function toString($literalNames, $symbolicNames, $elemsAreChar)
    {
        $literalNames = $literalNames || null;
        $symbolicNames = $symbolicNames || null;
        $elemsAreChar = $elemsAreChar || false;
        if ($this->intervals === null)
        {
            return "{}";
        }
        else if($literalNames!==null || $symbolicNames!==null)
        {
            return $this->toTokenString($literalNames, $symbolicNames);
        }
        else if($elemsAreChar)
        {
            return $this->toCharString();
        }
        else
        {
            return $this->toIndexString();
        }
    }

    function toCharString()
    {
        /*var */$names = [];
        for ($i = 0; $i < $this->intervals->length; $i++)
        {
            /*var */$v = $this->intervals[$i];
            if($v->stop===$v->start+1)
            {
                if ( $v->start===Token::EOF )
                {
                    array_push($names, "<EOF>");
                }
                else
                {
                    array_push($names, "'" . String.fromCharCode(v.start) . "'");
                }
            }
            else
            {
                array_push($names, "'" . String.fromCharCode(v.start) . "'..'" . String.fromCharCode(v.stop-1) + "'");
            }
        }
        if ($names->length > 1)
        {
            return "{" + names.join(", ") + "}";
        }
        else
        {
            return $names[0];
        }
    }


    function toIndexString()
    {
        /*var */$names = [];
        for ($i = 0; $i < $this->intervals->length; $i++)
        {
            /*var */$v = $this->intervals[$i];
            if($v->stop===$v->start+1)
            {
                if ( $v->start===Token::EOF )
                {
                    array_push($names, "<EOF>");
                }
                else
                {
                    array_push($names, $v->start->toString());
                }
            }
            else
            {
                array_push($names, $v->start->toString() + ".." + ($v->stop-1).toString());
            }
        }
        if ($names->length > 1)
        {
            return "{" + names.join(", ") + "}";
        }
        else
        {
            return $names[0];
        }
    }

    function toTokenString($literalNames, $symbolicNames)
    {
        /*var */$names = [];
        for ($i = 0; $i < $this->intervals->length; $i++)
        {
            /*var */$v = $this->intervals[$i];
            for ($j = $v->start; $j < $v->stop; $j++)
            {
                array_push($names, $this->elementName($literalNames, $symbolicNames, $j));
            }
        }
        if ($names->length > 1)
        {
            return "{" + names.join(", ") + "}";
        }
        else
        {
            return $names[0];
        }
    }

    function elementName($literalNames, $symbolicNames, $a)
    {
        if ($a === Token::EOF)
        {
            return "<EOF>";
        }
        else if ($a === Token::EPSILON)
        {
            return "<EPSILON>";
        }
        else
        {
            return $literalNames[$a] || $symbolicNames[$a];
        }
    }
}