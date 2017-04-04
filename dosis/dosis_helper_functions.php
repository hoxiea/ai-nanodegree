<?php
namespace grx1\dosis;

/////////////////////////////
// USAGE FILE VERIFICATION //
/////////////////////////////
/*
 * Main function for checking the Usage File provided by the user:
 * verification, echoing content to the page, dying, etc.
 */
function verify_usage_file($files, $formname)
{
    if (!verify_file_upload($files, $formname, ['txt', 'csv'])) {
        echo_dosis_restart_and_die();
    }

    // Read file directly from tmp directory instead of copying it to a new file first, for security purposes
    if (is_uploaded_file($files[$formname]['tmp_name'])) {
        $usageData = file($files[$formname]['tmp_name'], FILE_IGNORE_NEW_LINES) or die("Couldn't read local file (Usage)");
    } else {
        echo make_p("Something went very wrong with your Usage file upload. Please try again.");
        die;
    }

    if (!looks_like_usagefile($usageData)) {
        print_usagefile_fail();
        die;
    }

    return $usageData;
}

/* Helper functions: Usage file verification */
function looks_like_usagefile($data)
{
    // echo make_p(trim($data[0]));
    // echo make_p(usage_header());
    return trim($data[0]) == usage_header() || trim($data[0]) == str_replace('"', '', usage_header());
}

function usage_header()
{
    $header = '"DG-NDC Nbr","DG-Drug Name 27","RX-Qty Dispensed","DG-Drug Class","DG-Drug Unit",';
    $header .= '"DG-Drug Type","DL-Package Size","PT-Patient Group"';
    return $header;
}

function print_usagefile_fail()
{
    $err = "Sorry, I process Usage Files exported from QS1. Your file doesn't look like that. Please try again.";
    echo make_failure_text($err);

    $info = "If QS1 was recently updated or you think this report should work, ";
    $info .= "please email the input file you're trying to analyze to hoxiea@gmail.com";
    echo make_p($info);
    echo_dosis_restart_and_die();
}

function echo_dosis_restart_and_die()
{
    echo '<a href="./">Restart Dosis Analysis</a>' . PHP_EOL;
    echo "</body>" . nl() . "</html>";
    die;
}


///////////////////////////////////////
// DOSIS CANISTER FILE VERIFICATION //
///////////////////////////////////////
/*
 * Main function for checking the Dosis File provided by the user:
 * verification, echoing content to the page, dying, etc.
 */
function verify_dosis_file($files, $formname)
{
    if (!verify_file_upload($files, $formname, ['txt', 'csv'])) {
        echo_dosis_restart_and_die();
    }

    if (is_uploaded_file($files[$formname]['tmp_name'])) {
        $dosisData = file($files[$formname]['tmp_name'], FILE_IGNORE_NEW_LINES) or die("Couldn't read local file! (Dosis");
    } else {
        echo make_p("Something went very wrong with your Dosis file upload. Please try again.");
        die;
    }

    if (!looks_like_dosisfile($dosisData)) {
        $err = "Sorry, I process Dosis Files. Your file doesn't look like that. Please try again.";
        echo make_failure_text($err);
        echo '<p><a href="./">Restart Dosis Analyzer</a></p>' . PHP_EOL;
        echo "</body>".PHP_EOL."</html>";
        die;
    }

    return $dosisData;
}

/* Dosis Verification Helper Methods */
function looks_like_dosisfile($data)
{
    return trim($data[0]) == dosis_header();
}

function dosis_header()
{
    $header = '"DG-Drug Name 27","DG-Drug Type","IN-Dispensing Unit#","DG-NDC Nbr"';
    return $header;
}



///////////////////////////////////
// PART 1: PROCESSING USAGE FILE //
///////////////////////////////////
/*
 * Input: [String], where each String is a line from the usage file
 * Output: [UsageLine]
 */
function usagedata_to_UsageLines($ud, $verbose = false)
{
    if (looks_like_usagefile($ud)) {
        $ud = drop_elements($ud, 1);
    }

    $usageLines = [];
    $filteredDruginfos = [];
    foreach ($ud as $linestring) {
        try {
            $usageLines[] = new \grx1\dosis\UsageLine($linestring);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Filtered:') !== false) {
                $startOfDruginfo = strpos($msg, ' ') + 1;
                $druginfo = substr($msg, $startOfDruginfo);
                if (!isset($filteredDruginfos[$druginfo])) {
                    $filteredDruginfos[$druginfo] = true;
                }
            } else {
                switch ($msg) {
                    case "NDC11 Error":
                        // echo make_p("NDC11 Error");
                        continue;
                    case "line no good":  // constructor
                        // echo make_p("line no good");
                        continue;
                    case "line_to_array fail":
                        echo make_p("line_to_array, in UsageLine constructor");
                        continue;
                    default:
                        echo make_p($msg);
                        continue;
                }
            }
        }
    }
    return [$usageLines, array_keys($filteredDruginfos)];
}


/*
 * write_filtered_usage_lines
 * Input:
 * - $filteredDruginfos: [String], probably the second returned value from usagedata_to_usagelines
 * - $writtenFiles: [WrittenFile], the array of files written during the run
 * Returns:
 * - The written file, or null if a file wasn't written because there weren't any filtered druginfos
 * Side effects:
 * - Mutates $writtenFiles if a new file is written
 */
