<?php

namespace Antlr4;

class DoubleDict
{
    /**
     * @var \ArrayObject
     */
    private $data;

    function __construct()
    {
        $this->data = new \ArrayObject();
    }

    function get($a, $b)
    {
        $d = $this->data[$a] ?? null;
        return $d === null ? null : ($d[$b] ?? null);
    }

    function set($a, $b, $o) : void
    {
        $d = $this->data[$a] ?? null;
        if ($d === null)
        {
            $d = [];
            $this->data[$a] = $d;
        }
        else
        {
            $d[$b] = $o;
        }
    }
}