<?php
/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace org\antlr\v4\runtime\tree\pattern;

use Antlr4\Tree\ParseTree;
use Antlr4\Utils\MultiMap;

/**
 * Represents the result of matching a {@link ParseTree} against a tree pattern.
 */
class ParseTreeMatch
{
	/**
	 * This is the backing field for {@link #getTree()}.
	 * @var ParseTree
	 */
	private $tree;
	
	/**
	 * This is the backing field for {@link #getPattern()}.
	 * @var ParseTreePattern
	 */
	private $pattern;
	
	/**
	 * This is the backing field for {@link #getLabels()}.
	 * @var MultiMap<string, ParseTree>
	 */
	private $labels;
	
	/**
	 * This is the backing field for {@link #getMismatchedNode()}.
	 * @var ParseTree
	 */
	private $mismatchedNode;
	
	/**
	 * Constructs a new instance of {@link ParseTreeMatch} from the specified
	 * parse tree and pattern.
	 *
	 * @param ParseTree $tree  The parse tree to match against the pattern.
	 * @param ParseTreePattern $pattern The parse tree pattern.
	 * @param MultiMap<string, ParseTree> $labels A mapping from label names to collections of
	 * {@link ParseTree} objects located by the tree pattern matching process.
	 * @param ParseTree $mismatchedNode  The first node which failed to match the tree
	 * pattern during the matching process.
     * @throws \Exception
     */
	public function __construct(ParseTree $tree, ParseTreePattern $pattern, MultiMap $labels, ParseTree $mismatchedNode)
	{
		if ($tree === null) {
			throw new \Exception("tree cannot be null");
		}
		
		if ($pattern === null)
		{
			throw new \Exception("pattern cannot be null");
		}
		
		if ($labels === null)
		{
			throw new \Exception("labels cannot be null");
		}
		
		$this->tree = $tree;
		$this->pattern = $pattern;
		$this->labels = $labels;
		$this->mismatchedNode = $mismatchedNode;
	}
	
	/**
	 * Get the last node associated with a specific {@code label}.
	 *
	 * <p>For example, for pattern {@code <id:ID>}, {@code get("id")} returns the
	 * node matched for that {@code ID}. If more than one node
	 * matched the specified label, only the last is returned. If there is
	 * no node associated with the label, this returns {@code null}.</p>
	 *
	 * <p>Pattern tags like {@code <ID>} and {@code <expr>} without labels are
	 * considered to be labeled with {@code ID} and {@code expr}, respectively.</p>
	 *
	 * @param string $label The label to check.
	 *
	 * @return ParseTree The last {@link ParseTree} to match a tag with the specified
	 * label, or {@code null} if no parse tree matched a tag with the label.
	 */
	public function get(string $label) : ParseTree
	{
		/** @var ParseTree[] $parseTrees */
		$parseTrees = $this->labels->get($label);
		if ($parseTrees===null || count($parseTrees)===0)
		{
			return null;
		}
		
		return $parseTrees[count($parseTrees)-1]; // return last if multiple
	}
	
	/**
	 * Return all nodes matching a rule or token tag with the specified label.
	 *
	 * <p>If the {@code label} is the name of a parser rule or token in the
	 * grammar, the resulting list will contain both the parse trees matching
	 * rule or tags explicitly labeled with the label and the complete set of
	 * parse trees matching the labeled and unlabeled tags in the pattern for
	 * the parser rule or token. For example, if {@code label} is {@code "foo"},
	 * the result will contain <em>all</em> of the following.</p>
	 *
	 * <ul>
	 * <li>Parse tree nodes matching tags of the form {@code <foo:anyRuleName>} and
	 * {@code <foo:AnyTokenName>}.</li>
	 * <li>Parse tree nodes matching tags of the form {@code <anyLabel:foo>}.</li>
	 * <li>Parse tree nodes matching tags of the form {@code <foo>}.</li>
	 * </ul>
	 *
	 * @param string $label The label.
	 *
	 * @return ParseTree[] A collection of all {@link ParseTree} nodes matching tags with
	 * the specified {@code label}. If no nodes matched the label, an empty list
	 * is returned.
	 */
	public function getAll(string $label) : array
	{
		/** @var ParseTree[] $nodes */
		$nodes = $this->labels->get($label);
		if ($nodes===null)
		{
			return [];
		}
		
		return $nodes;
	}
	
	/**
	 * Return a mapping from label &rarr; [list of nodes].
	 *
	 * <p>The map includes special entries corresponding to the names of rules and
	 * tokens referenced in tags in the original pattern. For additional
	 * information, see the description of {@link #getAll(String)}.</p>
	 *
	 * @return MultiMap<string, ParseTree> mapping from labels to parse tree nodes. If the parse tree
	 * pattern did not contain any rule or token tags, this map will be empty.
	 */
	public function getLabels() : MultiMap
	{
		return $this->labels;
	}
	
	/**
	 * Get the node at which we first detected a mismatch.
	 *
	 * @return ParseTree the node at which we first detected a mismatch, or {@code null}
	 * if the match was successful.
	 */
	public function getMismatchedNode() : ParseTree
	{
		return $this->mismatchedNode;
	}
	
	/**
	 * Gets a value indicating whether the match operation succeeded.
	 *
	 * @return bool {@code true} if the match operation succeeded; otherwise, {@code false}.
	 */
	public function succeeded() : bool
	{
		return $this->mismatchedNode === null;
	}
	
	/**
	 * Get the tree pattern we are matching against.
	 *
	 * @return ParseTreePattern The tree pattern we are matching against.
	 */
	public function getPattern() : ParseTreePattern
	{
		return $this->pattern;
	}
	
	/**
	 * Get the parse tree we are trying to match to a pattern.
	 *
	 * @return ParseTree The {@link ParseTree} we are trying to match to a pattern.
	 */
	public function getTree() : ParseTree
	{
		return $this->tree;
	}
	
	/**
	 * {@inheritDoc}
	 * @return string
	 */
	public function toString() : string
	{
		return "Match " . ($this->succeeded() ? "succeeded" : "failed") . "; found " . $this->getLabels()->getLength() . " labels";
	}
}
