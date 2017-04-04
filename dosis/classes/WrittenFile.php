<?php
namespace grx1\dosis;

/*
 * This class encapsulates everything about an Output File that gets written.
 * This makes it super easy to display the results of the Dosis Analysis.
 */

class WrittenFile
{
    public $filepath = "";
    public $linktext = "";
    public $description = "";
    public $specialInstructions = "";

    public function __construct($filepath, $linktext, $description, $instructions = "", $descrClasses=null)
    {
        $this->filepath = $filepath;
        $this->linktext = $linktext;
        $this->description = $description;
        $this->specialInstructions = $instructions;
        if (is_null($descrClasses)) {
            $this->descrClasses = [];
        } else {
            $this->descrClasses = $descrClasses;
        }
    }

    public function descrClassesString()
    {
        return implode(" ", $this->descrClasses);
    }

    public function html_link()
    {
        return "<a href='{$this->filepath}'>{$this->linktext}</a>";
    }

    public function html_table_row()
    {
        $table_row = "<tr class='{$this->descrClassesString()}'>" . nl();
        $table_row .= "  <td>{$this->html_link()}</td>" . nl();
        $table_row .= "  <td>{$this->description}</td>" . nl();
        $table_row .= "  <td>{$this->specialInstructions}</td>" . nl();
        $table_row .= "</tr>" . nl();
        return $table_row;
    }
}
