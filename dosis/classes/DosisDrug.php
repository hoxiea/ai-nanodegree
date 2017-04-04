<?php
namespace grx1\dosis;

class DosisDrug extends Drug
{

    private $usage_match = null;

    public function hasUsageMatch() {
        return $this->usage_match !== null;
    }

    public function lacksUsageMatch() {
        return !$this->hasUsageMatch();
    }

    public function getUsageMatch() {
        if (!$this->hasUsageMatch()) {
            $msg  = "Tried to get usage match for DD lacking one; ";
            $msg .= "check with hasUsageMatch() first";
            throw new \Exception($msg);
        }
        return $this->usage_match;
    }

    public function absorbUsageMatch($ud) {
        if ($this->couldBeSameDrugAs($ud)) {
            $this->usage_match = $ud;
            return true;
        }
        return false;
    }

    public function __toString()
    {
        return "DD: {$this->getRawDruginfos()[0]} ({$this->getNDC11s()[0]})";
    }

}

