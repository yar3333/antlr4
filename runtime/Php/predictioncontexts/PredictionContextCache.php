<?php

namespace Antlr4\Predictioncontexts;

class PredictionContextCache
{
    /**
     * @var array
     */
    private $cache;

    function __construct()
    {
        $this->cache = [];
    }

    // Add a context to the cache and return it. If the context already exists,
    // return that one instead and do not add a new context to the cache.
    // Protect shared cache from unsafe thread access.
    function add($ctx)
    {
        if ($ctx === PredictionContext::EMPTY) {
            return PredictionContext::EMPTY;
        }
        $existing = $this->cache[$ctx] || null;
        if ($existing !== null) {
            return $existing;
        }
        $this->cache[$ctx] = $ctx;
        return $ctx;
    }

    function get($ctx)
    {
        return $this->cache[$ctx] || null;
    }

    function getLength()
    {
        return count($this->cache);
    }
}