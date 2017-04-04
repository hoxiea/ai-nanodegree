<?php
namespace grx1\dosis;

require_once '../helper_functions_global.php';
echo make_header("Dosis Analysis Results");
?>

  <body>
    <h1>Dosis Analysis Results</h1>

<?php
// Get dependencies in place
require_once './classes/UsageLine.php';
require_once './classes/Drug.php';
require_once './classes/UsageDrug.php';
require_once './classes/DosisDrug.php';
require_once './classes/DrugInfoParser.php';
require_once './classes/WrittenFile.php';

require_once '../helper_functions_global.php';
require_once './dosis_helper_functions.php';
require '../../../vendor/autoload.php';  // load composer components

// Set up logger
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('dosis');
$log->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

// Set up control variables and infrastructure
$OUTPUT = 1;    // echo progress to the results page?
$TIMING = 0;    // time each section and display the times? nice for optimization
$STEP   = 1;    // used for dynamic output, in helper functions for writing results

$times        = [];
$writtenFiles = [];  // collect written files for easy HTML table output


// Make a local folder for storing output
list($runID, $outputDir) = make_output_dir();
checkpoint($TIMING, $times, 'Initial checkpoint');

/**
 * OVERVIEW
 * The main purpose of this script is to compare the drugs in the usage file
 * with the drugs in the dosis machine.
 *
 * Part 1: Preparing Usage Data
 * - 1a) Validate usage file
 * - 1b) Convert data in usage file into UsageLines
 *          Write out the lines that get filtered out
 * - 1c) Merge UsageLines into Drugs, based on exact NDC11 matches
 * - 1d) Merge Usage Drugs that have similar name+strength
 *          Write out the pairs of Drugs that got merged
 * - 1e) Add usage_ranks and percentile info to Usage Drugs
 * - 1f) Add Problem Drug information to Usage Drugs
 *          Write out full Usage summary: ranks, quantities, problem drug status, etc.
 *          Write out user problem drug lines that didn't match any usage drugs
 *          Write out user problem drug lines that did match usage drugs, and the matches
 * Part 2: Preparing Dosis Data
 * - 2a) Validate Dosis file
 * Part 3: Matching and Calculating
 * - 3a) Match DosisDrugs and UsageDrugs based on NDC11
 *          Write out DosisDrugs that don't have a matching UsageDrug - weird case
 * - 3b) Remove UsageDrugs that (are problem drugs) AND (lack a matching DosisDrug)
 *          Write out the UsageDrugs in this group
 * - 3c) Recalculate usage_ranks for UsageDrugs, since some UsageDrugs were just removed
 * - 3d) Missing Fast Movers and Included Slow Movers
 */

/*
 * PART 1: Preparing Usage Data
 */
// 1a) Validate usage file
$usageData = verify_usage_file($_FILES, $formname = 'usagefile');
checkpoint($TIMING, $times, 'Done verifying usage file');

// 1b) Convert usage file data into UsageLines
//     Write out usage file lines that get filtered out
list($usageLines, $filteredDruginfos) = usagedata_to_UsageLines($usageData);
$wf = write_filtered_usage_lines($filteredDruginfos, $writtenFiles);
if ($OUTPUT) echo_step1b_results($usageData, $usageLines, $wf);
checkpoint($TIMING, $times, 'Convert usage file data into UsageLines');
unset($usageData, $filteredDruginfos, $wf);


// NEW: Remove UsageLines that should be prepacked
list($toPrepack, $usageLines) = partition_array($usageLines, function ($ul) {
    return $ul->shouldBePrepacked();
}, false);  // don't preserve keys

$prepackCounts = [];
foreach ($toPrepack as $ul) {
    if (isset($prepackCounts[$ul->ndc11])) {
        $prepackCounts[$ul->ndc11] += $ul->quantity;
    } else {
        $prepackCounts[$ul->ndc11] = $ul->quantity;
    }
}

if ($OUTPUT) {
    echo_prepack_filter_results($prepackCounts, $usageLines);
}
checkpoint($TIMING, $times, 'Filter out prepacks');

// $totalQuantity = 0;
// foreach ($usageLines as $ul) {
//     $totalQuantity += $ul->quantity;
// }
// echo make_p("Total quantity: $totalQuantity");

// 1c) Merge remaining UsageLines into Drugs, based on exact NDC11 matches
$usageDrugs = usagelines2drugs_NDC11($usageLines);
if ($OUTPUT) echo_step1c_results($usageLines, $usageDrugs);
checkpoint($TIMING, $times, 'UsageLines into UsageDrugs based on exact NDC11 matches');
unset($usageLines);

// 1d) Merge Usage Drugs that have similar name+strength
//     Write out the pairs of Drugs that got merged
list($usageDrugs, $mergedDuplicates) = merge_similar_usage_drugs($usageDrugs);
$wf = write_merged_duplicates($mergedDuplicates, $writtenFiles);
if ($OUTPUT) echo_step1d_results($usageDrugs, $mergedDuplicates, $wf);
checkpoint($TIMING, $times, 'Convert usage file data into UsageLines');
unset($mergedDuplicates, $wf);

// 1e) Add usage_ranks to Usage Drugs
$usageDrugs = add_usage_ranks($usageDrugs);  // reassign so that they're sorted by rank
add_percentiles($usageDrugs);
checkpoint($TIMING, $times, 'Add usage_ranks and percentiles to Usage Drugs');

