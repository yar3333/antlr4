<?php

namespace Antlr4;

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
        if ($d === null) {
            $d = [];
            $this[$a] = $d;
        }
        $d[$b] = $o;
    }
}