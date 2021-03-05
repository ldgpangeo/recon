<?php 
/*
 *   This opens the php monthly export file and loads the raw data into the paypal table.
 */

try {
    include_once("php_header.php");
    include_once("../../lib/r_payment_tools.php");
    include "../../lib/r_load_paypal_data.php";
    
    $title = "Show Reconciliation";
    #   $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";
    
    
#  the following must be set for each csv file
$skip = 1;               #number of lines to skip at the beginning.
$dates = array (0);      #which columns are dates, count from zero

#  ------------------------------  start form processing  --------------------------
if ($_POST['submit'] <> '') {
    $infile = "/tmp/".time().".csv";
    $report = "";   #  report of import results.
    
    debug ("$files array contains\n".dump_array($_FILES));
    debug ("Saving image to $infile from {$_FILES['newfile']['tmp_name']}");
    if (! move_uploaded_file($_FILES['newfile']['tmp_name'],$infile)) {
        throw new EXCEPTION ("CSV File upload failed ($dest)",ERROR_MAJOR);
    }

    #  begin processing the file
$fhdl = fopen($infile, 'r');
if ($fhdl === false) {
    throw new EXCEPTION ("CSV file not found.", ERROR_MINOR);
}
#  read the csv file into an array
$rowct = 0;
$data = array();

while (($row = fgetcsv($fhdl, 0,",")) !== false ) {
    $rowct ++;
    if ($rowct > $skip) {
        foreach ($dates as $col) {
            $row[$col] = strftime('%F', strtotime($row[$col]));
        }
        array_push($data, $row);
    }
}
fclose($fhdl);
debug( "csv has $rowct rows \n" );
$report .= "Rows read from the csv file:  $rowct<br /.";

# empty out the incoming table
do_sql("truncate r_paypal_incoming");

do_sql("start transaction");

$rownum = $skip;
$errmsg = '';
$added = 0;
$failed = 0;
foreach ($data as $row) {
    $rownum ++;
    $coltmp = array();
    foreach ($row as $col) {
        #  make each cell database safe
        if ( strpos($col,"'") > 0) {
            $col = addslashes($col);
        } 
        array_push($coltmp, $col);
    }
    $tmp = implode ("','", $coltmp);
    $sql = "insert r_paypal_incoming values ( '$tmp' )";
#    print "$sql\n\n";
    $res = do_sql($sql, false, false);
    if ($res === false ) {
        $failed ++;
        $errmsg .= "unable to import row $rowct:<br>$tmp<br><br>";
    } else {
        $added ++;
    }
}

#  test if any errors found
if ($errmsg <> '') {
    $tmp = "Spreadsheet could not be loaded for the following reasons:<br><br>" . $errmsg;
    throw new EXCEPTION ("$tmp", ERROR_MINOR);
}

do_sql ("commit");

$report .= "Rows loaded into temporary table: $added<br />";
$report .= "Rows unable to load into temporary table:  $failed </br>";

#  Now move the data into the correct tables

# populate r_payments
$result = load_paypal_from_incoming();
$report .= "Rows imported into Sponsorship Database: {$result['success']} <br />";
$report .= "Rows that duplicate existing data: {$result['duplicate']} <br />";
$report .= "Rows that are not sponsorships: {$result['other']} <br />";
$report .= "Rows where import failed: {$result['fail']} <br />";
if ($result['fail'] > 0) {
    $report .= "  (<a href=\"missing_payment_match.php\">Visit</a>)";
}
$report .= "<br />";


}  #  end if submit
#  -------------------------------  End form processing  ----------------------------

?>
<h2 align="center">Import a Paypal File</h2>
<P>
<blockquote>
<blockquote>
<?php print $report ?>
</blockquote>
</blockquote>
<form action="" method="POST" enctype="multipart/form-data">
<table align="center" border="1" cellpadding="3" cellspacing="3" width="50%">
<tr bgcolor="#FFFFCC">
<th colspan="2">
<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
<input type="hidden" name="id" value="<?php print $sessionid ?>">
Choose the csv file to import<br>This must be a PayPal Activity Download Report
</th>
</tr>
<tr>
<td>PayPal file:</td>
<td><input name="newfile" type="file" /></td>
</tr>
<tr bgcolor="#FFFFCC">
<td align="center" colspan="2">
<a href="index.php" class="MyGreen">Go Home</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="submit" value="Save Changes" class="MyRed">
</td>
</tr>
</table>
</form>


<?php
} catch(Exception $err) {
	$trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
	logerr($err->getMessage(),$err->getCode(),$trace);
}
?>