// 1f) Add ProblemDrug information to Usage Drugs
//     Write out full Usage summary: ranks, quantities, problem drug status, etc.
//     Write out user problem drug lines that didn't match any usage drugs
//     Write out user problem drug lines that DID match usage drugs, and the matches
//     Write out regexes that DID match usage drugs, and the matches
list($problemStrings, $problemRegexes) = gather_problem_drugs($_FILES, $_POST);
list($usedStrings, $unusedStrings, $usedRegexes) =
    annotate_problem_usagedrugs($usageDrugs, $problemStrings, $problemRegexes);

$wfAllUsage = write_full_usage($usageDrugs, $writtenFiles);
if ($OUTPUT) echo_step1f_results($usageDrugs, $wfAllUsage);

$wfUnusedStrings = write_unused_problem_strings($unusedStrings, $writtenFiles);
$wfUsedStrings   = write_used_problem_strings($usedStrings, $writtenFiles);
if ($OUTPUT) echo_step1f_results2($wfUnusedStrings, $wfUsedStrings);

$wfRegexes = write_used_regexes($usedRegexes, $problemRegexes, $writtenFiles);
if ($OUTPUT) echo_step1f_results3($wfRegexes);


unset($unusedStrings, $usedStrings, $usedRegexes);
unset($wfUnusedStrings, $wfUsedStrings, $wfAllUsage);
checkpoint($TIMING, $times, 'Assembled problem drugs, annotated problem UsageDrugs');

echo "<hr/>";

/*
 * PART 2: Verify Dosis Data, Make Dosis Drugs
 */
$dosisData = verify_dosis_file($_FILES, $formname = 'dosisfile');
$dosisDrugs = dosis_data_to_DosisDrugs($dosisData);
if ($OUTPUT) echo_part2_results($dosisDrugs);
unset($dosisData);
checkpoint($TIMING, $times, 'Made DosisDrugs out of Dosis data');

echo "<hr/>";

/*
 * Part 3: Matching and Calculating
 */

// 3a) Match DosisDrugs and UsageDrugs based on NDC11
//     REMOVE and write out DosisDrugs that don't have a matching UsageDrug
match_drugs_ndc11($usageDrugs, $dosisDrugs);
$unmatchedDosisDrugs = find_unmatched_dosis_drugs($dosisDrugs);
$dosisDrugs = array_diff($dosisDrugs, $unmatchedDosisDrugs);
$wf = write_unmatched_dosis_drugs($unmatchedDosisDrugs, $writtenFiles);
if ($OUTPUT) echo_part3a_results($wf);
checkpoint($TIMING, $times, "Matched Dosis Drugs and Usage Drugs");
unset($unmatchedDosisDrugs);


// Question: What percentage of all orders are handled by Dosis machine?
// $total1 = 0;
// foreach ($usageDrugs as $ud) {
//     if ($ud->hasDosisMatch()) {
//         $total1 += $ud->getQuantity();
//     }
// }
// echo make_p("Total quantity for drugs in the Dosis machine: $total1");
//
// $total2 = 0;
// foreach ($usageDrugs as $ud) {
//     $total2 += $ud->getQuantity();
// }
// echo make_p("Total quantity for all drugs: $total2");


// 3b) Remove UsageDrugs that (are a problem drug) AND (don't have a matching DosisDrug)
//     Write out the UsageDrugs in this group
$problemUsageDrugs = find_nondosis_problem_drugs($usageDrugs);
$usageDrugs = array_diff($usageDrugs, $problemUsageDrugs);
$wf = write_problem_usage_drugs($problemUsageDrugs, $writtenFiles);
if ($OUTPUT) echo_part3b_results($wf);
checkpoint($TIMING, $times, "Identified and removed Usage problem drugs");

// 3c) Recalculate usage_ranks for UsageDrugs, since some UsageDrugs were just removed
$usageDrugs = add_usage_ranks($usageDrugs);
add_percentiles($usageDrugs);

// 3d) Missing Fast Movers and Included Slow Movers
$cutoffNum = verify_cutoff_num($_POST, $formname = 'cutoff');
$includedSlowMovers = find_included_slow_movers($usageDrugs, $cutoffNum);
$missingFastMovers = find_missing_fast_movers($usageDrugs, $cutoffNum);

$wfISM = write_included_slow_movers($includedSlowMovers, $cutoffNum, $writtenFiles);
$wfMFM = write_missing_fast_movers($missingFastMovers, $cutoffNum, $writtenFiles);
if ($OUTPUT) echo_ism_mfm_results($wfISM, $wfMFM);
checkpoint($TIMING, $times, 'Wrote ISMs and MFMs');

// Final Wrap-Up
if ($TIMING) {
    echo '<h2>Timing Stats</h2>';
    print_times($times);
}

// Log the main results of this whole analysis
$msg = '(SEFL) Found '.count($missingFastMovers).' missing-fastmovers, '.count($includedSlowMovers).' included-slowmovers';
$log->addInfo($msg);



// Build the HTML Table containing all output files, links, descriptions, instructions. etc.
if ($OUTPUT) {
    $results = <<<EOD
<h2>All Output Files</h2>
<div class="datagrid">
    <table>
      <tr>
        <th>File</th>
        <th>Description</th>
        <th>Special Instructions</th>
      </tr>
EOD;

    foreach ($writtenFiles as $wf) {
        $results .= $wf->html_table_row();
    }

    $results .= "</table></div>";

    echo $results;

    echo "<h2>Clean Up and Finish</h2>";
    $end_html = <<<EOD
<p><span class='warning'>For security purposes</span>, click the button below
when you're saved or printed the output files.</p>
<form method='post' action='finish_dosis.php'>
  <input type='hidden' name='run_id' value="$runID">
  <input type='submit' value='Finish Securely and Start Again'>
</form>
</p>
EOD;
    echo $end_html;
}
?>

  </body>
</html>
