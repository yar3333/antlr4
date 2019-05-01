<?php
/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace org\antlr\v4\runtime\tree\pattern;

use Antlr4\CommonTokenStream;
use Antlr4\Error\Exceptions\ParseCancellationException;
use Antlr4\Error\Exceptions\RecognitionException;
use Antlr4\Lexer;
use Antlr4\Parser;
use Antlr4\Token;
use Antlr4\Tree\ParseTree;
use Antlr4\Tree\Pattern\CannotInvokeStartRule;
use Antlr4\Tree\Pattern\StartRuleDoesNotConsumeFullPattern;
use Antlr4\Utils\MultiMap;

/**
 * A tree pattern matching mechanism for ANTLR {@link ParseTree}s.
 *
 * <p>Patterns are strings of source input text with special tags representing
 * token or rule references such as:</p>
 *
 * <p>{@code <ID> = <expr>;}</p>
 *
 * <p>Given a pattern start rule such as {@code statement}, this object constructs
 * a {@link ParseTree} with placeholders for the {@code ID} and {@code expr}
 * subtree. Then the {@link #match} routines can compare an actual
 * {@link ParseTree} from a parse with this pattern. Tag {@code <ID>} matches
 * any {@code ID} token and tag {@code <expr>} references the result of the
 * {@code expr} rule (generally an instance of {@code ExprContext}.</p>
 *
 * <p>Pattern {@code x = 0;} is a similar pattern that matches the same pattern
 * except that it requires the identifier to be {@code x} and the expression to
 * be {@code 0}.</p>
 *
 * <p>The {@link #matches} routines return {@code true} or {@code false} based
 * upon a match for the tree rooted at the parameter sent in. The
 * {@link #match} routines return a {@link ParseTreeMatch} object that
 * contains the parse tree, the parse tree pattern, and a map from tag name to
 * matched nodes (more below). A subtree that fails to match, returns with
 * {@link ParseTreeMatch#mismatchedNode} set to the first tree node that did not
 * match.</p>
 *
 * <p>For efficiency, you can compile a tree pattern in string form to a
 * {@link ParseTreePattern} object.</p>
 *
 * <p>See {@code TestParseTreeMatcher} for lots of examples.
 * {@link ParseTreePattern} has two static helper methods:
 * {@link ParseTreePattern#findAll} and {@link ParseTreePattern#match} that
 * are easy to use but not super efficient because they create new
 * {@link ParseTreePatternMatcher} objects each time and have to compile the
 * pattern in string form before using it.</p>
 *
 * <p>The lexer and parser that you pass into the {@link ParseTreePatternMatcher}
 * constructor are used to parse the pattern in string form. The lexer converts
 * the {@code <ID> = <expr>;} into a sequence of four tokens (assuming lexer
 * throws out whitespace or puts it on a hidden channel). Be aware that the
 * input stream is reset for the lexer (but not the parser; a
 * {@link ParserInterpreter} is created to parse the input.). Any user-defined
 * fields you have put into the lexer might get changed when this mechanism asks
 * it to scan the pattern string.</p>
 *
 * <p>Normally a parser does not accept token {@code <expr>} as a valid
 * {@code expr} but, from the parser passed in, we create a special version of
 * the underlying grammar representation (an {@link ATN}) that allows imaginary
 * tokens representing rules ({@code <expr>}) to match entire rules. We call
 * these <em>bypass alternatives</em>.</p>
 *
 * <p>Delimiters are {@code <} and {@code >}, with {@code \} as the escape string
 * by default, but you can set them to whatever you want using
 * {@link #setDelimiters}. You must escape both start and stop strings
 * {@code \<} and {@code \>}.</p>
 */
class ParseTreePatternMatcher
{
	/**
	 * This is the backing field for {@link #getLexer()}.
	 * @var Lexer
	 */
	private $lexer;
	
	/**
	 * This is the backing field for {@link #getParser()}.
	 * @var Parser
	 */
	private $parser;
	
	/**
	 * @var string
	 */
	protected $start = "<";

	/**
	 * @var string
	 */
	protected $stop = ">";

	/**
	 * @var string
	 */
	protected $escape = "\\"; // e.g., \< and \> must escape BOTH!
	
