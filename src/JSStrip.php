<?php

namespace splitbrain\JSStrip;

/**
 * Strip comments and whitespaces from given JavaScript Code
 *
 * This is a port of Nick Galbreath's python tool jsstrip.py which is
 * released under BSD license. See link for original code.
 *
 * @author Nick Galbreath <nickg@modp.com>
 * @author Andreas Gohr <andi@splitbrain.org>
 * @link   http://code.google.com/p/jsstrip/
 */
class JSStrip
{

    const REGEX_STARTERS = [
        '(', '=', '<', '>', '?', '[', '{', ',', ';', ':', '!', '&', '|', '+', '-', '%', '~', '^',
        'return', 'yield', 'else', 'throw', 'await'
    ];
    const WHITESPACE_CHARS = [" ", "\t", "\n", "\r", "\0", "\x0B"];

    /** items that don't need spaces next to them */
    const CHARS = "^&|!+\-*\/%=\?:;,{}()<>% \t\n\r'\"`[]~^";

    /**
     * items which need a space if the sign before and after whitespace is equal.
     * E.g. '+ ++' may not be compressed to '+++' --> syntax error.
     */
    const OPS = "+-/";

    /**
     * Compress the given code
     * 
     * @param string $source The JavaScript code to compress
     * @return string
     */
    function compress($source)
    {
        $source = ltrim($source);     // strip all initial whitespace
        $source .= "\n";
        $i = 0;             // char index for input string
        $j = 0;             // char forward index for input string
        $line = 0;          // line number of file (close to it anyways)
        $slen = strlen($source); // size of input string
        $lch = '';         // last char added
        $result = '';       // we store the final result here


        while ($i < $slen) {
            // skip all "boring" characters.  This is either
            // reserved word (e.g. "for", "else", "if") or a
            // variable/object/method (e.g. "foo.color")
            while ($i < $slen && (strpos(self::CHARS, $source[$i]) === false)) {
                $result .= $source[$i];
                $i = $i + 1;
            }

            $ch = $source[$i];
            // multiline comments (keeping IE conditionals)
            if ($ch == '/' && $source[$i + 1] == '*' && $source[$i + 2] != '@') {
                $endC = strpos($source, '*/', $i + 2);
                if ($endC === false) trigger_error('Found invalid /*..*/ comment', E_USER_ERROR);

                // check if this is a NOCOMPRESS comment
                if (substr($source, $i, $endC + 2 - $i) == '/* BEGIN NOCOMPRESS */') {
                    // take nested NOCOMPRESS comments into account
                    $depth = 0;
                    $nextNC = $endC;
                    do {
                        $beginNC = strpos($source, '/* BEGIN NOCOMPRESS */', $nextNC + 2);
                        $endNC = strpos($source, '/* END NOCOMPRESS */', $nextNC + 2);

                        if ($endNC === false) trigger_error('Found invalid NOCOMPRESS comment', E_USER_ERROR);
                        if ($beginNC !== false && $beginNC < $endNC) {
                            $depth++;
                            $nextNC = $beginNC;
                        } else {
                            $depth--;
                            $nextNC = $endNC;
                        }
                    } while ($depth >= 0);

                    // verbatim copy contents, trimming but putting it on its own line
                    $result .= "\n" . trim(substr($source, $i + 22, $endNC - ($i + 22))) . "\n"; // BEGIN comment = 22 chars
                    $i = $endNC + 20; // END comment = 20 chars
                } else {
                    $i = $endC + 2;
                }
                continue;
            }

            // singleline
            if ($ch == '/' && $source[$i + 1] == '/') {
                $endC = strpos($source, "\n", $i + 2);
                if ($endC === false) trigger_error('Invalid comment', E_USER_ERROR);
                $i = $endC;
                continue;
            }

            // tricky.  might be an RE
            if ($ch == '/') {
                // rewind, skip white space
                $j = 1;
                while (in_array($source[$i - $j], self::WHITESPACE_CHARS)) {
                    $j = $j + 1;
                }
                if (current(array_filter(
                    self::REGEX_STARTERS,
                    function ($e) use ($source, $i, $j) {
                        $len = strlen($e);
                        $idx = $i - $j + 1 - $len;
                        return substr($source, $idx, $len) === $e;
                    }
                ))) {
                    // yes, this is an re
                    // now move forward and find the end of it
                    $j = 1;
                    // we set this flag when inside a character class definition, enclosed by brackets [] where '/' does not terminate the re
                    $ccd = false;
                    while ($ccd || $source[$i + $j] != '/') {
                        if ($source[$i + $j] == '\\') $j = $j + 2;
                        else {
                            $j++;
                            // check if we entered/exited a character class definition and set flag accordingly
                            if ($source[$i + $j - 1] == '[') $ccd = true;
                            else if ($source[$i + $j - 1] == ']') $ccd = false;
                        }
                    }
                    $result .= substr($source, $i, $j + 1);
                    $i = $i + $j + 1;
                    continue;
                }
            }

            // double quote strings
            if ($ch == '"') {
                $j = 1;
                while (($i + $j < $slen) && $source[$i + $j] != '"') {
                    if ($source[$i + $j] == '\\' && ($source[$i + $j + 1] == '"' || $source[$i + $j + 1] == '\\')) {
                        $j += 2;
                    } else {
                        $j += 1;
                    }
                }
                $string = substr($source, $i, $j + 1);
                // remove multiline markers:
                $string = str_replace("\\\n", '', $string);
                $result .= $string;
                $i = $i + $j + 1;
                continue;
            }

            // single quote strings
            if ($ch == "'") {
                $j = 1;
                while (($i + $j < $slen) && $source[$i + $j] != "'") {
                    if ($source[$i + $j] == '\\' && ($source[$i + $j + 1] == "'" || $source[$i + $j + 1] == '\\')) {
                        $j += 2;
                    } else {
                        $j += 1;
                    }
                }
                $string = substr($source, $i, $j + 1);
                // remove multiline markers:
                $string = str_replace("\\\n", '', $string);
                $result .= $string;
                $i = $i + $j + 1;
                continue;
            }

            // backtick strings
            if ($ch == "`") {
                $j = 1;
                while (($i + $j < $slen) && $source[$i + $j] != "`") {
                    if ($source[$i + $j] == '\\' && ($source[$i + $j + 1] == "`" || $source[$i + $j + 1] == '\\')) {
                        $j += 2;
                    } else {
                        $j += 1;
                    }
                }
                $string = substr($source, $i, $j + 1);
                // remove multiline markers:
                $string = str_replace("\\\n", '', $string);
                $result .= $string;
                $i = $i + $j + 1;
                continue;
            }

            // whitespaces
            if ($ch == ' ' || $ch == "\r" || $ch == "\n" || $ch == "\t") {
                $lch = substr($result, -1);

                // Only consider deleting whitespace if the signs before and after
                // are not equal and are not an operator which may not follow itself.
                if ($i + 1 < $slen && ((!$lch || $source[$i + 1] == ' ')
                        || $lch != $source[$i + 1]
                        || strpos(self::OPS, $source[$i + 1]) === false)) {
                    // leading spaces
                    if ($i + 1 < $slen && (strpos(self::CHARS, $source[$i + 1]) !== false)) {
                        $i = $i + 1;
                        continue;
                    }
                    // trailing spaces
                    //  if this ch is space AND the last char processed
                    //  is special, then skip the space
                    if ($lch && (strpos(self::CHARS, $lch) !== false)) {
                        $i = $i + 1;
                        continue;
                    }
                }

                // else after all of this convert the "whitespace" to
                // a single space.  It will get appended below
                $ch = ' ';
            }

            // other chars
            $result .= $ch;
            $i = $i + 1;
        }

        return trim($result);
    }
}
