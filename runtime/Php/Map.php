<?php

namespace Antlr4;

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
        foreach ($this->data as $hashKey => $v) {
            if (strpos($hashKey, "hash_") === 0) {
                $l = $l + count($this->data[$hashKey]);
            }
        }
        return $l;
    }

    function put($key, $value)
    {
        $hashKey = "hash_" . $this->hashFunction->call($key);
        if (isset($this->data[$hashKey])) {
            $entries = $this->data[$hashKey];
            for ($i = 0; $i < count($entries); $i++) {
                $entry = $entries[$i];
                if ($this->equalsFunction->call($key, $entry['key'])) {
                    $oldValue = $entry['value'];
                    $entry['value'] = $value;
                    return $oldValue;
                }
            }
            array_push($entries, ['key' => $key, 'value' => $value]);
            return $value;
        } else {
            $this->data[$hashKey] = [['key' => $key, 'value' => $value]];
            return $value;
        }
    }

    function containsKey($key)
    {
        $hashKey = "hash_" . $this->hashFunction->call($key);
        if (isset($this->data[$hashKey])) {
            $entries = $this->data[$hashKey];
            foreach ($entries as $entry) {
                if ($this->equalsFunction->call($key, $entry['key'])) return true;
            }
        }
        return false;
    }

    function get($key)
    {
        $hashKey = "hash_" . $this->hashFunction->call($key);
        if (isset($this->data[$hashKey])) {
            $entries = $this->data[$hashKey];
            foreach ($entries as $entry) {
                if ($this->equalsFunction->call($key, $entry['key'])) return $entry['value'];
            }
        }
        return null;
    }

    function entries()
    {
        $l = [];
        foreach ($this->data as $key => $value) {
            if (strpos($key, "hash_") === 0) {
                $l = array_merge($l, $value);
            }
        }
        return $l;
    }

    function getKeys(): array
    {
        return array_map(function ($e) {
            return $e['key'];
        }, $this->entries());
    }

    function getValues(): array
    {
        return array_map(function ($e) {
            return $e['value'];
        }, $this->entries());
    }


    function toString()
    {
        $ss = array_map(function ($entry) {
            return '{' . $entry['key'] . ':' . $entry['value'] . '}';
        }, $this->entries());
        return '[' . implode(", ", $ss) . ']';
    }
}