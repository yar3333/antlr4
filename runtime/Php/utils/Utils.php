<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Utils;

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
            /** @noinspection PhpMissingBreakStatementInspection */
            case 3:
                $k1 ^= (ord($key[$i + 2]) & 0xff) << 16;
            /** @noinspection PhpMissingBreakStatementInspection */
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

    static function escapeWhitespace($s, $escapeSpaces)
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

    static function titleCase($str)
    {
        return preg_replace_callback('/\w\S*/g', function($txt) { return mb_strtoupper($txt[0]) . substr($txt, 1); }, $str);
    }

    static function equalArrays($a, $b)
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

    static function fromCodePoint(...$codes) : string
    {
        $s = '';
        foreach ($codes as $code) $s .= mb_chr($code, 'UTF-8');
        return $s;
    }

    static function codePointAt(string $s, int $pos) : int
    {
        return mb_ord(mb_substr($s, $pos, 1, 'UTF-8'), 'UTF-8');
    }

    static function charCodeAt(string $s, int $pos) : int
    {
        return mb_ord(mb_substr($s, $pos, 1, 'UTF-8'), 'UTF-8');
    }

    static function fromCharCode(int $code) : string
    {
        return mb_chr($code, 'UTF-8');
    }
}