	/**
	 * Constructs a {@link ParseTreePatternMatcher} or from a {@link Lexer} and
	 * {@link Parser} object. The lexer input stream is altered for tokenizing
	 * the tree patterns. The parser is used as a convenient mechanism to get
	 * the grammar name, plus token, rule names.
	 * @param Lexer $lexer 
	 * @param Parser $parser 
	 */
	public function __construct(Lexer $lexer, Parser $parser)
	{
		$this->lexer = $lexer;
		$this->parser = $parser;
	}
	
	/**
	 * Set the delimiters used for marking rule and token tags within concrete
	 * syntax used by the tree pattern parser.
	 *
	 * @param string $start  The start delimiter.
	 * @param string $stop The stop delimiter.
	 * @param string $escapeLeft  The escape sequence to use for escaping a start or stop delimiter.
     * @throws \Exception
	 */
	public function setDelimiters(string $start, string $stop, string $escapeLeft) : void
	{
		if (!$start) {
			throw new \Exception("start cannot be null or empty");
		}
		
		if (!$stop)
		{
			throw new \Exception("stop cannot be null or empty");
		}
		
		$this->start = $start;
		$this->stop = $stop;
		$this->escape = $escapeLeft;
	}
	/** Does {@code pattern} matched as rule {@code patternRuleIndex} match {@code tree}?
	 * @param ParseTree $tree 
	 * @param string $pattern 
	 * @param int $patternRuleIndex 
	 * @return bool
	 */
	public function matchesEx(ParseTree $tree, string $pattern, int $patternRuleIndex) : bool
	{
		/** @var ParseTreePattern $p */
		$p = $this->compile($pattern, $patternRuleIndex);
		return $this->matches($tree, $p);
	}
	
	/**
	 * Does {@code pattern} matched as rule patternRuleIndex match tree? Pass in a
	 * compiled pattern instead of a string representation of a tree pattern.
	 * @param ParseTree $tree 
	 * @param ParseTreePattern $pattern 
	 * @return bool
	 */
	public function matches(ParseTree $tree, ParseTreePattern $pattern) : bool
	{
		/** @var MultiMap $labels */
		$labels = new MultiMap();
		/** @var ParseTree $mismatchedNode */
		$mismatchedNode = $this->matchImpl($tree, $pattern->getPatternTree(), $labels);
		return $mismatchedNode === null;
	}
	
	/**
	 * Compare {@code pattern} matched as rule {@code patternRuleIndex} against
	 * {@code tree} and return a {@link ParseTreeMatch} object that contains the
	 * matched elements, or the node at which the match failed.
	 * @param ParseTree $tree 
	 * @param string $pattern 
	 * @param int $patternRuleIndex 
	 * @return ParseTreeMatch
     * @throws \Exception
	 */
	public function matchWithEx(ParseTree $tree, string $pattern, int $patternRuleIndex) : ParseTreeMatch
	{
		/** @var ParseTreePattern $p */
		$p = $this->compile($pattern, $patternRuleIndex);
		return $this->match($tree, $p);
	}
	
	/**
	 * Compare {@code pattern} matched against {@code tree} and return a
	 * {@link ParseTreeMatch} object that contains the matched elements, or the
	 * node at which the match failed. Pass in a compiled pattern instead of a
	 * string representation of a tree pattern.
	 * @param ParseTree $tree
	 * @param ParseTreePattern $pattern 
	 * @return ParseTreeMatch
     * @throws \Exception
	 */
	public function match(ParseTree $tree, ParseTreePattern $pattern) : ParseTreeMatch
	{
		/** @var MultiMap $labels */
		$labels = new MultiMap();
		/** @var ParseTree $mismatchedNode */
		$mismatchedNode = $this->matchImpl($tree, $pattern->getPatternTree(), $labels);
		return new ParseTreeMatch($tree, $pattern, $labels, $mismatchedNode);
	}
	
