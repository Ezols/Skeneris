<?php namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class SkenerController
{
    const DELIM = ['.',';',':','[',']','=','#','(',')','/','*','-','+','%','^'];
    const DELIM2 = ['..',':='];
    const KEYWORDS = ['constructor', 'const', 'array', 'of', 'Char', 'var', 'Assign', 'Begin', 'Init', 'or', 'for', 'do', 'New', 'Insert', 'end', 'Integer', 'Pview', 'TRect', 'inherited',  'and', 'not']; 
    const CODE = "constructor TCalculator.Init;
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
    End; ";
    
        const FAKE_CODE = ".";
    protected $multicharacters = [];

    public function __construct()
    {
        $this->multicharacters = array_merge(static::DELIM2, static::KEYWORDS);
    }

    // static::DELIM;
    public function skener()
    {
        //echo(implode(" | ", static::DELIM));

        // converted CODE to array
        $response = $this->codeParser(static::CODE);

        echo "<pre>";
        print_r($response);
        echo "</pre>";
    }

    protected function codeParser($code)
    {
        $code = str_split($code);
        $inProgress = "";
        $response = [];

        foreach($code as $char)
        {
            if($this->isWhitespaceCharacter($char))
            {
                // Parbaudit in progress
                continue;
            }

            
            $isMatch = $this->isDelimOrKeyword($char);
            $inProgress = $inProgress . $char;

            if($this->maybeDelimOrKeyword($inProgress))
            {
                continue;
            }

            $isMatch = $this->isDelimOrKeyword($inProgress);

            if($isMatch)
            {
                $response[] = ['symbol' => $inProgress, 'type' => $isMatch];
                $inProgress = "";
            }

            // if($this->isDelim2($inProgress)) {
            //     // TODO": its delim2
            //     $response[] = ['symbol' => $char, 'type' => 'delim2'];
            //     continue;
            // }

            // if($this->canBeDelim2($inProgress)) 
            // {
            //     continue;
            // }

            // if($this->isDelim($char))
            // {
            //     $response[] = ['symbol' => $char, 'type' => 'delim'];
            // }
            
        }
        return $response;
    }

    protected function isDelim($char)
    {
        return in_array($char, static::DELIM);
    }

    protected function isDelim2($char)
    {
        return in_array($char, static::DELIM2);
    }

    protected function canBeDelim2($char)
    {
        foreach(static::DELIM2 as $delim2)    
        {
            $pos = strpos($delim2, $char);
            if($pos === 0)
            {
                return true;
            }

        }
        return false;
    }

    protected function maybeDelimOrKeyword($inProgress): bool
    {
        foreach($this->multicharacters as $keywordDelim2)
        {
            $isStartingWith = strpos($keywordDelim2, $inProgress) === 0;
            $isEqual = $inProgress == $keywordDelim2;

            if($isStartingWith && !$isEqual)
            {
                return true;
            }
        }
        return false;
    }

    protected function isDelimOrKeyword($inProgress)
    {
        if(in_array($inProgress, static::DELIM))
        {
            return "DELIM";
        }

        if(in_array($inProgress, static::DELIM2))
        {
            return "DELIM2";
        }

        if(in_array($inProgress, static::KEYWORDS))
        {
            return "KEYWORD";
        }

        return false;
    }

    protected function isWhitespaceCharacter($code)
    {
        return $code === " ";
    }
}