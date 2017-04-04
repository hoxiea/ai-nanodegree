<?php
namespace grx1\dosis;

/*
 * A Usage Drug contains all the information that a Drug does.
 * It also contains information about the quantity used.
 */

class UsageDrug extends Drug
{
    private $quantity = null;          // how many pills/doses this particular instance was prescribed for
    private $usage_rank = null;        // rank in terms of total usage
    private $usage_proportion = null;
    private $usage_cumulative_proportion = null;
    private $dosis_match = null;       // reference to the DosisDrug that this UsageDrug matches

    /*
     * Constructor and Static Creators
     */
    public function __construct($druginfo, $ndc11, $quantity, $drugunit=null)
    {
        parent::__construct($druginfo, $ndc11, $drugunit);
        $this->absorbQuantity($quantity);
    }

    public static function fromUsageLine($ul)
    {
        return new self($ul->druginfo, $ul->ndc11, $ul->quantity, $ul->drugunit);
    }

    /*
     * Getters
     */
    public function getQuantity() {
        return $this->quantity;
    }

    public function getUsageRank() {
        if (!$this->hasUsageRank()) {
            throw new \Exception("Called getUsageRank on a UsageDrug that doesn't have a usage rank!");
        }
        return $this->usage_rank;
    }

    public function getUsageProportion() {
        if (!$this->hasUsageProportion()) {
            throw new \Exception("Called getUsageProportion on a UsageDrug that doesn't have a usage proportion!");
        }
        return $this->usage_proportion;
    }

    public function getUsagePercentage() {
        return $this->getUsageProportion() * 100;
    }

    public function getUsageCumProportion() {
        if (!$this->hasUsageCumProportion()) {
            $msg = "Called getUsageCumProportion on UsageDrug {$this->getRawDruginfos()[0]} that doesn't have a cumulative usage proportion!";
            throw new \Exception($msg);
        }
        return $this->usage_cumulative_proportion;
    }

    public function getUsageCumPercentage() {
        return $this->getUsageCumProportion() * 100;
    }

    /*
     * Checkers (Boolean)
     */
    public function hasQuantity()  {
        return $this->quantity !== null;
    }

    public function hasUsageRank() {
        return $this->usage_rank !== null;
    }

    public function hasUsageProportion() {
        return $this->usage_proportion !== null;
    }

    public function hasUsageCumProportion() {
        return $this->usage_cumulative_proportion !== null;
    }

    public function hasDosisMatch() {
        return $this->dosis_match !== null;
    }

    public function lacksDosisMatch() {
        return !$this->hasDosisMatch();
    }

    public function isMissingFastMover($cutoff) {
        return $this->lacksDosisMatch() && $this->getUsageRank() <= $cutoff;
    }

    public function isIncludedSlowMover($cutoff) {
        return $this->hasDosisMatch() && $this->getUsageRank() > $cutoff;
    }

    /*
     * Setters
     */
    public function absorbQuantity($q)
    {
        // null gets treated as a 0 in arithmetic expressions
        // Want to avoid starting with a null quantity, adding a null quantity, and getting a 0 quantity
        // So we only add if we're seeing something other than null

        if ($q !== null) {
            $this->quantity += $q;
        }
        return true;
    }

    public function absorbUsageRank($rank)
    {
        $this->usage_rank = $rank;
        return true;
    }

    /*
     * Try to absorb DosisDrug $dd to be $dosis_match for this UsageDrug
     */
    public function absorbDosisMatchNDC11($dd)
    {
        // if (!is_null($this->dosis_match)) {
        //     $msg = "Tried to set Dosis match for $this, but already have one! ";
        //     $msg .= "Current match: {$this->dosis_match}. ";
        //     $msg .= "New match: $dd.";
        //     throw new \Exception($msg);
        // }
        //
        $dosisNDC11s = $dd->getNDC11s();
        assert(count($dosisNDC11s) == 1);
        $dosisNDC11 = $dosisNDC11s[0];

        if ($this->hasNDC11($dosisNDC11)) {
            $this->dosis_match = $dd;
            return true;
        }
        return false;
    }

