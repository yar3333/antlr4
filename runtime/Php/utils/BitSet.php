<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class BitSet
{
    public $data;

    function __construct()
    {
        $this->data = [];
        return $this;
    }

    function add($value)
    {
        $this->data[$value] = true;
    }

    function or($set)
    {
        foreach ($set->data as $alt) $this->add($alt);
    }

    function remove($value)
    {
        unset($this->data[$value]);
    }

    function contains($value)
    {
        return $this->data[$value] === true;
    }

    function values()
    {
        return array_keys($this->data);
    }

    function minValue()
    {
        return min($this->values());
    }

    function hashCode()
    {
        $hash = new Hash();
        $hash->update($this->values());
        return $hash->finish();
    }

    function equals($other)
    {
        if (!($other instanceof BitSet)) {
            return false;
        }
        return $this->hashCode() === $other->hashCode();
    }

    function getLength()
    {
        return count($this->values());
    }

    function __toString()
    {
        return "{" . implode(", ", $this->values()) . "}";
    }
}