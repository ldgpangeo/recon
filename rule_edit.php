<?php 
try {

    include_once("php_header.php");
#    include_once("../../lib/r_payment_tools.php");

    $reconid = getinput('reconid');
    if ($reconid == null) {
        throw NEW exception("I don't know which sponsorship to update.",ERROR_MAJOR);
    }
    # get info for this sponsorship
    $sql = "select * from sponsorship_summary where reconid = '$reconid'";
    $res = do_sql($sql);
    $info = mysqli_fetch_assoc($res);
    
    $sql = "select * from r_rules where reconid = '$reconid'";
    $res = do_sql($sql);
    $rules = array ();
    while ($row = mysqli_fetch_assoc($res)) {
            array_push($rules, $row);
    }
            
    
    
    #  ----------------------------  Start Form processing  -----------------------------------
    if ((isset($_POST['submit'])) and ($_POST['submit'] == "Save rules") ) {
        debug ("Post variables are\n". dump_array($_POST));
        $items = explode (",",$_POST['idlist']);
        foreach ($items as $seq) {
            $type = clean($_POST['type_'.$seq]);
            $period = clean($_POST['period_'.$seq]);
            $amount = addslashes(clean($_POST['amount_'.$seq]));
            $dateend = clean($_POST['dateend_'.$seq]);
            if ($dateend == "") { $tmp = "null"; } else { $tmp = "'$dateend'"; }
            $sql = '';
            if (filter_var($seq, FILTER_VALIDATE_INT)) {
                #  is delete set?
                debug ("testing {$_POST['del_'.$seq]}");
                if ( $_POST['del_'.$seq] == 'Y' ) {
                    $sql = "delete from r_rules where ruleid = '$seq'";
                } else {
                    #  update an existing
                      $sql = "update r_rules set type = '$type', period = '$period' , amount = '$amount', dateend = $tmp where ruleid = '$seq'";
                }
            } else {
                # create a new one
                if (( $amount <> '' ) and ($period <> '')) {
                    $sql = "insert r_rules (reconid, type, period, dateend, amount) values ('$reconid', '$type', '$period', $tmp, '$amount' ) ";
                }
            }
            if ($sql <> '') {
                $res = do_sql($sql);
                
            }
            
        }
        logit ("payment_rule_update", "login = $login, reconid = {$reconid}");
        $redirect = "show_recon.php?civicrmid={$info['civicrmid']}&itemid={$info['itemid']}";
    }
    
    #  -----------------------------  end Form processing  ------------------------------------
    
    
    $title = "Edit Payment rules";
 #   $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";
    
?>
<form action = "", method = "POST">
<h2 align="center">Payment Rules for <?php print $info['sponsor']?> sponsoring <?php print $info['child']?></h2>
<table width="70%" cellspacing="3" cellpadding = "3" border = "1" align='center'>
<tr  bgcolor="#ffffcc"><th colspan="5">Edit existing rules</th></tr>
<tr  bgcolor="#ffffcc" >
<th>Type</th>
<th>Period</th>
<th>Amount</th>
<th>End date</th>
<th>Delete</th>
</tr>
<?php 
$ids = array ();
foreach ($rules as $rule) { 
$i = $rule['ruleid'];
array_push($ids, $i);
    ?>
<tr>
<td>
<select name="type_<?php print $i ?>">
<option <?php if ($rule['type'] == "sponsorship") { print "selected";} ?>>sponsorship</option>
<option <?php if ($rule['type'] == "nest egg") { print "selected";} ?>>nest egg</option>
<option <?php if ($rule['type'] == "spending") { print "selected";} ?>>spending</option>
<option <?php if ($rule['type'] == "other") { print "selected";} ?>>other</option>
</select>
</td>
<td>
<select name="period_<?php print $i ?>">
<option <?php if ($rule['period'] == "monthly") { print "selected";} ?>>monthly</option>
<option <?php if ($rule['period'] == "annually") { print "selected";} ?>>annually</option>
<option <?php if ($rule['period'] == "quarterly") { print "selected";} ?>>quarterly</option>
<option <?php if ($rule['period'] == "semiannually") { print "selected";} ?>>semiannually</option>
</select>
</td>
<td>
<input type="text" name="amount_<?php print $i ?>" value="<?php print $rule['amount'] ?>" size="10">
</td>
<td>
<input type="date" name="dateend_<?php print $i ?>" value="<?php print $rule['dateend'] ?>">
</td>
<td><input type="checkbox" name="del_<?php print $i ?>" value = "Y"> Del.
</td></tr>
<?php } ?>
<tr>
<tr  bgcolor="#ffffcc"><th colspan="5">Add new rules</th></tr>
<?php foreach (array("a","b") as $i) {
    array_push($ids, $i);
    ?>
<tr>
<td>
<select name="type_<?php print $i ?>">
<option  >sponsorship</option>
<option >nest egg</option>
<option >spending</option>
<option >other</option>
</select>
</td>
<td>
<select name="period_<?php print $i ?>">
<option >monthly</option>
<option >annually</option>
<option >quarterly</option>
<option >semiannually</option>
</select>
</td>
<td>
<input type="text" name="amount_<?php print $i ?>" size="10">
</td>
<td>
<input type="date" name="dateend_<?php print $i ?>">
</td>
<td>&nbsp;
</td></tr>


<?php } ?>
<td colspan="5" bgcolor="#ffffcc" align="center">
<?php $tmp = implode(",",$ids); ?>
<input type ="hidden" name="civicrmid" value = "<?php print $info['civicrmid'] ?>">
<input type ="hidden" name="itemid" value = "<?php print $info['itemid'] ?>">
<input type ="hidden" name="idlist" value = "<?php print $tmp ?>">
<a href="show_recon.php?civicrmid=<?php print $info['civicrmid']?>&itemid=<?php print $info['itemid']?>">Cancel</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="submit" value="Save rules">&nbsp;

</td>
</tr>
<tr>
<th colspan="5" align="center">
Enter an end date only if this payment has a specific ending date.<br />
Each payment type can have only one rule.
</th>
</tr>
</table>
</form>
<?php
} catch(Exception $err) {
    $trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
    logerr($err->getMessage(),$err->getCode(),$trace);
    }
    
    ?>
