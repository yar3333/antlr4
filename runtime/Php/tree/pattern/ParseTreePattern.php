<?php
/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace org\antlr\v4\runtime\tree\pattern;

use Antlr4\Tree\ParseTree;

/**
 * A pattern like {@code <ID> = <expr>;} converted to a {@link ParseTree} by
 * {@link ParseTreePatternMatcher#compile(String, int)}.
 */
class ParseTreePattern
{
	/**
	 * This is the backing field for {@link #getPatternRuleIndex()}.
	 * @var int
	 */
	private $patternRuleIndex;
	
	/**
	 * This is the backing field for {@link #getPattern()}.
	 */
	
	/**
	 * @var string
	 */
	private $pattern;
	
	/**
	 * This is the backing field for {@link #getPatternTree()}.
	 */

    /**
     * @var ParseTree
     */
	private $patternTree;
	
	/**
	 * This is the backing field for {@link #getMatcher()}.
	 */
	
	/**
	 * @var ParseTreePatternMatcher
	 */
	private $matcher;
	
	/**
	 * Construct a new instance of the {@link ParseTreePattern} class.
	 *
	 * @param ParseTreePatternMatcher $matcher  The {@link ParseTreePatternMatcher} which created this tree pattern.
	 * @param string $pattern The tree pattern in concrete syntax form.
	 * @param int $patternRuleIndex  The parser rule which serves as the root of the tree pattern.
	 * @param ParseTree $patternTree The tree pattern in {@link ParseTree} form.	 * @param string $pattern 	 */
	public function __construct(ParseTreePatternMatcher $matcher, string $pattern, int $patternRuleIndex, ParseTree $patternTree)
    {
		$this->matcher = $matcher;
		$this->patternRuleIndex = $patternRuleIndex;
		$this->pattern = $pattern;
		$this->patternTree = $patternTree;
	}
	
	/**
	 * Match a specific parse tree against this tree pattern.
	 *
	 * @param ParseTree $tree The parse tree to match against this tree pattern.
	 * @return ParseTreeMatch A {@link ParseTreeMatch} object describing the result of the
	 * match operation. The {@link ParseTreeMatch#succeeded()} method can be
	 * used to determine whether or not the match was successful.
	 */
	public function match(ParseTree $tree) : ParseTreeMatch
	{
		return $this->matcher->match($tree, $this);
	}
	
	/**
	 * Determine whether or not a parse tree matches this tree pattern.
	 *
	 * @param ParseTree $tree  The parse tree to match against this tree pattern.
	 * @return {@code true} if {@code tree} is a match for the current tree
	 * pattern; otherwise, {@code false}.	 * @return bool
	 */
	public function matches(ParseTree $tree) : bool
	{
		return $this->matcher->match($tree, $this)->succeeded();
	}
	
	/**
	 * Find all nodes using XPath and then try to match those subtrees against
	 * this tree pattern.
	 *
	 * @param ParseTree $tree The {@link ParseTree} to match against this pattern.
	 * @param string $xpath An expression matching the nodes
	 *
	 * @return ParseTreeMatch[] A collection of {@link ParseTreeMatch} objects describing the
	 * successful matches. Unsuccessful matches are omitted from the result,
	 * regardless of the reason for the failure.
	 */
	public function findAll(ParseTree $tree, string $xpath) : array
	{
		/** @var Collection<ParseTree> $subtrees */
		$subtrees = XPath::findAll($tree, $xpath, $this->matcher->getParser());
		/** @var ParseTreeMatch[] $matches */
		$matches = [];
		foreach ($subtrees as $t)
		{
			/** @var ParseTreeMatch $match */
			$match = $this->match($t);
			if ( $match->succeeded())
			{
				$matches[] = $match;
			}
		}
		return $matches;
	}
	
	/**
	 * Get the {@link ParseTreePatternMatcher} which created this tree pattern.
	 *
	 * @return ParseTreePatternMatcher The {@link ParseTreePatternMatcher} which created this tree
	 * pattern.
	 */
	public function getMatcher() : ParseTreePatternMatcher
	{
		return $$this->matcher;
	}
	
	/**
	 * Get the tree pattern in concrete syntax form.
	 *
	 * @return string The tree pattern in concrete syntax form.
	 */
	public function getPattern() : string
	{
		return $this->pattern;
	}
	
	/**
	 * Get the parser rule which serves as the outermost rule for the tree pattern.
	 *
	 * @return int The parser rule which serves as the outermost rule for the tree pattern.
	 */
	public function getPatternRuleIndex() : int
	{
		return $this->patternRuleIndex;
	}
	
	/**
	 * Get the tree pattern as a {@link ParseTree}. The rule and token tags from
	 * the pattern are present in the parse tree as terminal nodes with a symbol
	 * of type {@link RuleTagToken} or {@link TokenTagToken}.
	 *
	 * @return ParseTree The tree pattern as a {@link ParseTree}.
	 */
	public function getPatternTree() : ParseTree
	{
		return $this->patternTree;
	}
}