function write_filtered_usage_lines($filteredDruginfos, &$wfs)
{
    $wf = null;
    if ($filteredDruginfos) {
        $filepath = $GLOBALS['outputDir'] . 'InitialDruginfosFiltered.txt';

        $header = "Drugs Filtered Based On DrugClass, DrugUnit, and DrugType" . nl() . nl();
        write_array($filepath, $filteredDruginfos, '\grx1\dosis\usage_filtered_drugs_write_array', $header);

        $link = "USAGE: Initially Filtered Drugs";
        $descr = "Usage Drugs are filtered based on DrugClass, DrugUnit, and DrugType; these are the Drugs that were removed.";
        $instr = "You probably won't need this info every time you run an analysis. But if a drug seems to be in your Usage file and not-appearing in the analysis, it might be in here.";
        $wf = new WrittenFile($filepath, $link, $descr, $instr);
        $wfs[] = $wf;
    }
    return $wf;
}


function echo_step1b_results($usageData, $usageLines, $wf)
{
    echo "<h2>Step {$GLOBALS['STEP']}: Usage Data, Initial Pass</h2>";
    $GLOBALS['STEP']++;
    echo make_p('Your usage file contained '.count($usageData).' lines in it.');
    echo "<ul>";
    // echo "<li>Removing lines with DrugClass in: (".implode(", ", array_keys(UsageLine::$badDrugClasses)).")</li>";
    echo "<li>Removing lines with DrugClass indicating a Controlled Substance</li>";
    echo "<li>Keeping only lines with DrugUnit in: (".implode(", ", array_keys(UsageLine::$goodDrugUnits)).")</li>";
    echo "<li>Removing lines with DrugType in: (".implode(", ", array_keys(UsageLine::$badDrugTypes)).")</li>";
    echo "</ul>";

    if (!is_null($wf)) {
        echo make_p("Usage drugs filtered by these rules can be viewed here: {$wf->html_link()}");
    }
    echo make_p('After applying these rules, '.count($usageLines). ' lines remain.');
}


function echo_prepack_filter_results($prepackCounts, $usageLines) {
    echo "<h2>Step {$GLOBALS['STEP']}: Filter Out Prepacks</h2>";
    $GLOBALS['STEP']++;

    $msg = "Next, we remove Usage Lines that are candidates for prepacking: the quantity is a multiple of 28, and ";
    $msg .= "the Usage Line's NDC11 appears in the following list:";
    echo make_p($msg);

    echo "<ul>";
    foreach (UsageLine::$PREPACKS as $ndc11 => $drugname) {
        echo "<li>$drugname ($ndc11)</li>";
    }
    echo "</ul>";

    echo make_p("This resulted in the following Usage Lines being removed:");
    echo "<table>";
    echo "<tr>";
    echo "<th>Drug</th>";
    echo "<th>Quantity Filtered Out</th>";
    echo "<th>Quantity / 28</th>";
    echo "</tr>";
    foreach (UsageLine::$PREPACKS as $ndc11 => $drugname) {
    echo "<tr>";
    echo "<td>$drugname</td>";
    $quantity = isset($prepackCounts[$ndc11]) ? $prepackCounts[$ndc11] : 0;
    echo "<td>$quantity</td>";
    $numPrepacks = $quantity / 28;
    echo "<td>$numPrepacks</td>";
    echo "</tr>";
    }
    echo "</table>";

    echo make_p('After applying these rules, '.count($usageLines). ' lines remain.');
}

/*
 * usagelines2Drugs_NDC11
 *
 * Converts the UsageLines into UsageDrugs, based on exact NDC11 matches.
 */
function usagelines2drugs_NDC11($usageLines)
{
    $drugs = [];
    foreach ($usageLines as $ul) {
        $ndc11 = $ul->ndc11;
        if (isset($drugs[$ndc11])) {
            $drugs[$ndc11]->absorbUsageLineNDC11($ul);
        } else {
            $drugs[$ndc11] = UsageDrug::fromUsageLine($ul);
        }
    }

    // Make sure all NDCs are unique
    $allNDC11s = [];
    foreach ($drugs as $d) {
        $ndcs = $d->getNDC11s();
        assert(count($ndcs) == 1);
        $ndc = $ndcs[0];
        if (!isset($allNDC11s[$ndc])) {
            $allNDC11s[$ndc] = true;
        }
    }
    assert(count($allNDC11s) == count($drugs));
    return $drugs;
}

function echo_step1c_results($usageLines, $usageDrugs)
{
    echo "<h2>Step {$GLOBALS['STEP']}: Usage Data, Combining Based on NDC11</h2>";
    $GLOBALS['STEP']++;
    echo make_p('These '.count($usageLines).' usage lines were combined into '.count($usageDrugs).' unique Usage Drugs based on exact NDC11 matches.');
}


function write_merged_duplicates($mergedDuplicates, &$wfs)
{
    $wf = null;

    // Write out possible duplicates as an output file
    if ($mergedDuplicates) {
        $filepath = $GLOBALS['outputDir'] . 'possible_duplicates.txt';
        $header = "Merged Usage-File Duplicates Based on Drug Name and Strength" . nl() . nl();
        $header .= $mergedDuplicates[0][0]->fileHeaderUD();
        write_array($filepath, $mergedDuplicates, '\grx1\dosis\dups_write_array', $header);

        $descr = 'Usage drugs with different NDC11s that were merged based on drug name and strength';
        $instr = 'Look over these pairs of drugs to see if any drugs that actually seem different got merged together.';
        $outputName =  "USAGE: Merged Duplicates";
        $descrClasses = ["dosis-merged-dups"];
        $wf = new WrittenFile($filepath, $outputName, $descr, $instr, $descrClasses);
        $wfs[] = $wf;
    }

    return $wf;
}


