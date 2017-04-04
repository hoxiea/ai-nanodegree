<?php
namespace grx1\dosis;

/*
 * This class models a drug, i.e. a pharmaceutical compound.
 *
 * As the constructor suggests, there are three main parts to a Drug:
 * 1. The 'druginfo' String: a drug name, modifiers (XL, GEL, etc.), and a strength
 * 2. The NDC11. A length-11 String of digits that identifies a certain drug.
 * 3. A usage count, OPTIONAL!
 *
 * Typically, we'll be working with a bunch of Drugs at one time, as we process
 * a Dosis or usage file. We always build the Drugs dynamically, based on what
 * we see in the files; there's no database of knows Drugs.
 *
 * And our job of modeling drugs is made trickier by the fact that, in a file,
 * you can have:
 * - Entries with slightly different names that refer to the same Drug
 *      eg: POTASSIUM CL 10MEQ ER*DNC*, POTASSIUM CL ER10MEQ *DNC*
 * - Totally different NDC9s, if two manufctrers both make a generic version of the drug
 *
 * To solve this problem, we merge the two Drugs together into a single Drug
 * that contains all the info.
 *
 * There are two ways to merge:
 * 1. Based on an NDC exact match. This is a surefire thing: if two Drugs have
 *    the same NDC11 or NDC9, then they're definitely referring the same drug.
 * 2. Based on name and strength.
 */

class Drug
{
    public static $validDrugunits = ["TAB", "CAP", null]; // from UsageLine, plus null
    const PROBLEM_STRING_CODE = "S";
    const PROBLEM_REGEX_CODE  = "R";

    // Constructor Inputs
    // raw_druginfos, ndc11s, ndc9s, and cleaned_druginfos have type String => true,
    // for faster membership lookups (handled by getter and checker methods below)
    private $raw_druginfos = [];
    private $ndc11s = [];
    private $drugunit = null;   // Tablet or Capsule?

    // derived properties
    private $ndc9s = [];
    private $cleaned_druginfos = [];
    private $names = [];              // drug NAMEs only
    private $strengths = [];          // drug STRENGTHs only

    // properties that can be added manually later
    private $problemDrugStatus = "";

    /*
     * CONSTRUCTOR AND STATIC CREATORS
     */
    public function __construct($druginfo, $ndc11, $drugunit = null)
    {
        $this->absorbRawDruginfo($druginfo);  // updates raw_druginfos, cleaned_druginfos, names, strengths
        $this->absorbNDC11($ndc11);   // updates ndc11s and ndc9s
        $this->absorbDrugunit($drugunit);
    }

    public static function fromUsageLine($ul)
    {
        return new self($ul->druginfo, $ul->ndc11, $ul->drugunit);
    }

    /*
     * CHANGERS: Public functions that call the various gatekeepers
     */
    public function absorbNDC11($ndc11)
    {
        if (strlen($ndc11) !== 11) {
            throw new \Exception("NDC11 $ndc11 isn't 11 characters long");
        }
        $ndc9 = substr($ndc11, 0, 9);
        return $this->addNDC11($ndc11) && $this->addNDC9($ndc9);
    }

    public function absorbRawDruginfo($rdi)
    {
        // As computationally efficient as possible
        if ($this->addRawDruginfo($rdi)) {          // if we were able to add it, it must have been new...
            $cleaned = \grx1\DrugInfoParser::cleanDrugInfo($rdi);  // so we clean it up
            if ($this->addCleanedDrugInfo($cleaned)) {        // if we were able to add it, it must have been new...*
                list($n, $s) = \grx1\DrugInfoParser::parseDrugInfo($cleaned, true);   // so we take the time to parse it
                $this->addName($n);
                $this->addStrength($s);
            }
        }
    //* We bother to check because different dirty druginfos can turn into
    //the same cleaned druginfo string.  So having seen a new dirtydruginfo
    //isn't a guarantee that the cleaned version will also be new
    }

