<?php

namespace Antlr4\Error\Exceptions;

use \Antlr4\Utils\Utils;

class LexerNoViableAltException extends RecognitionException
{
    public $startIndex;
    public $deadEndConfigs;

    function __construct($lexer, $input, $startIndex, $deadEndConfigs)
    {
        parent::__construct((object)['message' => "", 'recognizer' => $lexer, 'input' => $input, 'ctx' => null]);

        $this->startIndex = $startIndex;
        $this->deadEndConfigs = $deadEndConfigs;
    }

    function __toString()
    {
        $symbol = "";
		if ($this->startIndex >= 0 && $this->startIndex < $this->input->size()) {
			$symbol = $this->input->getText($this->startIndex, $this->startIndex);
			$symbol = Utils::escapeWhitespace($symbol, false);
		}
		return self::class . "('$symbol')";
    }
}