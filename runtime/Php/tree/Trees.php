<?php
/* Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr4\Tree;

use Antlr4\Recognizer;
use Antlr4\RuleContext;
use Antlr4\Token;
use Antlr4\Utils\Utils;

/** A set of utility routines useful for all kinds of ANTLR trees. */
class Trees
{
    // Print out a whole tree in LISP form. {@link //getNodeText} is used on the
    //  node payloads to get the text for the nodes.  Detect
    //  parse trees and extract data appropriately.
    static function toStringTree($tree, $ruleNames, $recog)
    {
        $ruleNames = $ruleNames || null;
        $recog = $recog || null;
        if($recog!==null)
        {
           $ruleNames = $recog->ruleNames;
        }
        $s = self::getNodeText($tree, $ruleNames);
        $s = Utils::escapeWhitespace($s, false);
        $c = $tree->getChildCount();
        if($c===0)
        {
            return $s;
        }
        $res = "(" . $s . " ";
        if ($c > 0)
        {
            $s = Trees::toStringTree($tree->getChild(0), $ruleNames);
            $res = $res->concat($s);
        }
        for($i=1; $i<$c; $i++)
        {
            $s = self::toStringTree($tree->getChild($i), $ruleNames);
            $res = $res->concat(' ' . $s);
        }
        $res = $res->concat(")");
        return $res;
    }

    static function getNodeText($t, array $ruleNames, Recognizer $recog)
    {
        if ($recog !== null)
        {
            $ruleNames = $recog->ruleNames;
        }

        if ($ruleNames!==null)
        {
           if ($t instanceof RuleContext)
           {
               $altNumber = $t->getAltNumber();
               if ($altNumber != INVALID_ALT_NUMBER)
               {
                   return $ruleNames[$t->ruleIndex] . ":" . $altNumber;
               }
               return $ruleNames[$t->ruleIndex];
           }
           else if ($t instanceof ErrorNode)
           {
               return (string)$t;
           }
           else if ($t instanceof TerminalNode)
           {
               if ($t->symbol!==null)
               {
                   return $t->symbol->text;
               }
           }
        }
        // no recog for rule names
        $payload = $t->getPayload();
        if ($payload instanceof Token)
        {
           return $payload->text;
        }
        return (string)$t->getPayload();
    }

    // Return ordered list of all children of this node
    static function getChildren($t)
    {
        $list = [];
        for($i=0; $i<$t->getChildCount(); $i++)
        {
            array_push($list, $t->getChild($i));
        }
        return $list;
    }

    // Return a list of all ancestors of this node.  The first node of
    //  list is the root and the last is the parent of this node.
    static function getAncestors($t)
    {
        $ancestors = [];
        $t = $t->getParent();
        while($t!==null)
        {
            $ancestors = array_merge([$t],  $ancestors);
            $t = $t->getParent();
        }
        return $ancestors;
    }

    static function findAllTokenNodes($t, $ttype)
    {
        return self::findAllNodes($t, $ttype, true);
    }

    static function findAllRuleNodes($t, $ruleIndex)
    {
        return self::findAllNodes($t, $ruleIndex, false);
    }

    static function findAllNodes($t, $index, $findTokens)
    {
        $nodes = [];
        self::_findAllNodes($t, $index, $findTokens, $nodes);
        return $nodes;
    }

    static function _findAllNodes($t, $index, $findTokens, $nodes)
    {
        // check this node (the root) first
        if ($findTokens && ($t instanceof TerminalNode))
        {
            if($t->symbol->type===$index)
            {
                array_push($nodes, $t);
            }
        }
        else if(!$findTokens && ($t instanceof ParserRuleContext))
        {
            if($t->ruleIndex===$index)
            {
                array_push($nodes, $t);
            }
        }

        // check children
        for($i=0; $i<$t->getChildCount(); $i++)
        {
            self::_findAllNodes($t->getChild($i), $index, $findTokens, $nodes);
        }
    }

    static function descendants($t)
    {
        $nodes = [$t];
        for($i=0; $i<$t->getChildCount(); $i++)
        {
            $nodes = $nodes->concat(self::descendants($t->getChild($i)));
        }
        return $nodes;
    }
}