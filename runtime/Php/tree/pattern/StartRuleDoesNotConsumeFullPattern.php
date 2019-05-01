<?php

namespace Antlr4\Tree\Pattern;

// Fixes https://github.com/antlr/antlr4/issues/413
// "Tree pattern compilation doesn't check for a complete parse"
class StartRuleDoesNotConsumeFullPattern extends \RuntimeException
{
}