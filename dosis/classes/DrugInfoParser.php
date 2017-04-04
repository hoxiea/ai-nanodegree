<?php
namespace grx1;

/*
 * Drugs often have strings that describe the drug and quantity
 * I call these "druginfo" strings.
 *
 * This class contains a bunch of methods that parse a druginfo string into:
 * - Drug name
 * - Drug strength
 */

class DrugInfoParser
{
    public static function cleanDrugInfo($s)
    {
        $s = strtoupper($s);   // captialize everything

        $replacements = array(
            "/,/"                    => "",  // get rid of commas
            "~\*\s*D?N?C?\s*\*?~"    => "",  // as much of *DNC*, anywhere, maybe with whitespace on either side
            "~[^A-Z]CA?P?S?U?L?E?$~" => "",  // as much of CAPSULE, at the end
            "~[^A-Z]TA?B?L?E?T?$~"   => "",  // as much of TABLET, at the end
            "~HCL~"                  => "",  // HCL is an inert base for drugs; doesn't affect anything
            "~R(\d)~"                => "R $1",  // FERROUS SULF ER140MG (UD) and AMLODIPINE-VALSAR5-320 MG
            "~([A-Z])(\d+ ?MC?G)~"   => "$1 $2",  // mostly a fix for PREVACID30MG SOLTAB (ORIG)
            "/\s+/"                  => " ", // condense multi-spaces into one space
        );

        foreach ($replacements as $pattern => $repl) {
            $s = trim(preg_replace($pattern, $repl, $s));
        }
        return $s;
    }

    /*
     * Inputs:
     * - $s: String = a druginfo string, clean or unclean
     *
     * Output:
     * - [$name: String, $strength: String] = the name and strength components of $s
     */
    public static function parseDrugInfo($s, $already_cleaned = false)
    {
        if (!$already_cleaned) {
            $s = self::cleanDrugInfo($s);
        }
        $matches = [];  // used for regex matches

        if (!preg_match('/\d+/', $s)) {
            // No digits. e.g. MULTIVITAMIN ONE-DAILY or THERAPEUTIC-M TAB
            return [$s, "1"];
        } elseif (preg_match("~\+~", $s)) {
            // Has a plus sign in it. These are either combinations (e.g. OYST CAL 500+D200IU)
            // or pills for certain age ranges (e.g. OCUVITE SOFTGEL ADULT 50+).
            return [$s, "1"];

        // So there's at least one digit, which suggests drugname + strength
        } elseif (preg_match("~^(\S*) (\S*)$~", $s, $matches)) {
            // special case: two chunks of text, separated by a space
            $name = trim($matches[1]);
            $strength = trim($matches[2]);
            return [$name, $strength];
        } elseif (preg_match('~(.*) ([\d\.\-]+ ?\w*)( ?.*)~', $s, $matches)) {
            /*
             * This is the workhorse of parseDrugInfo:
             * (.*) : any words appearing at the beginning, followed by a space
             * ([\d\.\-]+ ?\w*): one or more digits/periods/dashes, maybe a space, and then some letters
             * ( ?.*): maybe a space, and then more modifying words
             * Examples: SIMVASTATIN 20 MG, LISINOPRIL 5 MG, GABAPENTIN 600MG
             */
            $name = trim($matches[1]);
            $strength = trim($matches[2]);
            $modifiers = trim($matches[3]);

            // No spaces in $strength
            $strength = preg_replace("~\s+~", "", $strength);

            // Add $modifiers to the end of $name
            if ($modifiers !== "") {
                $modifiers2 = explode(" ", $modifiers);
                $name = implode(" ", array_merge([$name], $modifiers2));
            }
            return [$name, $strength];
        } else {
            // Didn't match anything? Just returned the unparsed string with quantity "1"
            return [$s, "1"];
        }
    }
}