	/**
	 * For repeated use of a tree pattern, compile it to a
	 * {@link ParseTreePattern} using this method.
	 * @param string $pattern 
	 * @param int $patternRuleIndex 
	 * @return ParseTreePattern
	 */
	public function compile(string $pattern, int $patternRuleIndex) : ParseTreePattern
	{
		/** @var array $tokenList */
		$tokenList = $this->tokenize($pattern);
		/** @var ListTokenSource $tokenSrc */
		$tokenSrc = new ListTokenSource($tokenList);
		/** @var CommonTokenStream $tokens */
		$tokens = new CommonTokenStream($tokenSrc);
		
		/** @var ParserInterpreter $parserInterp */
		$parserInterp = new ParserInterpreter($this->parser->getGrammarFileName(),
															   $this->parser->getVocabulary(),
															   $this->parser->getRuleNames(),
															   $this->parser->getATNWithBypassAlts(),
															   $tokens);
		
		/** @var ParseTree $tree */
		$tree = null;
		try
		{
			$parserInterp->setErrorHandler(new BailErrorStrategy());
			$tree = $parserInterp->parse($patternRuleIndex);
//			System.out.println("pattern tree = "+tree.toStringTree(parserInterp));
		}
		catch (ParseCancellationException $e)
		{
			throw $e->getCause();
		}
		catch (RecognitionException $re)
		{
			throw $re;
		}
		catch (\Exception $e)
		{
			throw new CannotInvokeStartRule($e);
		}
		
		// Make sure tree pattern compilation checks for a complete parse
		if ( $tokens->LA(1)!==Token::EOF)
		{
			throw new StartRuleDoesNotConsumeFullPattern();
		}
		
		return new ParseTreePattern($this, $pattern, $patternRuleIndex, $tree);
	}
	
	/**
	 * Used to convert the tree pattern string into a series of tokens. The
	 * input stream is reset.
	 */
	
	/**
	 * @return Lexer
	 */
	public function getLexer() : Lexer
	{
		return $this->lexer;
	}
	
	/**
	 * Used to collect to the grammar file name, token names, rule names for
	 * used to parse the pattern into a parse tree.
	 */
	
	/**
	 * @return Parser
	 */
	public function getParser() : Parser
	{
		return $this->parser;
	}
	
	// ---- SUPPORT CODE ----
	
	/**
	 * Recursively walk {@code tree} against {@code patternTree}, filling
	 * {@code match.}{@link ParseTreeMatch#labels labels}.
	 *
	 * @param ParseTree $tree
	 * @param ParseTree $patternTree
	 * @param MultiMap $labels
	 * @return ParseTree The first node encountered in {@code tree} which does not match
	 * a corresponding node in {@code patternTree}, or {@code null} if the match
	 * was successful. The specific node returned depends on the matching
	 * algorithm used by the implementation, and may be overridden.
	 */
	protected function matchImpl(ParseTree $tree, ParseTree $patternTree, MultiMap $labels) : ParseTree
    {
		if ($tree === null) {
			throw new \Exception("tree cannot be null");
		}
		
		if ($patternTree === null)
		{
			throw new \Exception("patternTree cannot be null");
		}
		
		// x and <ID>, x and y, or x and x; or could be mismatched types
		if ( $tree instanceof TerminalNode && patternTree instanceof TerminalNode)
		{
			/** @var TerminalNode $t1 */
			$t1 = (TerminalNode)$tree;
			/** @var TerminalNode $t2 */
			$t2 = (TerminalNode)patternTree;
			/** @var ParseTree $mismatchedNode */
			$mismatchedNode = null;
			// both are tokens and they have same type
			if ( $t1->getSymbol()->getType() === $t2->getSymbol()->getType())
			{
				if ( $t2->getSymbol() instanceof TokenTagToken) { // x and <ID>
					/** @var TokenTagToken $tokenTagToken */
					tokenTagToken = (TokenTagToken)$t2->getSymbol();
					// track label->list-of-nodes for both token name and label (if any)
					$labels->map(tokenTagToken->getTokenName(), $tree);
					if ( tokenTagToken->getLabel()!==null)
					{
						$labels->map(tokenTagToken->getLabel(), $tree);
					}
				}
				else if ( $t1->getText()->equals($t2->getText()))
				{
					// x and x
				}
				else
				{
					// x and y
					if ($mismatchedNode === null)
					{
						$mismatchedNode = $t1;
					}
				}
			}
			else
			{
				if ($mismatchedNode === null) {
					$mismatchedNode = $t1;
				}
			}
			
			return $mismatchedNode;
		}
		
		if ( $tree instanceof ParserRuleContext && patternTree instanceof ParserRuleContext)
		{
			/** @var ParserRuleContext $r1 */
			$r1 = (ParserRuleContext)$tree;
			/** @var ParserRuleContext $r2 */
			$r2 = (ParserRuleContext)patternTree;
			/** @var ParseTree $mismatchedNode */
			$mismatchedNode = null;
			// (expr ...) and <expr>
			/** @var RuleTagToken $ruleTagToken */
			ruleTagToken = getRuleTagToken($r2);
			if ( ruleTagToken!==null)
			{
				/** @var ParseTreeMatch $m */
				$m = null;
				if ( $r1->getRuleContext()->getRuleIndex() === $r2->getRuleContext()->getRuleIndex())
				{
					// track label->list-of-nodes for both rule name and label (if any)
					$labels->map(ruleTagToken->getRuleName(), $tree);
					if ( ruleTagToken->getLabel()!==null)
					{
						$labels->map(ruleTagToken->getLabel(), $tree);
					}
				}
				else
				{
					if ($mismatchedNode === null) {
						$mismatchedNode = $r1;
					}
				}
				
				return $mismatchedNode;
			}
			
			// (expr ...) and (expr ...)
			if ( $r1->getChildCount()!==$r2->getChildCount())
			{
				if ($mismatchedNode === null) {
					$mismatchedNode = $r1;
				}
				
				return $mismatchedNode;
			}
			
			/** @var int $n */
			$n = $r1->getChildCount();
			for ($i = 0; $i<$n; $i++)
			{
				/** @var ParseTree $childMatch */
				$childMatch = matchImpl($r1->getChild($i), patternTree->getChild($i), $labels);
				if ( $childMatch !== null)
				{
					return $childMatch;
				}
			}
			
			return $mismatchedNode;
		}
		
		// if nodes aren't both tokens or both rule nodes, can't match
		return $tree;
	}
	/** Is {@code t} {@code (expr <expr>)} subtree?
	 * @param ParseTree $t 
	 * @return RuleTagToken
	 */
	protected function getRuleTagToken(ParseTree $t) : RuleTagToken
	{
		if ( $t instanceof RuleNode) {
			/** @var RuleNode $r */
			$r = (RuleNode)$t;
			if ( $r->getChildCount()===1 && $r->getChild(0) instanceof TerminalNode)
			{
				/** @var TerminalNode $c */
				$c = (TerminalNode)$r->getChild(0);
				if ( $c->getSymbol() instanceof RuleTagToken)
				{
//					System.out.println("rule tag subtree "+t.toStringTree(parser));
					return (RuleTagToken)$c->getSymbol();
				}
			}
		}
		return null;
	}
	
