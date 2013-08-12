<?php

/**
 * Yii_Sniffs_ControlStructures_SingleLineWithoutBracesSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Artem Demchenkov <ardemchenkov@gmail.com>
 * @copyright 2013 Artem Demchenkov
 * @license   http://www.yiiframework.com/license/ BSD license
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Yii_Sniffs_ControlStructures_SingleLineWithoutBracesSniff.
 *
 * Ensures that a single line "if" have an expression on next line without braces.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Artem Demchenkov <ardemchenkov@gmail.com>
 * @copyright 2013 Artem Demchenkov
 * @license   http://www.yiiframework.com/license/ BSD license
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Yii_Sniffs_ControlStructures_SingleLineWithoutBracesSniff implements PHP_CodeSniffer_Sniff {
	
	/**
	 * Process the tokens that this sniff is listening for.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
	 * @param int|array            $types     The type(s) of tokens to search for.
	 * @param int                  $start     The position to start searching from in the token stack.
	 * @param int                  $end       The end position to fail if no token is found. if not specified or null, end will default to the end of the token stack.
	 * @param bool                 $exclude   If true, find the next token that is NOT of a type specified in $types.
	 * @param string               $value     The value that the token(s) must be equal to. If value is omitted, tokens with any value will be returned.
	 * @param bool                 $local     If true, tokens outside the current statement will not be checked. i.e., checking will stop at the next semi-colon found.
	 *
	 * @return void
	 */
	private function findNext($phpcsFile, $types, $start, $end = null, $exclude = false, $value = null, $local = false) {
		return  $phpcsFile->findNext($types, $start, $end, $exclude, $value, $local);
	}

	/**
	 * Registers the token types that this sniff wishes to listen to.
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_IF,
			T_WHILE,
			T_FOR,
			T_FOREACH,
			T_ELSE,
		);
	} //end register()

	/**
	 * Process the tokens that this sniff is listening for.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
	 * @param int                  $stackPtr  The position in the stack where
	 *                                        the token was found.
	 *
	 * @return void
	 */
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {

		$tokens        = $phpcsFile->getTokens();
		$startPosition = $stackPtr + 1;

		$allowedTokens = array(
			T_OPEN_CURLY_BRACKET
		);

		// special trick for "FOR". Exclude 2 internal semicolons 
		if ($tokens[$stackPtr]['code'] == T_FOR) {

			$specialTokens = array(
				T_SEMICOLON
			);

			$next = $stackPtr;

			for ($i = 0; $i <= 1; $i++)
				$next = $this->findNext($phpcsFile, $specialTokens, ($next + 1), null, false, null, false);

			$startPosition = $next + 1;
		} // end if

		$next = $this->findNext($phpcsFile, $allowedTokens, $startPosition, null, false, null, true);

		if ($next === false) { // this is single line structure.
			
			// find the last close parenthesis in the condition.
			$i = 0;
			$newParenthesis = $stackPtr;
			if( $tokens[$stackPtr]['type'] !== 'T_ELSE'){
				do {
					$newParenthesis = $phpcsFile->findNext(array(T_OPEN_PARENTHESIS, T_CLOSE_PARENTHESIS), ($newParenthesis + 1));
					$i = ($tokens[$newParenthesis]['type'] == "T_OPEN_PARENTHESIS") ? $i + 1 : $i - 1;		
				} while ($i != 0);
			}

			$closeBracket = $newParenthesis;
		
			// check the new line
			$n       = 1;
			$newline = false;
			
			do{
				$strlen = strlen($tokens[$closeBracket + $n]['content']);
				if ($tokens[$closeBracket + $n]['content'][$strlen - 1] == $phpcsFile->eolChar) {
					$newline = true;
					break;
				}
				$n++;
			} while ($tokens[$closeBracket + $n]['type'] == 'T_WHITESPACE' || $tokens[$closeBracket + $n]['type'] == 'T_COMMENT');
			
			if ($newline === false) {
				$error = 'Single line "%s" must have an expression started from new line.';
				$phpcsFile->addError($error, $stackPtr, 'SingleLineExpressionMustHaveANewLineExpression', array(strtoupper($tokens[$stackPtr]['content'])));
			}
		} // end if
	} //end process()
} //end class
