<?php

namespace Antlr4\PredictionContexts;

use Antlr4\Utils\Map;

class PredictionContextCache
{
    /**
     * @var Map
     */
    private $cache;

    function __construct()
    {
        $this->cache = new Map();
    }

    // Add a context to the cache and return it. If the context already exists,
    // return that one instead and do not add a new context to the cache.
    // Protect shared cache from unsafe thread access.
    function add(PredictionContext $ctx) : PredictionContext
    {
        if ($ctx === PredictionContext::EMPTY()) return PredictionContext::EMPTY();
        $existing = $this->cache->get($ctx);
        if ($existing !== null) {
            return $existing;
        }
        $this->cache->put($ctx, $ctx);
        return $ctx;
    }

    function get(PredictionContext $ctx) : ?PredictionContext
    {
        return $this->cache->get($ctx);
    }

    function size() : int
    {
        return $this->cache->size();
    }
}