    public function addPropotion($totalQuantity)
    {
        $this->usage_proportion = $this->getQuantity() / $totalQuantity;
    }

    public function addCumProportion($priorProportion)
    {
        $this->usage_cumulative_proportion = $this->getUsageProportion() + $priorProportion;
    }


    /*
     * Mergers
     */
    public function absorbUsageLineNDC11($ul) {
        parent::absorbUsageLineNDC11($ul);
        $this->absorbQuantity($ul->quantity);
    }

    public function absorbSimilarUsageDrug($that) {
        parent::absorbSimilarDrug($that);
        $this->absorbQuantity($that->getQuantity());
        return true;
    }

    /*
     * Sorters
     */
    // function that can be fed into `usort` to sort in descending quantity order
    public static function cmpUsage($d1, $d2)
    {
        $q1 = $d1->getQuantity();
        $q2 = $d2->getQuantity();
        if ($q1 == $q2) {
            return 0;
        }
        return ($q1 < $q2) ? 1 : -1;
    }

    /*
     * String Formatting
     */
    private static function sprintfFormat($prependRank = false, $appendPercentages = false)
    {
        // Start with string formats for all possible output slots
        $sections = array(
            "%4s",    // rank
            "%12s",   // NDC11, plus maybe an asterisk if there's more than one
            "%-34s",  // dirty drug name, plus maybe an asterisk if there's more than one
            "%6s",    // usage
            "%6s",    // usage percentage
            "%6s",    // cumulative usage percentage
        );

        // Remove those that aren't wanted
        if (!$prependRank) {
            array_shift($sections);  // get rid of first element
        }

        if (!$appendPercentages) {
            array_pop($sections);
            array_pop($sections);
        }

        return implode(" | ", $sections) . nl();
    }

    /*
     * If the UD has a usage_rank:      Rank    NDC11     Drug Name         Quantity
     * If not:                          NDC11   Drug Name  Quantity
     */
    public function stringForFile($useRaw = false, $usePercentages = false)
    {
        // Druginfo
        if ($useRaw) {
            $druginfo = $this->getRawDruginfos()[0];
        } else {
            $druginfo = $this->getCleanedDruginfos()[0];
        }

        // if ($this->hasDrugunit()) {
        //     $druginfo .= " ({$this->getDrugunit()})";
        // }

        // Other fields
        $ndc11 = (count($this->getNDC11s()) > 1 ? "#" : "") . $this->getNDC11s()[0];
        $quantity_rounded = round($this->getQuantity());
        $content = array($ndc11, $druginfo, $quantity_rounded);

        // Add rank if this UD has one
        if ($this->hasUsageRank()) {
            array_unshift($content, $this->getUsageRank());
        }

        // Add percentages if desired
        if ($usePercentages) {
            array_push($content, round($this->getUsagePercentage(), 2));
            array_push($content, round($this->getUsageCumPercentage(), 2));
        }

        return sprintf(self::sprintfFormat($this->hasUsageRank(), $usePercentages), ...$content);
    }

    /*
     * Note that fileHeaderUD isn't a static method!
     * If the UD has a usage_rank, then a column for Rank will be included
     * If not, then no such column will appear
     */
    public function fileHeaderUD($dividing_line = "-", $appendPercentages = false)
    {
        // Define all possible column headings
        $colnames = array( "Rank", "NDC11", "Drug Name", "Quant.", "%", "Cuml %");

        $prependRank = $this->hasUsageRank();
        if (!$prependRank) {
            array_shift($colnames);
        }

        if (!$appendPercentages) {
            array_pop($colnames);
            array_pop($colnames);
        }

        // ellipses = pass array as individ arguments
        $header = sprintf(self::sprintfFormat($prependRank, $appendPercentages), ...$colnames);

        if ($dividing_line !== null) {
            $header .= str_repeat($dividing_line, strlen($header)) . nl();
        }
        return $header;
    }

    public function __toString()
    {
        return "UD: {$this->getRawDruginfos()[0]} ({$this->getNDC11s()[0]})";
    }
}