function echo_step1d_results($usageDrugs, $mergedDuplicates, $wf)
{
    $numDups  = count($mergedDuplicates);
    $numDrugs = count($usageDrugs);

    echo "<h2>Step {$GLOBALS['STEP']}: Usage Data, Combining Based on Name & Strength</h2>";
    $GLOBALS['STEP']++;
    $msg = 'Some lines in a usage file have different NDC11s, and yet they refer to the same compound. ';
    $msg .= 'To make our quantity counts as accurate as possible, we next combine ';
    $msg .= 'compounds with different NDC11s that probably refer to the same compound, based on drug name and strength.';
    echo make_p($msg);

    if ($mergedDuplicates) {
        assert(!is_null($wf));
        echo make_p("$numDups pairs of Drugs were merged based on similarities in drug name and drug strength.");
        echo make_p("These pairs can be seen here: {$wf->html_link()}");
    } else {
        echo make_p("No potentially duplicate pairs of Usage Drugs were found in your usage file.");
    }

    echo make_p("That leaves $numDrugs Drugs for the next steps.");
}


function add_usage_ranks($usageDrugs)
{
    usort($usageDrugs, array("grx1\dosis\UsageDrug", "cmpUsage"));
    foreach ($usageDrugs as $index => $ud) {
        $rank = $index + 1;   // start ranks at 1, not 0
        $ud->absorbUsageRank($rank);
    }
    foreach ($usageDrugs as $ud) {
        assert($ud->hasUsageRank());
    }
    return $usageDrugs;
}

function add_percentiles($usageDrugs)
{
    // Make sure the data are in order
    for ($ii = 0; $ii < count($usageDrugs) - 1; $ii++) {
        assert($usageDrugs[$ii]->getUsageRank() < $usageDrugs[$ii + 1]->getUsageRank());
    }

    // Figure out the total quantity, for percentages
    $totalUsageQuantity = 0;
    foreach ($usageDrugs as $ud) {
        $totalUsageQuantity += $ud->getQuantity();
    }

    // Add percentages to UsageDrugs
    foreach ($usageDrugs as $ud) {
        $ud->addPropotion($totalUsageQuantity);
    }

    // Add cumulatve percentages to UsageDrugs
    $priorProportion = 0;
    for ($ii = 0; $ii < count($usageDrugs); $ii++) {
        $usageDrugs[$ii]->addCumProportion($priorProportion);
        $priorProportion = $usageDrugs[$ii]->getUsageCumProportion();
    }

    // Make sure the cumulative percentages pass some basic tests
    for ($ii = 0; $ii < count($usageDrugs) - 1; $ii++) {
        $currentUD = $usageDrugs[$ii];
        $nextUD = $usageDrugs[$ii + 1];
        assert($currentUD->getUsageCumProportion() <= 1);
        assert($currentUD->getUsageCumProportion() > 0);
        assert($currentUD->getUsageCumProportion() < $nextUD->getUsageCumProportion(), "$ii");
    }

    return $usageDrugs;
}


///////////////////
// PROBLEM DRUGS //
///////////////////
function gather_problem_drugs($FILES, $POST)
{
    // User-submitted data
    $problemDrugData = get_user_problem_drugs($FILES, 'problemdrugfile');
    $userProblemDrugData = clean_problem_drug_data($problemDrugData);

    // Radio button regexes
    $problemRegexes = process_radio_button_regexes($POST);

    return [$userProblemDrugData, $problemRegexes];
}

