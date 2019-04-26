<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
* Use of this file is governed by the BSD 3-clause license that
* can be found in the LICENSE.txt file in the project root.
*/

namespace Antlr4;

use Antlr4\InputStream; //('./InputStream').InputStream;

// Utility functions to create InputStreams from various sources.
// All returned InputStreams support the full range of Unicode
// up to U+10FFFF (the default behavior of InputStream only supports
// code points up to U+FFFF).
class CharStreams
{
    function fromString($str)
    {
        return new InputStream($str, true);
    }

    // Creates an InputStream from a Buffer given the
    // encoding of the bytes in that buffer (defaults to 'utf8' if
    // encoding is null).
    function fromBuffer2($buffer, $encoding)
    {
        return new InputStream($buffer->toString($encoding), true);
    }

    // Synchronously creates an InputStream given a path to a file
    // on disk and the encoding of the bytes in that file (defaults to
    // 'utf8' if encoding is null).
    function fromPathSync($path, $encoding)
    {
        $data = file_get_contents($path);
        return new InputStream($data, true);
    }
}