    public function absorbDrugunit($du) {
        $this->setDrugunit($du);
    }

    // Return whether or not the absorb succeeded
    public function absorbProblemDrugString($string) {
        if (!$this->matchedProblemString()) {
           if ($this->rawDruginfoContainsString($string)) {
                $this->problemDrugStatus .= Drug::PROBLEM_STRING_CODE;
                return true;
            }
        }
        return false;
    }

    // Return whether or not the absorb succeeded
    public function absorbProblemDrugRegex($regex) {
        if (!$this->matchedProblemRegex() && $this->rawDruginfoMatchesRegex($regex)) {
            $this->problemDrugStatus .= Drug::PROBLEM_REGEX_CODE;
            return true;
        }
        return false;
    }

    /*
     * GETTERS
     */
    public function getRawDruginfos()     { return array_keys($this->raw_druginfos); }
    public function getCleanedDruginfos() { return array_keys($this->cleaned_druginfos); }
    public function getNDC11s()           { return array_keys($this->ndc11s); }
    public function getNDC9s()            { return array_keys($this->ndc9s); }
    public function getNames()            { return array_keys($this->names); }
    public function getStrengths()        { return array_keys($this->strengths); }
    public function getDrugunit()         { return $this->drugunit; }
    public function getProblemDrugStatus() { return $this->problemDrugStatus; }

    /**
     * Return the first $prefixLength letters of the first cleaned druginfo string
     * Useful for quickly determining if two different Drugs are NOT the same
     */
    public function getPrefix($prefixLength = 5)
    {
        return substr($this->getCleanedDruginfos()[0], 0, $prefixLength);
    }


    //////////////
    // CHECKERS //
    //////////////
    public function hasNDC9($ndc9) { return isset($this->ndc9s[$ndc9]); }
    public function hasNDC11($ndc11) { return isset($this->ndc11s[$ndc11]); }

    public function hasDrugunit()  { return $this->drugunit !== null; }
    public function isProblemDrug() { return $this->problemDrugStatus !== ""; }

    public function matchedProblemString() {
        return strpos($this->problemDrugStatus, self::PROBLEM_STRING_CODE) !== false;
    }

    public function matchedProblemRegex() {
        return strpos($this->problemDrugStatus, self::PROBLEM_REGEX_CODE) !== false;
    }


    // Returns the index of the first match, or -1 if no match found
    private static function findRegexMatch($array, $regex)
    {
        foreach ($array as $index => $element) {
            if (preg_match($regex, $element)) {
                return $index;
            }
        }
        return -1;
    }

    // Returns the index of the first match, or -1 if no match found
    private static function findStringMatch($array, $string)
    {
        foreach ($array as $index => $element) {
            if (strpos($element, $string) !== false) {
                return $index;
            }
        }
        return -1;
    }

    public function rawDruginfoMatchesRegex($regex)  {
        $match_index = Drug::findRegexMatch($this->getRawDruginfos(), $regex);
        return ($match_index >= 0) ? true : false;
    }

    public function rawDruginfoContainsString($string) {
        $match_index = Drug::findStringMatch($this->getRawDruginfos(), $string);
        return ($match_index >= 0) ? true : false;
    }

    /*
     * SETTERS: Directly modify the class variables
     *
     * These are mostly private because you usually use a merge method instead
     * of manually adding info
     *
     * The exception is drugunit: Dosis Drugs don't have a drugunit, but when
     * we sync them with the Usage Drugs, we grab the Usage Drug's drugunit.
     * This makes it easier to merge similar Dosis Drugs.
     *
     * NOTE: These all return a boolean, indicating success of the operation.
     */
    private function addRawDruginfo($di)
    {
        if (!isset($this->raw_druginfos[$di])) {
            $this->raw_druginfos[$di] = true;
            return true;
        }
        return false;
    }

