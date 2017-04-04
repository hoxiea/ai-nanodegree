<?php
namespace grx1\dosis;

class UsageLine
{
    const NUM_LINE_ELEMENTS = 8;

    const NDC11_INDEX     = 0;
    const DRUGINFO_INDEX  = 1;
    const QUANTITY_INDEX  = 2;
    const DRUGCLASS_INDEX = 3;
    const DRUGUNIT_INDEX  = 4;
    const DRUGTYPE_INDEX  = 5;

    public static $badDrugClasses = ["2C" => 1, "4C" => 1];
    public static $goodDrugUnits  = ["TAB" => 1, "CAP" => 1, ];
    public static $badDrugTypes   = ["PP" => 1, "EK" => 1, "CANT" => 1, "FR" => 1, ];

    public static $PREPACKS = [
        "00603002632" => "ASPIRIN  81MG  EC  TAB *DNC*",
        "00904615760" => "VITAMIN D3 2,000 UNIT TAB",
        '00904582460' => 'VITAMIN D3 1,000 IU TAB',
        '60505006501' => "OMEPRAZOLE DR 20MG*DNC* CAP",
        '13668010310' => "DONEPEZIL 10 MG TAB",
        "65862006299" => "METOPROLOL TART 25 MG TAB",
        "65862065399" => "MEMANTINE 10 MG TAB",
        "00904198280" => "ACETAMINOPHEN 325",
    ];

    public $druginfo;
    public $ndc11;
    public $quantity;
    public $drugunit;   // TAB or CAP

    public function __construct($line)
    {
        $elements = line_to_array($line);

        // We only make a UsageLine if it's valid, as determined by DrugClass, DrugUnit, and DrugType
        $dc = $elements[self::DRUGCLASS_INDEX];
        $du = $elements[self::DRUGUNIT_INDEX];
        $dt = $elements[self::DRUGTYPE_INDEX];
        if (self::drugclassOK($dc) && self::drugunitOK($du) && self::drugtypeOK($dt)) {
            $this->druginfo = $elements[self::DRUGINFO_INDEX];
            $this->ndc11    = self::processNDC11($elements[self::NDC11_INDEX]);
            $this->quantity = self::processQuantity($elements[self::QUANTITY_INDEX]);
            $this->drugunit = $du;
        } else {
            throw new \Exception("Filtered: {$elements[self::DRUGINFO_INDEX]}");
        }
    }

    private static function drugclassOK($dc)
    {
        return !isset(self::$badDrugClasses[$dc]);
    }

    private static function drugunitOK($du)
    {
        return isset(self::$goodDrugUnits[$du]);
    }

    private static function drugtypeOK($dt)
    {
        return !isset(self::$badDrugTypes[$dt]);
    }

    private static function processNDC11($s)
    {
        if (strlen($s) == 11) {
            return $s;
        } else if (5 <= strlen($s) && strlen($s) < 11)
            // Leading zeros sometimes get cut off by Excel
            return str_pad($s, 11, "0", STR_PAD_LEFT);
        else {
            throw new \Exception("NDC11 Error");
        }
    }

    private static function processQuantity($s)
    {
        $s = str_replace(",", "", $s); // no commas in quantities
        return floatval($s);
    }

    private function quantityDivisBy28()
    {
        return intval($this->quantity) % 28 === 0;
    }

    public function shouldBePrepacked()
    {
        return $this->quantityDivisBy28() && isset(self::$PREPACKS[$this->ndc11]);
    }
}
