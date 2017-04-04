<?php
require_once '../helper_functions_global.php';
echo make_header("Dosis/Usage Analyzer");
$step = 1;
?>

  <body>
    <h1>Dosis Analyzer</h1>
    <p>This page lets you run the Dosis/Usage Analysis.</p>
    <p>Inputs:</p>
    <ul>
        <li>Usage File, in comma-separated file (.csv) format</li>
        <li>Dosis Canister File, in comma-separated file (.csv) format</li>
        <li>Optional Problem Drug file, indicating drugs to be filtered out</li>
        <li>The cutoff rank that separates "fast movers" and "slow movers"</li>
    </ul>
    <p>Outputs:</p>
    <ul>
        <li>A ranking of all drugs in the usage file, by quantity</li>
        <li>The usage rankings of the drugs currently in the Dosis machine</li>
        <li>Missing Fast Movers: top-selling drugs that aren't in the Dosis machine</li>
        <li>Included Slow Movers: top-selling drugs that aren't in the Dosis machine</li>
    </ul>


    <form action="upload_file.php" method="post" enctype="multipart/form-data">
        <h2>Step <?php echo $step++; ?>: Usage File</h2>
        <p>Choose your comma-separated Usage file:
            <input type="file" name="usagefile" id="usagefile" />
        </p>

        <h2>Step <?php echo $step++; ?>: Dosis Canister File</h2>
        <p>Choose your comma-separated Dosis canister file:
            <input type="file" name="dosisfile" id="dosisfile" />
        </p>

        <h2>Step <?php echo $step++; ?>: Problem Drug File (Optional)</h2>
        <p>Choose your optional Problem Drug file:
            <input type="file" name="problemdrugfile" id="problemdrugfile" />
        </p>
        <p>
            Note: If you don't have a Problem Drug file, you can download a template <a href="./problem-drugs-windows.txt">here</a>.
        </p>

        <h2>Step <?php echo $step++; ?>: Problem Drug Options</h2>
        <table id='dosisoptionstable'>
          <tr>
            <td>Remove anything with CHEW except 81MG ASPIRIN?</td>
            <td>
                <input type="radio" name="removechew" value="yes" checked> Yes
                <input type="radio" name="removechew" value="no"> No
            </td>
          </tr>
          <tr>
            <td>Remove anything with (ORIG) at the end of the drug name?
            <td>
                <input type="radio" name="removeorig" value="yes" checked> Yes
                <input type="radio" name="removeorig" value="no"> No
            </td>
          </tr>
        </table>

        <h2>Step <?php echo $step++; ?>: Cutoff Rank</h2>
        <p>Specify the cut-off rank to be considered a "Fast Mover":
            <input type="number" min="1" value="250" required name="cutoff" id="cutoff" />
        </p>

        <h2>Step <?php echo $step++; ?>: Go!</h2>
        <p>Submit these files for verification and processing.</p>
        <input type="submit" name="submit" value="Submit!" />
    </form>

<?php
make_simple_footer();
?>

  </body>
</html>