    private function addCleanedDrugInfo($di)
    {
        if (!isset($this->cleaned_druginfos[$di])) {
            $this->cleaned_druginfos[$di] = true;
            return true;
        }
        return false;
    }

    private function addNDC11($ndc11)
    {
        if (!strlen($ndc11) == 11) {
            throw new \Exception("Drug.addNDC11");
        }

        if (!isset($this->ndc11s[$ndc11])) {
            $this->ndc11s[$ndc11] = true;
            return true;
        }
        return false;
    }

    private function addNDC9($ndc9)
    {
        if (!strlen($ndc9) == 9) {
            throw new \Exception("Drug.addNDC9");
        }

        if (!isset($this->ndc9s[$ndc9])) {
            $this->ndc9s[$ndc9] = true;
            return true;
        }
        return false;
    }

    private function addName($n)
    {
        if (!isset($this->names[$n])) {
            $this->names[$n] = true;
            return true;
        }
        return false;
    }

    private function addStrength($s)
    {
        if (!isset($this->strengths[$s])) {
            $this->strengths[$s] = true;
            return true;
        }
        return false;
    }

    // This only lets you change a null $drugunit to an approved $drugunit
    // You can also set the drugunit to what it already is; this has no effect
    // Cases: this null, that null -> do nothing
    //        this null, that real -> set to real
    //        this real, that null -> exception
    //        this real, that real -> do nothing if they match, throw if they don't
    private function setDrugunit($du)
    {
        if (!in_array($du, self::$validDrugunits)) {
            throw new \Exception("Invalid Drugunit provided");
        }

        if ($this->drugunit === null && $du !== null) {
            $this->drugunit = $du;
            return true;
        } elseif ($this->drugunit !== null && $du === null) {
            throw new \Exception('Tried to null out a Drug\'s drugunit');
        } elseif ($this->drugunit !== null && $du !== null && $this->drugunit != $du) {
            $msg = 'Tried to change a drug unit from '.$this->getDrugunit().' to '.$du;
            $msg .= "\n" . $this;
            throw new \Exception($msg);
        }
        return false;
    }

    // MERGERS
    public function absorbUsageLineNDC11($ul)
    {
        // Make sure the NDC11 matches
        if (!$this->hasNDC11($ul->ndc11)) {
            throw new \Exception("absorbUsageLineNDC11: NDC11 mismatch");
        }

        // Absorb! Same as the Drug constructor, really
        $this->absorbRawDruginfo($ul->druginfo);  // updates raw_druginfos, cleaned_druginfos, names, strengths
        $this->absorbNDC11($ul->ndc11);           // updates ndc11s and ndc9s
        $this->absorbDrugunit($ul->drugunit);
    }

    /*
     * absorbSimilarDrug
     */
    public function absorbSimilarDrug($that)
    {
        if ($that === null) {
            return false;
        }

        if (!$this->couldBeSameDrugAs($that)) {
            return false;
        }

        // The + operator for arrays takes a union based on array KEYS
        // This is perfect for our arrays, since the keys are where the information is stored:
        $this->raw_druginfos     += $that->raw_druginfos;
        $this->cleaned_druginfos += $that->cleaned_druginfos;
        $this->ndc11s            += $that->ndc11s;
        $this->ndc9s             += $that->ndc9s;
        $this->names             += $that->names;
        $this->strengths         += $that->strengths;
        $this->absorbDrugunit($that->getDrugunit());

        return true;
    }

