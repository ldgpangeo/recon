<?php 
try {

    include_once("php_header.php");
#    include_once("../../lib/r_payment_tools.php");
    
    $title = "Edit Sponsor Aliases";
 #   $header = "<script type='text/javascript' src='ajax.js'></script>";
    
    $itemid = getinput("itemid");
    $civicrmid = getinput("civicrmid");
    if ($civicrmid == null) {
        throw NEW exception ("Unknown sponsor.",ERROR_MAJOR);
    }
    
$ids = array();
    #  -----------------------------  Start Form Processing  --------------------------
if ((isset($_POST['submit'])) and ($_POST['submit'] == "Save Alias Changes") ) {
    debug ("Post variables are\n". dump_array($_POST));
    $items = explode (",",$_POST['idlist']);
    foreach ($items as $seq) {
        $source = addslashes(clean($_POST['source_'.$seq]));
        $name = addslashes(clean($_POST['name_'.$seq]));
        $sql = '';
        if (filter_var($seq, FILTER_VALIDATE_INT)) {
            #  is delete set?
            debug ("testing {$_POST['del_'.$seq]}");
            if ( $_POST['del_'.$seq] == 'Y' ) {
                $sql = "delete from r_search where searchid = '$seq'";
            } else {
                #  update an existing
                $sql = "update r_search set source = '$source', name = '$name' where searchid = '$seq'";
            }
        } else {
            # create a new one
            if (($source <> '') and ($name <> '')) {
                $sql = "insert r_search (civicrmid, source, name) values ('$civicrmid', '$source', '$name') ";
            }
        }
        if ($sql <> '') {
            $res = do_sql($sql);
            
        }
         
    }
    logit ("Sponsor_alias_update", "login = $login, civicrm ID = $civicrmid");
    $redirect = "show_recon.php?civicrmid=$civicrmid&itemid=$itemid";
}
    #  ------------------------------  End Form Processing  ---------------------------

$res = do_sql("select * from r_search where civicrmid = '$civicrmid' order by source");
$aliases = array();
while ($row = mysqli_fetch_assoc($res) ) {
    array_push($aliases, $row);
}
include "page_header.php";

?>
<form action = "" method = "POST">
<h2 align="center">Edit Search Aliases for Sponsors</h2>
<table width = "650px" cellspacing = "3" cellpadding = "3" align = "Center" border = "1">
<tr bgcolor = "#ffffcc">
<th width="20%">Source
</th>
<th width="65%">Name
</th>
<th width="15%">Delete
</th>

</tr>
<?php foreach ($aliases as $row) {
 #   debug ("Processing row\n".dump_array($row));
    $seq = $row['searchid'];
    array_push($ids, $seq);
    ?>
<tr>
<td> 
<select name="source_<?php print $seq ?>">
<option value="">Choose...</option>
<option <?php if ($row['source'] == 'quickbooks') {print " selected";} ?> value="quickbooks" >QuickBooks</option>
<option <?php if ($row['source'] == 'paypal') {print " selected";} ?> value="paypal">PayPal</option>
<option <?php if ($row['source'] == 'spreadsheet') {print " selected";} ?> value="spreadsheet">Spreadsheet</option>
</select>
</td>
<td><input type="text" size="60" name="name_<?php print $seq ?>" value = "<?php print $row['name'] ?>">
</td>
<td><input type="checkbox" name="del_<?php print $seq ?>" value = "Y"> Del.
</td>
</tr>
<?php  } # end loop through aliases ?>
<tr>
<td colspan="3" align="center"  bgcolor = "#ffffcc">Add New Aliases
</td>
</tr>
<?php foreach (array("a","b","c") as $seq) {
    array_push($ids, $seq);
    ?>
<tr>
<td> 
<select name="source_<?php print $seq ?>">
<option value="">Choose...</option>
<option  value="quickbooks" >QuickBooks</option>
<option  value="paypal">PayPal</option>
<option  value="spreadsheet">spreadsheet</option>
</select>
</td>
<td><input type="text" size="60" name="name_<?php print $seq ?>" value = "">
</td>
<td>&nbsp;
</td>
</tr>
<?php  } # end loop through aliases ?>
<tr>
<td colspan = "3" bgcolor = "#ffffcc" align="center"  bgcolor = "#ffffcc">
<?php $tmp = implode(",",$ids); ?>
<input type ="hidden" name="civicrmid" value = "<?php print $civicrmid ?>">
<input type ="hidden" name="itemid" value = "<?php print $itemid ?>">
<input type ="hidden" name="idlist" value = "<?php print $tmp ?>">
<input type="submit" name="submit" value = "Save Alias Changes">
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
