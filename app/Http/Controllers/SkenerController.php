<?php namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Log;

class SkenerController
{
    const DELIM = ['.',',',';',':','[',']','=','#','(',')','/','*','-','+','%','^'];
    const DELIM2 = ['..',':='];
    const KEYWORDS = ['constructor', 'const', 'array', 'of', 'Char', 'var', 'Assign', 'Begin', 'Init', 'or', 'for', 'do', 'New', 'Insert', 'end', 'Integer', 'Pview', 'TRect', 'inherited',  'and', 'not'];
    const CODE = " constructor TCalculator.Init;
    const KeyChar: array[0..19] of Char = 'C'#27'%'#241'789/456*123-0.=+';
    var I: Integer; P: PView; R: TRect;
    Begin
    R.Assign(5, 3, 29, 18); inherited Init(R, 'Calculator');
    Options := Options or ofFirstClick;
    for I := 0 to 19 do P:=New(PButton, cmCalcButton);
    P^.Options := P^.Options and not ofSelectable;
    Insert(P);
    R.Assign(3, 2, 21, 3);
    Insert(New(PCalcDisplay, Init(R)));
    End;";

    protected $tokens = [];
    protected $lines = 1;
    protected $position = 1;

    public function skener()
    {
        $code = str_split(static::CODE);


        $data['tokens'] = array_filter($this->tokenize($code), function($token) {

            return $token['type'] !== 'whitespace';
        });

        return view('skener', $data);
    }

    protected function tokenize($chars)
    {
        Log::info(head($chars) . " | " . count($chars));
        switch (true)
        {
            // TO DO check if chars is empty
            case count($chars) === 0:
                return $this->tokens;

            case $this->isWhitespace($chars):
                $chars = $this->parseWhitespace($chars);
                break;

            case $this->isLetter($chars):
                $chars = $this->parseLetters($chars);
                break;

            case $this->isDelim2($chars):
                $chars = $this->parseDelim2($chars);
                break;

            case $this->isDelim($chars):
                $chars = $this->parseDelim($chars);
                break;

            case $this->isNumber($chars):
                $chars = $this->parseNumber($chars);
                break;

            case $this->isComment($chars):
                $chars = $this->parseComment($chars);
                break;

            default:
                $this->parseException($chars);

        }

        return $this->tokenize($chars);
    }

    protected function foundToken($type, $value)
    {
        $this->tokens[] = ['type' => $type, 'value' => $value];
    }

    protected function charsOffset($chars, $offSet = 1)
    {
        $this->position += $offSet;
        return array_slice($chars, $offSet);
    }

    protected function splitWhile($chars, $callback)
    {
        $ok = [];
        $i = 0;
        do
        {
            $ok[] = $chars[$i];
            $i++;

        } while($callback($chars, $i));

        $rest = $this->charsOffset($chars, $i);

        return [$ok, $rest];
    }

    protected function parseWhitespace($chars)
    {
        $responseChars = $this->charsOffset($chars);

        $char = $chars[0];
        $newLines = ["\r\n", "\n", "\r"];
        if(in_array($char, $newLines))
        {
            $this->lines++;
            $this->position = 1;
        }

        $this->foundToken("whitespace", $char);
        return $responseChars;
    }

    protected function parseLetters($chars)
    {
        $callback = function($chars, $index)
        {
            return $this->isLetter($chars, $index);
        };

        list($letters, $chars) = $this->splitWhile($chars, $callback);

        $letters = join("", $letters);

        if(in_array($letters, static::KEYWORDS))
        {
            $this->foundToken("keyword", $letters);
        }
        else
        {
            $this->foundToken("id", $letters);
        }

        return $chars;
    }

    protected function parseDelim($chars)
    {
        $this->foundToken("delim", $chars[0]);

        return $this->charsOffset($chars);
    }

    protected function parseDelim2($chars)
    {
        $delim2 = $chars[0] . $chars[1];
        $this->foundToken("delim2", $delim2);

        return $this->charsOffset($chars, 2);
    }

    protected function parseNumber($chars)
    {
        $callback = function($chars, $index)
        {
            return $this->isNumber($chars, $index);
        };

        list($numbers, $chars) = $this->splitWhile($chars, $callback);

        $numbers = join("", $numbers);

        $this->foundToken("literal", $numbers);

        return $chars;
    }

    protected function parseComment($chars)
    {
        $callback = function($chars, $index)
        {
            return !$this->isComment($chars, $index);
        };

        list($comment, $chars) = $this->splitWhile($chars, $callback);

        $comment[] = array_shift($chars);

        $comment = join("", $comment);

        $this->foundToken("comment", $comment);

        return $chars;
    }

    protected function parseException($chars)
    {
        $lastToken = end($this->tokens);
        $typo = "";

        if($lastToken['type'] !== 'whitespace')
        {
            $typo = $lastToken['value'];
        }

        $callback = function($chars, $index)
        {
            return !$this->isWhitespace($chars, $index);
        };

        $typo = $typo . join("", $this->splitWhile($chars, $callback)[0]);

        $didYouMean = $this->findMeaning($typo);
        throw new LexerException($chars[0], $didYouMean, ceil($this->lines / 2), $this->position);
    }

    protected function isWhitespace($chars, $index = 0)
    {
        $whiteSpaces = [" ", "\r\n", "\t", "\n", "\r"];
        return in_array($chars[$index], $whiteSpaces);
    }

    protected function isLetter($chars, $index = 0)
    {
        return ctype_alpha($chars[$index]);
    }

    protected function isDelim2($chars, $index = 0)
    {
        if(count($chars) < 2)
        {
            return false;
        }

        $firstTwo = $chars[$index] . $chars[$index + 1];
        return in_array($firstTwo, static::DELIM2);

    }

    protected function isDelim($chars, $index = 0)
    {
        return in_array($chars[$index], static::DELIM);
    }

    protected function isNumber($chars, $index = 0)
    {
        return ctype_digit($chars[$index]);
    }

    protected function isComment($chars, $index = 0)
    {
        return $chars[$index] === "'";
    }

    protected function findMeaning($typo)
    {
        $foundSimilar = "";
        $index = 100;
        $delimsKeywords = array_merge(static::DELIM, static::DELIM2, static::KEYWORDS);

        foreach($delimsKeywords as $delkey)
        {
            $lastIndex = levenshtein($typo, $delkey);

            if($lastIndex <= $index)
            {
                $index = $lastIndex;
                $foundSimilar = $delkey;
            }
        }

        return $foundSimilar;
    }
}

class LexerException extends \Exception
{
    public function __construct($char, $didYouMean, $line, $position)
    {
        parent::__construct("found unexpected character: [$char]. At: [$line]:[$position], Did you mean: [$didYouMean] ?");
    }
}