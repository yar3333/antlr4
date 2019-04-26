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
        $key = $obj->toString();

        $remainder = $key->length & 3;// key.length % 4
        $bytes = $key->length - $remainder;
        $h1 = 123131231312;//String::prototype->seed;
        $c1 = 0xcc9e2d51;
        $c2 = 0x1b873593;
        $i = 0;

        while ($i < $bytes)
        {
            $k1 =
                (($key->charCodeAt($i) & 0xff)) |
                (($key->charCodeAt(++$i) & 0xff) << 8) |
                (($key->charCodeAt(++$i) & 0xff) << 16) |
                (($key->charCodeAt(++$i) & 0xff) << 24);
            ++$i;

            $k1 = (((($k1 & 0xffff) * $c1) + (((($k1 >>> 16) * $c1) & 0xffff) << 16))) & 0xffffffff;
            $k1 = ($k1 << 15) | ($k1 >>> 17);
            $k1 = (((($k1 & 0xffff) * $c2) + (((($k1 >>> 16) * $c2) & 0xffff) << 16))) & 0xffffffff;

            $h1 ^= $k1;
            $h1 = ($h1 << 13) | ($h1 >>> 19);
            $h1b = (((($h1 & 0xffff) * 5) + (((($h1 >>> 16) * 5) & 0xffff) << 16))) & 0xffffffff;
            $h1 = ((($h1b & 0xffff) + 0x6b64) + (((($h1b >>> 16) + 0xe654) & 0xffff) << 16));
        }

        $k1 = 0;

        switch ($remainder)
        {
            case 3:
                $k1 ^= ($key->charCodeAt($i + 2) & 0xff) << 16;
            case 2:
                $k1 ^= ($key->charCodeAt($i + 1) & 0xff) << 8;
            case 1:
                $k1 ^= ($key->charCodeAt($i) & 0xff);

                $k1 = ((($k1 & 0xffff) * $c1) + (((($k1 >> 16) * $c1) & 0xffff) << 16)) & 0xffffffff;
                $k1 = ($k1 << 15) | ($k1 >> 17);
                $k1 = ((($k1 & 0xffff) * $c2) + (((($k1 >> 16) * $c2) & 0xffff) << 16)) & 0xffffffff;
                $h1 ^= $k1;
        }

        $h1 ^= $key->length;

        $h1 ^= $h1 >> 16;
        $h1 = ((($h1 & 0xffff) * 0x85ebca6b) + (((($h1 >> 16) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
        $h1 ^= $h1 >> 13;
        $h1 = (((($h1 & 0xffff) * 0xc2b2ae35) + (((($h1 >> 16) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
        $h1 ^= $h1 >> 16;

        return $h1 >> 0;
    }

    static function standardEqualsFunction($a, $b)
    {
        return $a->equals($b);
    }

    static function standardHashCodeFunction($a)
    {
        return $a->hashCode();
    }
}


class Set
{
    public $data;
    public $hashFunction;
    public $equalsFunction;

    function __construct($hashFunction, $equalsFunction)
    {
        $this->data = [];
        $this->hashFunction = $hashFunction || Utils::standardHashCodeFunction;
        $this->equalsFunction = $equalsFunction || Utils::standardEqualsFunction;
    }

    function getLength()
    {
        $l = 0;
        foreach ($this->data as $key => $value)
        {
            if ($key->indexOf("hash_") === 0) {
                $l = $l + $this->data[$key]->length;
            }
        }
        return $l;
    }

    function add($value)
    {
        /*var */$hash = $this->hashFunction($value);
        /*var */$key = "hash_" . $hash;
        if (isset($this->data[$key]))
        {
            $values = $this->data[$key];
            for ($i = 0; $i < $values->length; $i++)
            {
                if ($this->equalsFunction($value, $values[$i]))
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
        $hash = $this->hashFunction($value);
        $key = "hash_" . $hash;
        if (isset($this->data[$key]))
        {
            /*var */$values = $this->data[$key];
            for ($i = 0; $i < $values->length; $i++)
            {
                if ($this->equalsFunction($value, $values[$i]))
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
            if ($key->indexOf("hash_") === 0) {
                $l = $l->concat($value);
            }
        }
        return $l;
    }

    function toString()
    {
        return arrayToString($this->values());
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

    /* BitSet */function add ($value)
    {
        $this->data[$value] = true;
    }

    /* BitSet */function or($set)
    {
        foreach ($set->data as $alt) $this->add($alt);
    }

    /* BitSet */function remove ($value)
    {
        unset($this->data[$value]);
    }

    /* BitSet */function contains ($value)
    {
        return $this->data[$value] === true;
    }

    /* BitSet */function values()
    {
        return array_keys($this->data);
    }

    /* BitSet */function minValue ()
    {
        return min($this->values());
    }

    /* BitSet */function hashCode ()
    {
        $hash = new Hash();
        $hash->update($this->values());
        return $hash->finish();
    }

    /* BitSet */function equals($other)
    {
        if (!($other instanceof BitSet))
        {
            return false;
        }
        return $this->hashCode() === $other->hashCode();
    }

    function getLength() { return $this->values()->length; }

    /* BitSet */function toString()
    {
        return "{" + this.values().join(", ") + "}";
    }
}


class Map($hashFunction, $equalsFunction
{


function Map($hashFunction, $equalsFunction)
{
    $this->data = {};
    $this->hashFunction = $hashFunction || $standardHashCodeFunction;
    $this->equalsFunction = $equalsFunction || $standardEqualsFunction;
    return $this;
}

Object->defineProperty(Map::prototype, "length", {
    $get: function () 
    {
        /*var */$l = 0;
        for ($hashKey $in $this->data) 
        {
            if ($hashKey->indexOf("hash_") === 0) {
                $l = $l + $this->data[$hashKey].$length;
            }
        }
        return $l;
    }
});

/* Map */function put ($key, $value) 
{
    /*var */$hashKey = "hash_" . $this->hashFunction($key);
    if ($hashKey $in $this->data) 
    {
        /*var */$entries = $this->data[$hashKey];
        for ($i = 0; $i < $entries->length; $i++) 
        {
            /*var */$entry = $entries[$i];
            if ($this->equalsFunction($key, $entry->key)) 
            {
                /*var */$oldValue = $entry->value;
                $entry->value = $value;
                return $oldValue;
            }
        }
        $entries->push({$key:$key, $value:$value});
        return $value;
    }
    else 
    {
        $this->data[$hashKey] = [{$key:$key, $value:$value}];
        return $value;
    }
};

/* Map */function containsKey ($key) 
{
    /*var */$hashKey = "hash_" . $this->hashFunction($key);
    if($hashKey $in $this->data) 
    {
        /*var */$entries = $this->data[$hashKey];
        for ($i = 0; $i < $entries->length; $i++) 
        {
            /*var */$entry = $entries[$i];
            if ($this->equalsFunction($key, $entry->key))
                return true;
        }
    }
    return false;
};

/* Map */function get ($key) 
{
    /*var */$hashKey = "hash_" . $this->hashFunction($key);
    if($hashKey $in $this->data) 
    {
        /*var */$entries = $this->data[$hashKey];
        for ($i = 0; $i < $entries->length; $i++) 
        {
            /*var */$entry = $entries[$i];
            if ($this->equalsFunction($key, $entry->key))
                return $entry->value;
        }
    }
    return null;
};

/* Map */function entries () 
{
    /*var */$l = [];
    for ($key $in $this->data) 
    {
        if ($key->indexOf("hash_") === 0) {
            $l = $l->concat($this->data[$key]);
        }
    }
    return $l;
};


/* Map */function getKeys () 
{
    return $this->entries().map(function($e) 
    {
        return $e->key;
    });
};


/* Map */function getValues () 
{
    return $this->entries().map(function($e) 
    {
            return $e->value;
    });
};


/* Map */function toString () 
{
    /*var */$ss = $this->entries().map(function($entry) 
    {
        return '{' + entry.key + ':' + entry.value + '}';
    });
    return '[' + ss.join(", ") + ']';
};


function AltDict() 
{
    $this->data = {};
    return $this;
}


/* AltDict */function get ($key) 
{
    $key = "k-" . $key;
    if ($key $in $this->data) 
    {
        return $this->data[$key];
    }
    else 
    {
        return null;
    }
};

/* AltDict */function put ($key, $value) 
{
    $key = "k-" . $key;
    $this->data[$key] = $value;
};

/* AltDict */function values () 
{
    /*var */$data = $this->data;
    /*var */$keys = Object->keys($this->data);
    return $keys->map(function ($key) 
    {
        return $data[$key];
    });
};

function DoubleDict() 
{
    return $this;
}

function Hash() 
{
    $this->count = 0;
    $this->hash = 0;
    return $this;
}

/* Hash */function update () 
{
    for($i=0;$i<$arguments->length;$i++) 
    {
        /*var */$value = $arguments[$i];
        if ($value == null)
            continue;
        if(Array->isArray($value))
            $this->update->apply($value);
        else 
        {
            /*var */$k = 0;
            switch (typeof($value)) 
            {
                case 'undefined':
                case 'function':
                    continue;
                case 'number':
                case 'boolean':
                    $k = $value;
                    break;
                case 'string':
                    $k = $value->hashCode();
                    break;
                $default:
                    $value->updateHashCode($this);
                    continue;
            }
            $k = $k * 0xCC9E2D51;
            $k = ($k << 15) | ($k >>> (32 - 15));
            $k = $k * 0x1B873593;
            $this->count = $this->count + 1;
            /*var */$hash = $this->hash ^ $k;
            $hash = ($hash << 13) | ($hash >>> (32 - 13));
            $hash = $hash * 5 + 0xE6546B64;
            $this->hash = $hash;
        }
    }
}

/* Hash */function finish () 
{
    /*var */$hash = $this->hash ^ ($this->count * 4);
    $hash = $hash ^ ($hash >>> 16);
    $hash = $hash * 0x85EBCA6B;
    $hash = $hash ^ ($hash >>> 13);
    $hash = $hash * 0xC2B2AE35;
    $hash = $hash ^ ($hash >>> 16);
    return $hash;
}

function hashStuff() 
{
    /*var */$hash = new Hash();
    $hash->update->apply($arguments);
    return $hash->finish();
}

/* DoubleDict */function get ($a, $b) 
{
    /*var */$d = $this[$a] || null;
    return $d === null ? null : ($d[$b] || null);
};

/* DoubleDict */function set ($a, $b, $o) 
{
    /*var */$d = $this[$a] || null;
    if ($d === null) 
    {
        $d = {};
        $this[$a] = $d;
    }
    $d[$b] = $o;
};


function escapeWhitespace($s, $escapeSpaces) 
{
    $s = $s->replace(/\$t/$g, "\\t")
         .replace(/\$n/$g, "\\n")
         .replace(/\$r/$g, "\\r");
    if ($escapeSpaces) 
    {
        $s = $s->replace(/ /$g, "\u00B7");
    }
    return $s;
}

function titleCase($str) 
{
    return $str->replace(/\$w\S*/$g, function ($txt) 
    {
        return $txt->charAt(0).toUpperCase() + $txt->substr(1);
    });
};

function equalArrays($a, $b)
{
    if (!Array->isArray($a) || !Array->isArray($b))
        return false;
    if ($a == $b)
        return true;
    if ($a->length != $b->length)
        return false;
    for ($i = 0; $i < $a->length; $i++) 
    {
        if ($a[$i] == $b[$i])
            continue;
        if (!$a[$i].equals($b[$i]))
            return false;
    }
    return true;
};

$exports->Hash = Hash;
$exports->Set = Set;
$exports->Map = Map;
$exports->BitSet = BitSet;
$exports->AltDict = AltDict;
$exports->DoubleDict = DoubleDict;
$exports->hashStuff = $hashStuff;
$exports->escapeWhitespace = $escapeWhitespace;
$exports->arrayToString = $arrayToString;
$exports->titleCase = $titleCase;
$exports->equalArrays = $equalArrays;
