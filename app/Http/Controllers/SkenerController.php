<?php namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class SkenerController
{
    const DELIM = ['.',';',':','[',']','=','#','(',')','/','*','-','+','%','^'];
    const DELIM2 = ['..',':='];
    const KEYWORDS = ['constructor', 'const', 'array', 'of', 'Char', 'var', 'Assign', 'Begin', 'Init', 'or', 'for', 'do', 'New', 'Insert', 'end', 'Integer', 'Pview', 'TRect', 'inherited',  'and', 'not']; 
    const CODE = ".constructor TCalculator.Init;
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


    // static::DELIM;
    public function skener()
    {
        //echo(implode(" | ", static::DELIM));

        // converted CODE to array
        $response = $this->codeParser(static::FAKE_CODE);

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
            $inProgress = $inProgress . $char;
            
            if($this->isDelim2($inProgress)) {
                // TODO": its delim2
                $response[] = ['symbol' => $char, 'type' => 'delim2'];
                continue;
            }

            if($this->canBeDelim2($inProgress)) 
            {
                continue;
            }

            if($this->isDelim($char))
            {
                $response[] = ['symbol' => $char, 'type' => 'delim'];
            }
            
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
}