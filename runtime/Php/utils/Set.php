<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

class Set
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var \Closure
     */
    public $hashFunction;

    /**
     * @var \Closure
     */
    public $equalsFunction;

    function __construct(\Closure $hashFunction=null, \Closure $equalsFunction=null)
    {
        $this->data = [];
        $this->hashFunction = isset($hashFunction) ? $hashFunction : (function ($a) {
            return Utils::standardHashCodeFunction($a);
        });
        $this->equalsFunction = isset($equalsFunction) ? $equalsFunction : (function ($a, $b) {
            return Utils::standardEqualsFunction($a, $b);
        });
    }

    function getLength()
    {
        $l = 0;
        foreach ($this->data as $key => $value) {
            if (strpos($key, "hash_") === 0) {
                $l = $l + count($this->data[$key]);
            }
        }
        return $l;
    }

    function add($value)
    {
        $hash = $this->hashFunction->call($value);
        $key = "hash_" . $hash;
        if (isset($this->data[$key])) {
            $values = $this->data[$key];
            for ($i = 0; $i < $values->length; $i++) {
                if ($this->equalsFunction->call($value, $values[$i])) {
                    return $values[$i];
                }
            }
            array_push($values, $value);
            return $value;
        } else {
            $this->data[$key] = [$value];
            return $value;
        }
    }

    function contains($value)
    {
        return $this->get($value) != null;
    }

    function get($value)
    {
        $hash = $this->hashFunction->call($value);
        $key = "hash_" . $hash;
        if (isset($this->data[$key])) {
            $values = $this->data[$key];
            for ($i = 0; $i < count($values); $i++) {
                if ($this->equalsFunction->call($value, $values[$i])) {
                    return $values[$i];
                }
            }
        }
        return null;
    }

    function values()
    {
        $l = [];
        foreach ($this->data as $key => $value) {
            if (strpos($key, "hash_") === 0) {
                $l = array_merge($l, $value);
            }
        }
        return $l;
    }

    function __toString()
    {
        return Utils::arrayToString($this->values());
    }
}