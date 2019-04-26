<?php

namespace Antlr4;

class AltDict
{
    public $data;

    function __construct()
    {
        $this->data = [];
    }

    function get($key)
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