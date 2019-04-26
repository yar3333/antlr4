<?php

namespace Antlr4;

/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

class Utils
{
    static function arrayToString(array $a) : string
    {
        return "[" . implode(", ", $a) . "]";
    }

    //seed = round(random() * pow(2, 32));
    static function hashCode($obj)
    {
        $key = is_object($obj) ? ((object)$obj)->toString() : (string)$obj;

        $remainder = strlen($key) & 3;// key.length % 4
        $bytes = strlen($key) - $remainder;
        $h1 = 123131231312;//String::prototype->seed;
        $c1 = 0xcc9e2d51;
        $c2 = 0x1b873593;
        $i = 0;

        while ($i < $bytes)
        {
            $k1 =
                ((ord($key[$i])) & 0xff) |
                ((ord($key[++$i]) & 0xff) << 8) |
                ((ord($key[++$i]) & 0xff) << 16) |
                ((ord($key[++$i]) & 0xff) << 24);
            ++$i;

            $k1 = (((($k1 & 0xffff) * $c1) + (((($k1 >> 16) * $c1) & 0xffff) << 16))) & 0xffffffff;
            $k1 = ($k1 << 15) | ($k1 >> 17);
            $k1 = (((($k1 & 0xffff) * $c2) + (((($k1 >> 16) * $c2) & 0xffff) << 16))) & 0xffffffff;

            $h1 ^= $k1;
            $h1 = ($h1 << 13) | ($h1 >> 19);
            $h1b = (((($h1 & 0xffff) * 5) + (((($h1 >> 16) * 5) & 0xffff) << 16))) & 0xffffffff;
            $h1 = ((($h1b & 0xffff) + 0x6b64) + (((($h1b >> 16) + 0xe654) & 0xffff) << 16));
        }

        $k1 = 0;

        switch ($remainder)
        {
            case 3:
                $k1 ^= (ord($key[$i + 2]) & 0xff) << 16;
            case 2:
                $k1 ^= (ord($key[$i + 1]) & 0xff) << 8;
            case 1:
                $k1 ^= ord($key[$i]) & 0xff;

                $k1 = ((($k1 & 0xffff) * $c1) + (((($k1 >> 16) * $c1) & 0xffff) << 16)) & 0xffffffff;
                $k1 = ($k1 << 15) | ($k1 >> 17);
                $k1 = ((($k1 & 0xffff) * $c2) + (((($k1 >> 16) * $c2) & 0xffff) << 16)) & 0xffffffff;
                $h1 ^= $k1;
        }

        $h1 ^= strlen($key);

        $h1 ^= $h1 >> 16;
        $h1 = ((($h1 & 0xffff) * 0x85ebca6b) + (((($h1 >> 16) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
        $h1 ^= $h1 >> 13;
        $h1 = (((($h1 & 0xffff) * 0xc2b2ae35) + (((($h1 >> 16) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
        $h1 ^= $h1 >> 16;

        return $h1 >> 0;
    }

    static function standardEqualsFunction(object $a, object $b)
    {
        return $a->equals($b);
    }

    static function standardHashCodeFunction(object $a)
    {
        return $a->hashCode();
    }

    static function hashStuff(...$arguments)
    {
        $hash = new Hash();
        $hash->update($arguments);
        return $hash->finish();
    }

    function escapeWhitespace($s, $escapeSpaces)
    {
        $s = preg_replace('/\n/g', "\\n", $s);
        $s = preg_replace('/\r/g', "\\r", $s);
        $s = preg_replace('/\t/g', "\\t", $s);

        if ($escapeSpaces)
        {
            $s = preg_replace('/ /g', "\u00B7", $s);
        }

        return $s;
    }

    function titleCase($str)
    {
        return preg_replace_callback('/\w\S*/g', function($txt) { return mb_strtoupper($txt[0]) . substr($txt, 1); }, $str);
    }
    
    function equalArrays($a, $b)
    {
        if (!is_array($a) || !is_array($b)) return false;
        if ($a == $b) return true;
        if (count($a) != count($b)) return false;
        for ($i = 0; $i < count($a); $i++)
        {
            if ($a[$i] == $b[$i]) continue;
            if (!$a[$i].equals($b[$i])) return false;
        }
        return true;
    }
}


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

    function __construct(\Closure $hashFunction, \Closure $equalsFunction)
    {
        $this->data = [];
        $this->hashFunction = isset($hashFunction) ? $hashFunction : (function($a) { return Utils::standardHashCodeFunction($a); });
        $this->equalsFunction = isset($equalsFunction) ? $equalsFunction : (function($a, $b) { return Utils::standardEqualsFunction($a, $b); });
    }

    function getLength()
    {
        $l = 0;
        foreach ($this->data as $key => $value)
        {
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
        if (isset($this->data[$key]))
        {
            $values = $this->data[$key];
            for ($i = 0; $i < $values->length; $i++)
            {
                if ($this->equalsFunction->call($value, $values[$i]))
                {
                    return $values[$i];
                }
            }
            $values->push($value);
            return $value;
        }
        else
        {
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
        if (isset($this->data[$key]))
        {
            $values = $this->data[$key];
            for ($i = 0; $i < count($values); $i++)
            {
                if ($this->equalsFunction->call($value, $values[$i]))
                {
                    return $values[$i];
                }
            }
        }
        return null;
    }

    function values ()
    {
        $l = [];
        foreach ($this->data as $key => $value)
        {
            if (strpos($key, "hash_") === 0) {
                $l = array_merge($l, $value);
            }
        }
        return $l;
    }

    function toString()
    {
        return Utils::arrayToString($this->values());
    }
}

class BitSet
{
    public $data;

    function __construct()
    {
        $this->data = [];
        return $this;
    }

    function add ($value)
    {
        $this->data[$value] = true;
    }

    function or($set)
    {
        foreach ($set->data as $alt) $this->add($alt);
    }

    function remove ($value)
    {
        unset($this->data[$value]);
    }

    function contains ($value)
    {
        return $this->data[$value] === true;
    }

    function values()
    {
        return array_keys($this->data);
    }

    function minValue ()
    {
        return min($this->values());
    }

    function hashCode ()
    {
        $hash = new Hash();
        $hash->update($this->values());
        return $hash->finish();
    }

    function equals($other)
    {
        if (!($other instanceof BitSet))
        {
            return false;
        }
        return $this->hashCode() === $other->hashCode();
    }

    function getLength() { return count($this->values()); }

    function toString()
    {
        return "{" . implode(", ", $this->values()) . "}";
    }
}

class Map
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var \Closure
     */
    public $hashFunction;

    /*
     * @var Closure
     */
    public $equalsFunction;

    function __construct(\Closure $hashFunction, \Closure $equalsFunction)
    {
        $this->data = [];
        $this->hashFunction = isset($hashFunction) ? $hashFunction : (function($a) { return Utils::standardHashCodeFunction($a); });
        $this->equalsFunction = isset($equalsFunction) ? $equalsFunction : (function($a, $b) { return Utils::standardEqualsFunction($a, $b); });
    }

    function getLength()
    {
        $l = 0;
        foreach ($this->data as $hashKey => $v)
        {
            if (strpos($hashKey, "hash_") === 0) {
                $l = $l + count($this->data[$hashKey]);
            }
        }
        return $l;
    }

    function put($key, $value)
    {
        $hashKey = "hash_" . $this->hashFunction->call($key);
        if (isset($this->data[$hashKey]))
        {
            $entries = $this->data[$hashKey];
            for ($i = 0; $i < count($entries); $i++)
            {
                $entry = $entries[$i];
                if ($this->equalsFunction->call($key, $entry['key']))
                {
                    $oldValue = $entry['value'];
                    $entry['value'] = $value;
                    return $oldValue;
                }
            }
            $entries->push(['key'=>$key, 'value'=>$value]);
            return $value;
        }
        else
        {
            $this->data[$hashKey] = [['key'=>$key, 'value'=>$value]];
            return $value;
        }
    }

    function containsKey($key)
    {
        $hashKey = "hash_" . $this->hashFunction->call($key);
        if (isset($this->data[$hashKey]))
        {
            $entries = $this->data[$hashKey];
            foreach ($entries as $entry)
            {
                if ($this->equalsFunction->call($key, $entry['key'])) return true;
            }
        }
        return false;
    }

    function get($key)
    {
        $hashKey = "hash_" . $this->hashFunction->call($key);
        if (isset($this->data[$hashKey]))
        {
            $entries = $this->data[$hashKey];
            foreach ($entries as $entry)
            {
                if ($this->equalsFunction->call($key, $entry['key'])) return $entry['value'];
            }
        }
        return null;
    }

    function entries()
    {
        $l = [];
        foreach ($this->data as $key => $value)
        {
            if (strpos($key, "hash_") === 0)
            {
                $l = array_merge($l, $value);
            }
        }
        return $l;
    }

    function getKeys() : array
    {
        return array_map(function($e) { return $e['key']; }, $this->entries());
    }

    function getValues() : array
    {
        return array_map(function($e) { return $e['value']; }, $this->entries());
    }


    function toString ()
    {
        $ss = array_map(function($entry) { return '{' . $entry['key'] . ':' . $entry['value'] . '}'; }, $this->entries());
        return '[' . implode(", ", $ss) . ']';
    }
}

class AltDict
{
    public $data;

    function __construct()
    {
        $this->data = [];
    }

    function get ($key)
    {
        $key = "k-" . $key;
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    function put($key, $value)
    {
        $key = "k-" . $key;
        $this->data[$key] = $value;
    }

    function values()
    {
        return array_values($this->data);
    }
}

class DoubleDict
{
    function get($a, $b)
    {
        $d = $this[$a] || null;
        return $d === null ? null : ($d[$b] || null);
    }

    function set($a, $b, $o)
    {
        $d = $this[$a] || null;
        if ($d === null)
        {
            $d = [];
            $this[$a] = $d;
        }
        $d[$b] = $o;
    }
}

class Hash
{
    public $count;
    public $hash;

    function __construct()
    {
        $this->count = 0;
        $this->hash = 0;
    }

    function update(...$arguments)
    {
        for ($i = 0; $i < count($arguments); $i++)
        {
            $value = $arguments[$i];
            if ($value === null) continue;
            if (is_array($value)) foreach ($value as $v) $this->update($v);
            else
            {
                //$k = 0;

                if (is_string($value)) $k = Utils::hashCode($value);
                else
                if (is_int($value)) $k = $value;
                else
                if (is_float($value)) $k = $value;
                else
                if (is_bool($value)) $k = $value ? 1 : 0;
                else {
                    $value->updateHashCode($this);
                    continue;
                }

                $k = $k * 0xCC9E2D51;
                $k = ($k << 15) | ($k >> (32 - 15));
                $k = $k * 0x1B873593;

                $this->count = $this->count + 1;

                $hash = $this->hash ^ $k;
                $hash = ($hash << 13) | ($hash >> (32 - 13));
                $hash = $hash * 5 + 0xE6546B64;

                $this->hash = $hash;
            }
        }
    }

    function finish()
    {
        $hash = $this->hash ^ ($this->count * 4);
        $hash = $hash ^ ($hash >> 16);
        $hash = $hash * 0x85EBCA6B;
        $hash = $hash ^ ($hash >> 13);
        $hash = $hash * 0xC2B2AE35;
        $hash = $hash ^ ($hash >> 16);
        return $hash;
    }
}