    /*
     * This is the interesting drug comparison method. (Comparing NDC11s is easy.)
     * Given another drug $that, it tries to figure out if $that could be the same Drug as $this,
     * even if the NDC9s aren't the same.
     */
    public function couldBeSameDrugAs($that)
    {
        // Case 0: Overlapping NDC11s
        if (array_intersect($this->getNDC11s(), $that->getNDC11s())) {  // empty array is false
            return true;
        }

        // Easy, IMPORTANT check: non-null drugunits that don't match
        if ($this->drugunit !== null && $that->drugunit !== null && $this->drugunit !== $that->drugunit) {
            return false;
        }

        // Case 1: Overlapping NDC9s, plus similar name and drugunit if it exists
        if (array_intersect($this->getNDC9s(), $that->getNDC9s()) && $this->getPrefix() == $that->getPrefix()) {
            return true;
        }

        // Case 2: Generics. Totally different NDCs, but lots of other similarities

        // Easy check: do the first few letters of the cleaned drug name prefixes overlap?
        // If so, that's not good enough to say yes.
        // But if not, that IS good enough to say no, since I've never seen druginfos for the same Drug that
        // differed within the first few characters; usually it's little modifiers towards the ends
        $prefixLength = 4;
        if ($this->getPrefix($prefixLength) !== $that->getPrefix($prefixLength)) {
            return false;
        }

        // Get the strengths of both, and make sure:
        // - each drug has one strength
        // - the numbers are the same
        $strengths1 = array_unique(array_map(array('self', 'simplifyStrength'), $this->getStrengths()));
        $strengths2 = array_unique(array_map(array('self', 'simplifyStrength'), $that->getStrengths()));
        $s1 = $strengths1[0];
        $s2 = $strengths2[0];
        if ($s1 != $s2) {
            return false;
        }

        // Are the letters in the (first cleaned) druginfos the same, after getting rid of spaces?
        // This is nice if the drug is ABC 5MG ER versus ABC ER 5MG
        $d1letters = str_split(str_replace(" ", "", $this->getCleanedDruginfos()[0]));
        $d2letters = str_split(str_replace(" ", "", $that->getCleanedDruginfos()[0]));
        if (array_count_values($d1letters) == array_count_values($d2letters)) {
            return true;
        };

        return false;
    }

    public static function simplifyStrength($s)
    {
        return trim(preg_replace("~[^\d\.]~", "", $s));  // blow away anything except a digit or a period
    }

    ////////////////
    // OUTPUTTERS //
    ////////////////
    /*
     * __toString
     * Used mostly for debugging purposes
     */
    public function __toString()
    {
        return $this->getRawDruginfos()[0];
    }

    /*
     * sprintfFormat
     * A Drug just has an NDC11 and a name, basically. So that's all you get.
     */
    private static function sprintfFormat()
    {
        // Start with string formats for all possible output slots
        $sections = array(
            "%12s",   // NDC11, plus maybe an asterisk if there's more than one
            "%-34s",  // drug name, plus maybe an asterisk if there's more than one
        );

        return implode(" | ", $sections) . nl();
    }

    public static function fileHeader($dividing_line = "-")
    {
        $texts = ["NDC11", "Drug Name"];
        $header = sprintf(self::sprintfFormat(), ...$texts);
        if ($dividing_line !== null) {
            $header .= str_repeat($dividing_line, strlen($header) / strlen($dividing_line)) . nl();
        }
        return $header;
    }

    /*
     * The main workhorse when writing to file
     */
    public function stringForFile($useRaw = false)
    {
        // Start with info that always appears
        $ndc11 = (count($this->getNDC11s()) > 1 ? "#" : "") . $this->getNDC11s()[0];

        $druginfo = $useRaw ? $this->getRawDruginfos()[0] : $this->getCleanedDruginfos()[0];
        // if ($this->hasDrugunit()) {
        //     $druginfo .= " ({$this->getDrugunit()})";
        // }

        $content = array($ndc11, $druginfo);
        return sprintf(self::sprintfFormat(), ...$content);
    }

    /////////////
    // SORTERS //
    /////////////
    /*
     * cmpName can be fed into usort to sort Drugs into alphabetical order
     */
    public static function cmpName($d1, $d2)
    {
        $n1 = $d1->getCleanedDruginfos()[0];
        $n2 = $d2->getCleanedDruginfos()[0];
        if ($n1 == $n2) {
            return 0;
        }
        return ($n1 > $n2) ? 1 : -1;
    }
}