function get_user_problem_drugs($FILES, $formname)
{
    $info = $FILES[$formname];
    if (file_was_uploaded($info) && problem_drug_file_is_text($info) && size_ok($info)) {
        if (is_uploaded_file($info['tmp_name'])) {
            $problemDrugData = file($FILES[$formname]['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return $problemDrugData;
        } else {
            echo make_failure_text('Something went very wrong with your problem drug file... Contact hoxiea@gmail.com');
            die;
        }
    } else {
        echo make_p('No problem drug file uploaded! Proceeding without a problem drug file...');
        return [];
    }
}

function clean_problem_drug_data($problemDrugData)
{
    $problemDrugData = trim_leading_spaces($problemDrugData);
    $problemDrugData = drop_comment_lines($problemDrugData);
    return $problemDrugData;
}

function file_was_uploaded($file_info)
{
    return $file_info['error'] != 4;
}

function problem_drug_file_is_text($file_info)
{
    $file_type = $file_info['type'];
    return strpos($file_type, 'text') !== false;
}

function size_ok($file_info)
{
    $file_size_bytes = intval($file_info['size']);
    return $file_size_bytes < 3000;  # 3kb
}

function process_radio_button_regexes($POST)
{
    $problemRegexes = array();
    $removeCHEW = ($POST['removechew'] == 'yes');
    $removeORIG = ($POST['removeorig'] == 'yes');
    if ($removeCHEW) {
        $problemRegexes["~(?<!81MG) CHEW~"] = "CHEW, without 81MG before it";
    }
    if ($removeORIG) {
        $problemRegexes["~\(O?R?I?G?\)?$~"] = "Some or all of (ORIG) at the end of the drug name";
    }
    return $problemRegexes;
}


/*
 * Mutates the UsageDrugs in array $uds
 */
function annotate_problem_usagedrugs(&$uds, $strings, $regexes)
{
    list($usedStrings, $unusedStrings) = update_problem_drugs_strings($uds, $strings);
    $usedRegexes = update_problem_drugs_regexes($uds, $regexes);
    return [$usedStrings, $unusedStrings, $usedRegexes];
}

function update_problem_drugs_strings(&$uds, $strings)
{
    $usedStrings = [];   // [String -> [Drug]]
    foreach ($strings as $string) {
        foreach ($uds as $ud) {
            if ($ud->absorbProblemDrugString($string)) {
                $usedStrings[$string][] = $ud;
                break;   // first match is enough to be a Problem Drug
            }
        }
    }
    $unusedStrings = array_diff($strings, array_keys($usedStrings));
    assert(count($usedStrings) + count($unusedStrings) == count($strings));
    return [$usedStrings, $unusedStrings];
}

function update_problem_drugs_regexes(&$uds, $regexes)
{
    $usedRegexes = [];
    foreach ($uds as $ud) {
        foreach ($regexes as $regex => $description) {
            if ($ud->absorbProblemDrugRegex($regex)) {
                $usedRegexes[$regex][] = $ud;
                break;   // first match is enough to be a Problem Drug
            }
        }
    }
    return $usedRegexes;
}

function write_full_usage($uds, &$wfs)
{
    $wf = null;
    if ($uds) {
        $path = $GLOBALS['outputDir'] . 'FullUsageInfo.txt';
        $header = "Usage Summary" . nl() . nl();
        $header .= $uds[0]->fileHeaderUD("-", true); // true: include percentages
        write_array($path, $uds, '\grx1\dosis\all_usage_write_array', $header);

        $descr = "Summary of all Drugs in your provided Usage file";
        $instr = '';
        $linktext = "USAGE: Full Summary";
        $descrClasses = ["dosis-usage-summary"];
        $wf = new WrittenFile($path, $linktext, $descr, $instr, $descrClasses);
        $wfs[] = $wf;
    }
    return $wf;

}

function echo_step1f_results($usageDrugs, $wfAllUsage)
{
    echo "<h2>Step {$GLOBALS['STEP']}: Usage Data, Summarizing All Usage Data</h2>";
    $GLOBALS['STEP']++;
    $msg = "Here's a summary of all the Drugs in your usage file (including Problem Drugs): ";
    $msg .= $wfAllUsage->html_link();
    echo make_p($msg);
    echo make_p("Note: A '#' before an NDC means that more than one NDC was pooled to form this record.");
}

function write_unused_problem_strings($unusedStrings, &$wfs)
{
    $wf = null;
    if ($unusedStrings) {
        $path = $GLOBALS['outputDir'] . 'UnusedProblemDrugLines.txt';
        $header = "Lines From Your Problem Drug File That Didn't Match Any Drugs" . nl() . nl();
        write_array($path, $unusedStrings, '\grx1\dosis\each_element_new_line', $header);

        $descr = "Lines in your Problem Drugs file that didn't match any Usage Drugs";
        $instr = '';
        $linktext = "Unused Problem Drug Lines";
        $wf = new WrittenFile($path, $linktext, $descr, $instr);
        $wfs[] = $wf;
    }
    return $wf;
}

function write_used_problem_strings($usedStrings, &$wfs)
{
    $wf = null;
    if ($usedStrings) {
        $path = $GLOBALS['outputDir'] . 'UsedProblemDrugLines.txt';
        $header = "Lines From Your Problem Drug File That Matched Usage Drugs, ";
        $header .= "And The Usage Drugs They Matched" . nl() . nl();
        $header .= "LINE FROM PROBLEM DRUG FILE" . nl() . nl();
        $header .= "    MATCHING DRUGS" . nl() . nl();
        $header .= "-----------------------------------------" . nl() . nl();
        write_array($path, $usedStrings, '\grx1\dosis\used_strings_write_array', $header);

        $descr = "Lines in your Problem Drugs file that matched at least one Usage Drug, and the Usage Drugs they matched";
        $instr = '';
        $linktext = "Problem Drug File Matches";
        $wf = new WrittenFile($path, $linktext, $descr, $instr);
        $wfs[] = $wf;
    }
    return $wf;
}

function echo_step1f_results2($wfUnusedStrings, $wfUsedStrings)
{
    echo "<h2>Step {$GLOBALS['STEP']}: Usage Data, Finding Problem Drugs</h2>";
    $GLOBALS['STEP']++;

    $msg = "<h3>Problem Drug File</h3>";

    // User's Problem Drug File
    if ($GLOBALS['problemStrings']) {
        $msg .= make_p("You provided a Problem Drug File.".PHP_EOL);
        if ($wfUnusedStrings) {
            $info = "These lines from your file didn't match any usage drugs: ";
            $info .= "{$wfUnusedStrings->html_link()}";
            $msg .= make_p($info);
        } else {
            $msg .= make_p("Every line in your Problem Drug file matched at least one Usage Drug.".PHP_EOL);
        }

        if ($wfUsedStrings) {
            $info = "And here are all the Usage Drugs matched by lines in your file: ";
            $info .= "{$wfUsedStrings->html_link()}";
            $msg .= make_p($info);
        } else {
            $msg .= make_p("None of your Problem Drug lines matched a Usage Drug.");
        }
    } else {
        $msg .= make_p("You didn't provide a Problem Drug file.");
    }
    echo $msg;
}

function write_used_regexes($usedRegexes, $problemRegexes, &$wfs)
{
    /*
     * $usedRegexes:    [regex => [Drug]]
     * $problemRegexes: [regex => String description]
     *
     * The user should see the description, not the actual regex.
     * If we're going to use write_array to write things out, then the same
     * array must include both the descriptions and the Drugs
     *
     * Thus, we combine $usedRegexes and $problemRegexes into $regexArray.
     */
    $regexArray = [];
    foreach ($usedRegexes as $regex => $drugArray) {
        $descr = $problemRegexes[$regex];
        $regexArray[$descr] = $drugArray;
    }

    $wf = null;
    if ($usedRegexes) {
        $path = $GLOBALS['outputDir'] . 'UsedProblemDrugCheckboxes.txt';
        $header = "Problem Drug Checkbox Options, And The Usage Drugs They Matched" . nl() . nl();
        $header .= "LINE FROM PROBLEM DRUG FILE" . nl();
        $header .= "    MATCHING DRUGS" . nl() . nl();
        $header .= "-----------------------------------------" . nl() . nl();
        write_array($path, $regexArray, '\grx1\dosis\used_regexes_write_array', $header);

        $descr = "The checkbox options for Problem Drugs you selected on the home screen, and the Usage Drugs that were flagged by each option";
        $instr = '';
        $linktext = "Problem Drug Checkbox Matches";
        $descrClasses = ["dosis-checkbox-problemdrugs"];
        $wf = new WrittenFile($path, $linktext, $descr, $instr, $descrClasses);
        $wfs[] = $wf;
    }
    return $wf;
}

function echo_step1f_results3($wfRegexes)
{
    $msg = "<h3>Problem Drug Checkboxes</h3>".PHP_EOL;

    if ($GLOBALS['problemRegexes']) {
        $numCheckboxes = count($GLOBALS['problemRegexes']);
        $msg .= make_p("You selected $numCheckboxes Problem Drug checkboxes.".PHP_EOL);
        if ($wfRegexes) {
            $info = "Here are the checkbox options that matched at least one Usage Drug: ";
            $info .= "{$wfRegexes->html_link()}";
            $msg .= make_p($info);
        } else {
            $msg .= make_p("The checkbox option(s) didn't match any Usage Drugs.".PHP_EOL);
        }
    } else {
        $msg .= make_p("You didn't select any Problem Drug checkboxes.");
    }
    echo $msg;
}

///////////////////////////////////
// PART 2: DOSIS FILE PROCESSING //
///////////////////////////////////
/*
 * Inputs:
 * - $a: [String] == lines of user's Dosis file
 */
function dosis_data_to_DosisDrugs($a)
{
    // Drop the header, if it's still there
    if ($a[0] == dosis_header()) {
        $a = drop_elements($a, 1);
    }

    // Parse the lines of the file
    $a = array_map('\grx1\dosis\line_to_array', $a);
    $a = array_map('\grx1\dosis\parse_dosis_line_array', $a);

    $dds = [];
    foreach ($a as &$pair) {
        $druginfo  = $pair[0];
        $ndc11     = $pair[1];
        $dds[] = new \grx1\dosis\DosisDrug($druginfo, $ndc11);
    }
    return $dds;
}

function parse_dosis_line_array($a)
{
    $druginfo = $a[0];
    $ndc11 = substr($a[3], 0, 11);
    return array($druginfo, $ndc11);
}


function echo_part2_results($dosisDrugs)
{
    $numDosisDrugs = count($dosisDrugs);
    echo "<h2>Step {$GLOBALS['STEP']}: Dosis Data, Processing</h2>";
    $GLOBALS['STEP']++;
    echo make_p("Made $numDosisDrugs Dosis Drugs out of the provided Dosis file.");
}


///////////////////////////////////
// PART 3: MERGING AND FINISHING //
///////////////////////////////////
function match_drugs_ndc11(&$uds, &$dds)
{
    foreach ($dds as $dd) {
        foreach ($uds as $ud) {
            if ($ud->absorbDosisMatchNDC11($dd)) {
                $result = $dd->absorbUsageMatch($ud);
                if (!$result) {
                    throw new \Exception("DD matched UD, but UD didn't match DD!?");
                }
                break;
            }
        }
    }
}

function write_unmatched_dosis_drugs($unmatchedDDs, &$wfs)
{
    $wf = null;
    if ($unmatchedDDs) {
        $path = $GLOBALS['outputDir'] . 'DosisDrugsNoUsageMatch.txt';
        $header = "Dosis Drugs That Didn't Have a Matching Usage Drug" . nl() . nl();
        $header .= Drug::fileHeader();
        write_array($path, $unmatchedDDs, '\grx1\dosis\unmatched_dds_write_array', $header);

        $descr = "Dosis Drugs that didn't have a matching Usage Drug in your usage file";
        $instr = '';
        $linktext = "DOSIS: No Usage Match";
        $wf = new WrittenFile($path, $linktext, $descr, $instr);
        $wfs[] = $wf;
    }
    return $wf;
}

function find_unmatched_dosis_drugs($dosisDrugs)
{
    return array_filter($dosisDrugs, function ($dd) { return $dd->lacksUsageMatch(); });
}

function echo_part3a_results($wf)
{
    echo "<h2>Step {$GLOBALS['STEP']}: Matching Usage Drugs and Problem Drugs</h2>";
    $GLOBALS['STEP']++;
    if ($wf) {
        $msg = "Some of your Dosis Drugs didn't have a matching Usage Drug. ";
        $msg = "These unmatched Dosis Drugs can be seen here: {$wf->html_link()}";
    } else {
        echo make_p("Found a matching Usage Drug for all your Dosis Drugs.");
    }
}


function find_nondosis_problem_drugs($uds)
{
    return array_values(array_filter($uds, function ($ud) {
        return $ud->isProblemDrug() && $ud->lacksDosisMatch();
    }));  // array_values to reset keys
}

function write_problem_usage_drugs($problemUsageDrugs, &$wfs) {
    $wf = null;
    if ($problemUsageDrugs) {
        $path = $GLOBALS['outputDir'] . 'ProblemUsageDrugs.txt';
        $header = "Usage Drugs Removed for Being Problematic and Non-Dosis" . nl() . nl();
        $header .= $problemUsageDrugs[0]->fileHeaderUD();
        write_array($path, $problemUsageDrugs, '\grx1\dosis\problem_uds_write_array', $header);

        $descr = 'Usage Drugs that were problematic and not in the Dosis machine';
        $instr = '';
        $linktext = "USAGE: Problem Drugs";
        $descrClasses = ["dosis-problem-drugs"];
        $wf = new WrittenFile($path, $linktext, $descr, $instr, $descrClasses);
        $wfs[] = $wf;
    }
    return $wf;
}

function echo_part3b_results($wf)
{
    echo "<h2>Step {$GLOBALS['STEP']}: Removing Problematic Usage Drugs</h2>";
    $GLOBALS['STEP']++;
    if ($wf) {
        $msg = "The following Usage Drugs were removed due to being both ";
        $msg .= "(a) a problem drug, and (b) not a Dosis Drug: ";
        $msg .= "{$wf->html_link()}";
    } else {
        $msg = "No problem Usage Drugs were removed.";
    }
    echo $msg;
}


function find_included_slow_movers($usageDrugs, $cutoffNum)
{
    return array_values(array_filter($usageDrugs, function ($ud) use ($cutoffNum) {
        return $ud->isIncludedSlowMover($cutoffNum);
    }));
}

function find_missing_fast_movers($usageDrugs, $cutoffNum)
{
    return array_values(array_filter($usageDrugs, function ($ud) use ($cutoffNum) {
        return $ud->isMissingFastMover($cutoffNum);
    }));
}

function write_included_slow_movers($isms, $cutoffNum, &$wfs)
{
    $wf = null;
    if ($isms) {
        $path = $GLOBALS['outputDir'] . 'IncludedSlowMovers.txt';
        $header = "Included Slow Movers: Drugs In the Dosis Machine With Usage Rank > $cutoffNum" . nl() . nl();
        $header .= $isms[0]->fileHeaderUD();
        write_array($path, $isms, '\grx1\dosis\isms_write_array', $header);

        $descr = "Drugs In the Dosis Machine With Usage Rank > $cutoffNum";
        $instr = "Consider replacing these with Missing Fast Movers";
        $linktext = "Included Slow Movers";
        $descrClasses = ["dosis-included-slow-movers"];
        $wf = new WrittenFile($path, $linktext, $descr, $instr, $descrClasses);
        $wfs[] = $wf;
    }
    return $wf;
}

function write_missing_fast_movers($mfms, $cutoffNum, &$wfs)
{
    $wf = null;
    if ($mfms) {
        $path = $GLOBALS['outputDir'] . 'MissingFastMovers.txt';
        $header = "Missing Fast Movers: Drugs NOT In the Dosis Machine With Usage Rank <= $cutoffNum" . nl() . nl();
        $header .= $mfms[0]->fileHeaderUD();
        write_array($path, $mfms, '\grx1\dosis\mfms_write_array', $header);

        $descr = "Drugs NOT In the Dosis Machine With Usage Rank <= $cutoffNum";
        $instr = "Consider replacing Included Slow Movers with these drugs";
        $linktext = "Missing Fast Movers";
        $descrClasses = ["dosis-missing-fast-movers"];
        $wf = new WrittenFile($path, $linktext, $descr, $instr, $descrClasses);
        $wfs[] = $wf;
    }
    return $wf;
}

function echo_ism_mfm_results($wfISM, $wfMFM)
{
    echo "<h2>Step {$GLOBALS['STEP']}: Included Slow Movers and Missing Fast Movers</h2>";
    $GLOBALS['STEP']++;
    if ($wfISM) {
        echo make_p("Here are your Included Slow Movers: {$wfISM->html_link()}");
    } else {
        echo make_p("No Included Slow Movers were found.");
    }
    if ($wfMFM) {
        echo make_p("Here are your Missing Fast Movers: {$wfMFM->html_link()}");
    } else {
        echo make_p("No Missing Fast Movers were found.");
    }
}

/////////////////////
// GENERAL HELPERS //
/////////////////////

/*
 * Inputs:
 * - $array: an array of things that you'd like to index
 * - $indexing_function: this function can be applied to each element of
 *   $array, and it returns a value that can be used as a key in an associative
 *   array (integer or string)
 * - $preserve_array_keys: Bool. The keys in $array could actually have
 *   meaning. If you'd like, this function can preserve each key when its
 *   corresponding value is assigned to an indexed subarray. For example, if
 *   $drugs has been sorted by usage, then the keys are the usage ranks, and
 *   keeping these around makes it easy to figure out the rank of each dosis
 *   drug.
 *
 * Output:
 * - an array where all elements of $array with the same $indexing_function
 *   value have been grouped together in an array that's mapped to by that key
 */
function convert_to_indexed_array($array, $indexing_function, $preserve_array_keys = true)
{
    $result = [];
    foreach ($array as $key => $value) {
        $index = call_user_func($indexing_function, $value);
        if (isset($result[$index])) {  // If this index has already been seen...
            if ($preserve_array_keys) {  // And you want to preserve key => value...
                $result[$index][$key] = $value;
            } else { // And you don't care about the preserving key => value...
                $result[$index][] = $value;
            }
        } else {  // This index hasn't already been seen...
            if ($preserve_array_keys) {  // But you want to preserve key => value...
                $result[$index] = array($key => $value);
            } else {  // And you don't care about the preserving key => value...
                $result[$index] = [$value];
            }
        }
    }
    return $result;
}

// Flatten an array of arrays, like those produced by convert_to_indexed_array
// Note that this doesn't preserve the keys, if preserve_array_keys was true above
function flatten_indexed_array($array)
{
    $flat = [];
    foreach ($array as $index => $a) {
        $flat = array_merge($flat, $a);  // NOTE: performance enhancement possibly relevant here
    }
    return $flat;
}


/**
 * merge_similar_usage_drugs
 *
 * Go through an array of Drugs, merging similar pairs based on Drug::absorbSimilarDrug.
 *
 * Inputs:
 * @drugs : [Drug]
 * @prefix_length : Int. How many characters to use for the indexed array keys.
 *     (prefix_length matters only for computational complexity, since a flat array of Drugs is returned)
 *
 * Outputs:
 * @output : [mixed], where
 *   $output[0] : [Drug], the merged Drugs
 *   $output[1] : [[Drug, Drug]], an array of pairs of Drugs that were merged
 *
 * We use an indexed array (explained at top) so that we're not comparing
 * radically different drugs.
 * But within a subarray (that contains only drugs with the same first letters
 * of drugname), we want to compare all pairs of drugs, not just adjacent ones
 * in alphabetical order
 *
 * If we find a pair of Drugs that are likely duplicates, the trick is to get
 * all of the information from those duplicates into the same single Drug
 * object, and to keep track of that Drug object so that it (the Drug
 * containing the updated information) emerges from the chaos of merging.
 *
 * Complicating this is that there could be triplets: three Drugs that are all
 * actually the same.
 *
 * To do this, for each prefixed subarray, we start walking through pairs with
 * a classic double loop.
 * For each pair of drugs ("the earlier drug" via the outer loop and "the later
 * drug" via the inner loop), we:
 * 1. Try to absorb the later Drug into the earlier Drug via Drug->absorbSimilarDrug.
 *    This returns a boolean of success/failure.
 * 2. If absorbSimilarDrug succeeded, then we add the pair to $mergedDuplicates
 *
 * We also replace the later Drug from the subarray with null, so that it
 * won't eventually be an "earlier Drug" and so that the first Drug of any
 * duplicate sets is always the one that contains the most information.
 */
function merge_similar_usage_drugs($drugs, $prefix_length = 4)
{
    // Build an indexed Drug array, where keys are the first $prefix_length letters of the first cleanedDruginfo
    $drugsIndexed = convert_to_indexed_array($drugs, function ($d) use ($prefix_length) {
        return substr($d->getCleanedDruginfos()[0], 0, $prefix_length);
    }, false);  // false => reset the keys for each subarray

    // As we merge similar Drugs, we modify $drugsIndexed directly
    // We also need to keep track of the merges we make
    $mergedDuplicates = [];

    // Merge
    foreach ($drugsIndexed as $prefix => &$a) {
         // &$a is a REFERENCE, so that mutations apply to $drugsIndexed
        for ($ii = 0; $ii < count($a) - 1; $ii++) {  // can use count() here, since we're nulling, not unsetting
            for ($jj = $ii + 1; $jj < count($a); $jj++) {
                if ($a[$ii] !== null && $a[$jj] !== null) {
                    $d1 = $a[$ii];  // earlier drug
                    $d2 = $a[$jj];  // later drug
                    if ($d1->absorbSimilarDrug($d2)) {
                        $mergedDuplicates[] = [$d1, $d2];
                        $a[$jj] = null;  // null out the later drug
                    }
                }
            }
        }
        $a = array_values(array_filter($a));  // filter out the nulls, then reset the keys
    }

    // Flatten
    $mergedDrugs = flatten_indexed_array($drugsIndexed);
    return array($mergedDrugs, $mergedDuplicates);
}

function trim_leading_spaces($lines)
{
    $trimmer = function($line) {
        return ltrim($line);
    };
    return array_map($trimmer, $lines);
}

function drop_comment_lines($lines)
{
    $comment_chars = "#";   // can add others to this string
    $line_doesnt_start_with_comment_char = function ($line) use ($comment_chars) {
        $first_char = $line[0];
        return strpos($comment_chars, $first_char) === false;
    };
    return array_filter($lines, $line_doesnt_start_with_comment_char);
}

function find_drugs_exact_string_match($drugs, $strings)
{
    $matchingDrugs = [];       // [String1 -> [Drug], String2 -> [Drug], ...]
    $nonMatchingDrugs = [];    // [Drug]
    $numMatchingDrugs = 0;

    foreach ($drugs as $drug) {
        $drugMatchedAString = false;
        foreach ($strings as $string) {
            if ($drug->rawDruginfoContainsString($string)) {
                $matchingDrugs[$string][] = $drug;
                $drugMatchedAString = true;
                $numMatchingDrugs++;
                break;  // a drug could match more than one string; the first one is enough to flag as a match
            }
        }

        if (!$drugMatchedAString) {
            $nonMatchingDrugs[] = $drug;
        }
    }

    assert($numMatchingDrugs + count($nonMatchingDrugs) == count($drugs));
    return [$matchingDrugs, $nonMatchingDrugs, $numMatchingDrugs];
}


function find_drugs_match_regex($drugs, $regexes)
{
    $matchingDrugs = [];       // [String -> [Drug]]
    $nonMatchingDrugs = [];    // [Drug]
    $numMatchingDrugs = 0;

    foreach ($drugs as $drug) {
        $drugMatchedARegex = false;
        foreach ($regexes as $regex) {
            $matchIndex = $drug->findRawDruginfoRegexMatch($regex);
            if ($matchIndex >= 0) {
                $matchingDrugs[$regex][] = $drug;
                $drugMatchedARegex = true;
                $numMatchingDrugs++;
                break;
            }
        }

        if (!$drugMatchedARegex) {
            $nonMatchingDrugs[] = $drug;
        }
    }

    assert($numMatchingDrugs + count($nonMatchingDrugs) == count($drugs));
    return [$matchingDrugs, $nonMatchingDrugs, $numMatchingDrugs];
}


/////////////////////////////
// CUTOFF NUM VERIFICATION //
/////////////////////////////
function verify_cutoff_num($post, $formname)
{
    if (!isset($post[$formname]) || !is_numeric($post[$formname])) {
        $msg = 'A numeric cutoff number is required!';
        $msg .= ' Please go back and try again.';
        echo make_failure_text($msg);
        die;
    }

    return intval($post[$formname]);
}

/*
 * This function splits a csv-line String into an array of its components
 *
 * There are two cases we need to handle:
 * 1. Every single field is surrounded by double-quotes:
 *    - "00527134410","LEVOTHYROXINE 88 MCG TABLET","28.0000","","TAB", etc.
 * 2. Only fields with commas in them are surrounded by double-quotes:
 *    - 603017932,FERROUS SULF 325 MG TAB,56,OO,TAB,D,"1,000.00",EADC
 */
function line_to_array($line)
{
    if (each_field_surrounded_by_quotes($line)) {
        return line_to_array_all_quoted($line);
    } else {
        return line_to_array_minimal_quotes($line);
    }
}

/*
 * Technically, we only check to make sure the first and last characters
 * are double quotes. But the first column (NDC code) should never have a
 * comma in it, so the first character tells us which format we have.
 */
function each_field_surrounded_by_quotes($line)
{
    $firstChar = substr($line, 0, 1);
    $lastChar = substr($line, -1, 1);
    return ($firstChar == '"' && $lastChar == '"');
}

function line_to_array_all_quoted($line)
{
    // Some lines have commas in the values in addition to commas as separators,
    // so we can't just replace all commas. Instead, replace <","> with a pipe,
    // then trim off the very first and last quotes
    $line = str_replace('","', "|", $line);
    $line = trim($line, '"');
    return explode("|", $line);
}

/*
 * Stolen from AnniversaryLine::csvLineToArray
 */
function line_to_array_minimal_quotes($line)
{
    if (strpos($line, '"') === false) {
        // No double quotes, so presumably no commas in any fields
        return explode(",", $line);
    }

    // So there's a double quote somewhere... must be one or more
    // fields that have commas in them. Make sure we have an even number:
    assert(substr_count($line, '"') % 2 === 0,
        "odd number of double quote marks in line $line"
    );

    // Replace each non-greedy quoted field with a non-quoted
    // version that has all commas replaced by the empty string
    $quoteRegex = '~"(.*?)"~';  //   ? => non-greedy
    $newCSVLine = preg_replace_callback(
        $quoteRegex,
        function ($matches) {
            return str_replace(',', '', $matches[1]);
        },
        $line
    );
    // echo make_p($line);      // might help debugging
    // echo make_p($newCSVLine);
    return explode(",", $newCSVLine);
}

///////////////////////////////
// Functions for write_array //
///////////////////////////////
function each_element_new_line($blah, $element)
{
    return "$element" . nl();
}

function usage_filtered_drugs_write_array($blah, $druginfo)
{
    return each_element_new_line($blah, $druginfo);
}

function unmatched_dds_write_array($blah, $dd)
{
    return each_element_new_line($blah, $dd);
}

function all_usage_write_array($blah, $ud)
{
    return $ud->stringForFile(true, true);  // use raw druginfo, yes percentages
}

function used_strings_write_array($string, $drugArray)
{
    $result = "$string" . nl();
    foreach ($drugArray as $ud) {
        $result .= "    $ud" . nl();
    }
    $result .= "" . nl() . nl();
    return $result;
}

function used_regexes_write_array($regexDescr, $drugArray)
{
    return used_strings_write_array($regexDescr, $drugArray);
}

function problem_uds_write_array($blah, $ud)
{
    return $ud->stringForFile(true);
}

function isms_write_array($blah, $ud)
{
    return $ud->stringForFile(true);
}

function mfms_write_array($blah, $ud)
{
    return $ud->stringForFile(true);
}

function dups_write_array($blah, $pair_of_usagedrugs)
{
    list($d1, $d2) = $pair_of_usagedrugs;
    $output  = $d1->stringForFile(true);   // true means use raw info
    $output .= $d2->stringForFile(true) . nl();
    return $output;
}
