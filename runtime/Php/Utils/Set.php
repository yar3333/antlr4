<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class Set implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var callable
     */
    private $hashFunction;

    /**
     * @var callable
     */
    private $equalsFunction;

    function __construct(callable $hashFunction=null, callable $equalsFunction=null)
    {
        $this->data = [];
        $this->hashFunction = $hashFunction ?? function ($a) { return Utils::standardHashCodeFunction($a); };
        $this->equalsFunction = $equalsFunction ?? function ($a, $b) { return Utils::standardEqualsFunction($a, $b); };
    }

    function length()
    {
        $l = 0;
        foreach ($this->data as $values)
        {
            $l += count($values);
        }
        return $l;
    }

    function addAll(array $values) : void
    {
        foreach ($values as $v) $this->add($v);
    }

    function add($value)
    {
        $hash = ($this->hashFunction)($value);
        if (array_key_exists($hash, $this->data))
        {
            foreach ($this->data[$hash] as $v)
            {
                if (($this->equalsFunction)($value, $v)) return $v;
            }
            $this->data[$hash][] = $value;
            return $value;
        }

        $this->data[$hash] = [ $value ];
        return $value;
    }

    function contains($value) : bool
    {
        return $this->get($value) !== null;
    }

    function get($value)
    {
        $hash = ($this->hashFunction)($value);
        if (array_key_exists($hash, $this->data))
        {
            foreach ($this->data[$hash] as $v)
            {
                if (($this->equalsFunction)($value, $v)) return $v;
            }
        }
        return null;
    }

    function values() : array
    {
        $r = [];
        foreach ($this->data as $value)
        {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $r = array_merge($r, $value);
        }
        return $r;
    }

    function isEmpty() : bool { return !$this->data; }

    function __toString()
    {
        return Utils::arrayToString($this->values());
    }

    function getIterator() : \Iterator
    {
        return new \ArrayIterator($this->data);
    }
}