	/**
	 * @param string $pattern 
	 * @return array
	 */
	public function tokenize(string $pattern) : array
	{
		// split pattern into chunks: sea (raw input) and islands (<ID>, <expr>)
		/** @var Chunk[] $chunks */
		$chunks = split($pattern);
		
		// create token stream from text and tags
		/** @var Token[] $tokens */
		$tokens = new ArrayList<Token>();
		foreach ($chunks as $chunk)
		{
			if ($chunk instanceof TagChunk) {
				/** @var TagChunk $tagChunk */
				$tagChunk = (TagChunk)$chunk;
				// add special rule token or conjure up new token from name
				if ( Character::isUpperCase($tagChunk->getTag()->charAt(0)))
				{
					/** @var Integer $ttype */
					$ttype = $parser->getTokenType($tagChunk->getTag());
					if ( $ttype===Token::INVALID_TYPE)
					{
						throw new IllegalArgumentException("Unknown token "+tagChunk.getTag()+" in pattern: "+$pattern);
					}
					/** @var TokenTagToken $t */
					$t = new TokenTagToken($tagChunk->getTag(), $ttype, $tagChunk->getLabel());
					$tokens->add($t);
				}
				else if ( Character::isLowerCase($tagChunk->getTag()->charAt(0)))
				{
					/** @var int $ruleIndex */
					$ruleIndex = $parser->getRuleIndex($tagChunk->getTag());
					if ( $ruleIndex===-1)
					{
						throw new IllegalArgumentException("Unknown rule "+tagChunk.getTag()+" in pattern: "+$pattern);
					}
					/** @var int $ruleImaginaryTokenType */
					ruleImaginaryTokenType = $parser->getATNWithBypassAlts()->ruleToTokenType[$ruleIndex];
					$tokens->add(new RuleTagToken($tagChunk->getTag(), ruleImaginaryTokenType, $tagChunk->getLabel()));
				}
				else
				{
					throw new IllegalArgumentException("invalid tag: "+tagChunk.getTag()+" in pattern: "+$pattern);
				}
			}
			else
			{
				/** @var TextChunk $textChunk */
				$textChunk = (TextChunk)$chunk;
				/** @var ANTLRInputStream $in */
				$in = new ANTLRInputStream($textChunk->getText());
				$lexer->setInputStream($in);
				/** @var Token $t */
				$t = $lexer->nextToken();
				while ( $t->getType()!==Token::EOF)
				{
					$tokens->add($t);
					$t = $lexer->nextToken();
				}
			}
		}

//		System.out.println("tokens="+tokens);
		return $tokens;
	}
	/** Split {@code <ID> = <e:expr> ;} into 4 chunks for tokenizing by {@link #tokenize}.
	 * @param string $pattern 
	 * @return Chunk[]
	 */
	public function split(string $pattern) : array
	{
		/** @var int $p */
		$p = 0;
		/** @var int $n */
		$n = $pattern->length();
		/** @var Chunk[] $chunks */
		$chunks = new ArrayList<Chunk>();
		/** @var StringBuilder $buf */
		$buf = new StringBuilder();
		// find all start and stop indexes first, then collect
		/** @var Integer[] $starts */
		$starts = new ArrayList<Integer>();
		/** @var Integer[] $stops */
		$stops = new ArrayList<Integer>();
		while ( $p<$n)
		{
			if ( $p === $pattern->indexOf($escape+$start,$p)) {
				$p += $escape->length() + $start->length();
			}
			else if ( $p === $pattern->indexOf($escape+$stop,$p))
			{
				$p += $escape->length() + $stop->length();
			}
			else if ( $p === $pattern->indexOf($start,$p))
			{
				$starts->add($p);
				$p += $start->length();
			}
			else if ( $p === $pattern->indexOf($stop,$p))
			{
				$stops->add($p);
				$p += $stop->length();
			}
			else
			{
				$p++;
			}
		}

//		System.out.println("");
//		System.out.println(starts);
//		System.out.println(stops);
		if ( $starts->size() > $stops->size())
		{
			throw new IllegalArgumentException("unterminated tag in pattern: "+$pattern);
		}
		
		if ( $starts->size() < $stops->size())
		{
			throw new IllegalArgumentException("missing start tag in pattern: "+$pattern);
		}
		
		/** @var int $ntags */
		$ntags = $starts->size();
		for ($i=0; $i<$ntags; $i++)
		{
			if ( $starts->get($i)>=$stops->get($i)) {
				throw new IllegalArgumentException("tag delimiters out of order in pattern: "+$pattern);
			}
		}
		
		// collect into chunks now
		if ( $ntags===0)
		{
			/** @var string $text */
			$text = $pattern->substring(0, $n);
			$chunks->add(new TextChunk($text));
		}
		
		if ( $ntags>0 && $starts->get(0)>0) { // copy text up to first tag into chunks
			/** @var string $text */
			$text = $pattern->substring(0, $starts->get(0));
			$chunks->add(new TextChunk($text));
		}
		for ($i=0; $i<$ntags; $i++)
		{
			// copy inside of <tag>
			/** @var string $tag */
			$tag = $pattern->substring($starts->get($i) + $start->length(), $stops->get($i));
			/** @var string $ruleOrToken */
			ruleOrToken = $tag;
			/** @var string $label */
			$label = null;
			/** @var int $colon */
			$colon = $tag->indexOf(':');
			if ( $colon >= 0)
			{
				$label = $tag->substring(0,$colon);
				ruleOrToken = $tag->substring($colon+1, $tag->length());
			}
			$chunks->add(new TagChunk($label, ruleOrToken));
			if ( $i+1 < $ntags)
			{
				// copy from end of <tag> to start of next
				/** @var string $text */
				$text = $pattern->substring($stops->get($i) + $stop->length(), $starts->get($i + 1));
				$chunks->add(new TextChunk($text));
			}
		}
		if ( $ntags>0)
		{
			/** @var int $afterLastTag */
			afterLastTag = $stops->get($ntags - 1) + $stop->length();
			if ( afterLastTag < $n) { // copy text from end of last tag to end
				/** @var string $text */
				$text = $pattern->substring(afterLastTag, $n);
				$chunks->add(new TextChunk($text));
			}
		}
		
		// strip out the escape sequences from text chunks but not tags
		for ($i = 0; $i < $chunks->size(); $i++)
		{
			/** @var Chunk $c */
			$c = $chunks->get($i);
			if ( $c instanceof TextChunk)
			{
				/** @var TextChunk $tc */
				$tc = (TextChunk)$c;
				/** @var string $unescaped */
				$unescaped = $tc->getText()->replace($escape, "");
				if ($unescaped->length() < $tc->getText()->length())
				{
					$chunks->set($i, new TextChunk($unescaped));
				}
			}
		}
		
		return $chunks;
	}
}
