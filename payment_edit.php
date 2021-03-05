<?php 
try {

    include_once("php_header.php");
#    include_once("../../lib/r_payment_tools.php");
    
    $title = "Edit a payment";
 #   $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";
    
$paymentid = getinput('paymentid');
if ($paymentid == '') {
    throw NEW dxception("unknown payment", ERROR_MAJOR);
}
$civicrmid = getinput('civicrmid');
$itemid = getinput('itemid');

$res = do_sql( "Select * from r_payments where paymentid = '$paymentid'" );
if (mysqli_num_rows($res) <> 1) {
    throw NEW exception ("Failed to find a unique payment.", ERROR_MAJOR);
}
$row = mysqli_fetch_assoc($res);

# ------------------------------  Start Form processsing ---------------------------------
if (isset($_POST['submit']) and ($_POST['submit'] == "Save Changes")) {
    $errmsg = "";
    debug ("form values are \n".dump_array($_POST) );
    $in = form_validate('edit_payment', $errmsg);
    if (($in['is_active'] == "N") and ($in['note'] == '') ) {$errmsg .= "You must provide a reason when canceling a payment.<br/>"; }
    if ($errmsg <> '') {
        throw NEW exception ($errmsg. ERROR_MINOR);
    }
    $sql = "update r_payments set type = '{$in['type']}', datedone = '{$in['datedone']}', source = '{$in['source']}', amount = '{$in['amount']}', ";
    $sql .= "transactionid = '{$in['transactionid']}', is_active = '{$in['is_active']}', note = '{$in['note']}' where paymentid = '{$in['paymentid']}' ";
    $res = do_sql($sql);
    #  log the edit
    $items = array ('type', "datedone", "source", "amount", "transactionid", "is_active", "note");
    $detail = "login = $login";
    foreach ($items as $item) {
        if ($in[$item] <> $row[$item]) { $detail .= ", $item from '{$row[$item]}' to '{$in[$item]}'"; }
    }
    logit("Payment_edited",$detail);
    
    $redirect = "show_recon.php?civicrmid=$civicrmid&itemid=$itemid";
}
# ------------------------------   End Form processsing  ---------------------------------
include "page_header.php";

?>
<form action="" method = "POST">
<h2 align="center">Edit a Payment</h2>
<table width="60%" cellspacing="3" cellpadding = "3" border = "1" align = "center">
<tr>
<td>Type:
</td>
<td>
<select name="type">
<option <?php if ($row['type'] == 'sponsorship') {print " selected";} ?>>sponsorship</option>
<option <?php if ($row['type'] == 'nest egg') {print " selected";} ?>>nest egg</option>
<option <?php if ($row['type'] == 'spending') {print " selected";} ?>>spending</option>
<option <?php if ($row['type'] == 'other') {print " selected";} ?>>other</option>
</select>
</td>
</tr>
<tr>
<td>Source:
</td>
<td>
<select name="source">
<option value="">Choose...</option>
<option <?php if ($row['source'] == 'quickbooks') {print " selected";} ?> value="quickbooks">QuickBooks</option>
<option <?php if ($row['source'] == 'paypal') {print " selected";} ?> value="paypal">PayPal</option>
<option <?php if ($row['source'] == 'check') {print " selected";} ?> value="check">Check</option>
</select>
</td>
</tr>
<tr>
<td>Date:
</td>
<td>
<input type="date" name="datedone" value="<?php print dbdate($row['datedone'],false)?>">
</td>
</tr>
<tr>
<td>Amount:
</td>
<td>
<input type="text" name="amount" size="10" value="<?php print $row['amount'] ?>">
</td>
</tr>
<tr>
<td>Transaction ID:
</td>
<td>
<input type="text" name="transactionid" size="30" value="<?php print $row['transactionid'] ?>"> (if available)
</td>
</tr>
<tr>
<td>Status:
</td>
<td>
<input type="radio" name="is_active" value = "Y" <?php if ($row['is_active'] == 'Y') {print " checked";} ?>> Active &nbsp;&nbsp;&nbsp;
<input type="radio" name="is_active" value = "N" <?php if ($row['is_active'] == 'N') {print " checked";} ?>> Canceled &nbsp; (if canceled, you must enter a reason) <br />
 cancellation reason <input type="text" name="note" size="50" value="<?php print $row['note'] ?>">
</td>
</tr>
<tr>
<td colspan="2" bgcolor = "#ffffcc" align="center">
<input type = "hidden" name="paymentid" value = "<?php  print $row['paymentid'] ?>">
<input type = "hidden" name="reconid" value = "<?php  print $row['reconid'] ?>">
<input type = "hidden" name="civicrmid" value = "<?php  print $civicrmid ?>">
<input type = "hidden" name="itemid" value = "<?php  print $itemid ?>">
<input type="submit" name="submit" value = "Save Changes">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="show_recon.php?civicrmid=<?php print $civicrmid ?>&itemid=<?php print $itemid ?>">Cancel</a>
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
