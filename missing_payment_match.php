<?php 
try {
    
    include_once("../../lib/common-init.php");
    include_once("../../lib/r_payment_tools.php");
    
    
 if (isset($_POST['submit']) ) {
     # collect and validate the data
    $transactionid = getinput('txn');
    if ($transactionid == null) {
        throw new EXCEPTION("I don't know which transaction to resolve.",ERROR_MINOR);
    }
    
    $sponsor = $_POST['sponsor'];
    $civicrmid = array_pop( explode("|", $sponsor) );
    if (filter_var($civicrmid, FILTER_VALIDATE_INT) === false) {
        throw NEW exception ("Invalid sponsor | $sponsor", ERROR_MAJOR);
    }
    
    $itemid = $_POST['child'];
    if ($itemid == '') {$itemid = $_POST['itemid']; }
    if (filter_var($itemid, FILTER_VALIDATE_INT) === false) {
        throw NEW exception ("Invalid Child | $itemid", ERROR_MAJOR);
    }
    
    
    
    debug ("Post variables are\n".dump_array($_POST));
    #  create the recon entry
    debug("creating sponsorship between sponsor $civicrmid and child $itemid");
    $reconid = get_reconid ($itemid, $civicrmid, true);
    if ($reconid === false) {
        throw NEW exception ("Failed to create recon entry |  itemid = $itemid, civicrmid = $civicrmid", ERROR_MAJOR);
    }
    
    #  update search table accordingly
    $res = do_sql("select * from r_paypal_unmatched where transaction_id = '$transactionid' ");
    $data = mysqli_fetch_assoc($res);
    debug ("r_paypal_unmatched row is \n", dump_array($data));
    get_search ($civicrmid, $data['name'], 'paypal', TRUE);
    
    #  store the payment record
    $load = load_a_paypal_row($reconid, $data );
    
    if ($load) {
        #  remove the unmatched row
        $res = do_sql("delete from r_paypal_unmatched where transaction_id = '$transactionid'");
    }
    #  log the action
    logit( "Manual paypal import","reconid = $reconid, transaction = $transactionid, payment date = {$data['datedone']}");  
    
    # create the payment rule
   
    $recur = getinput('recur');
    if ($recur <> '') {
        $amount = getinput('amount');
        $period = getinput('period');
        $dateend = strftime('%Y-%m-%d',strtotime(getinput('dateend')));
         if ($recur == 'N') {
             $res = do_sql("insert r_rules (reconid, type, period) values ('$reconid', 'sponsorship','none'"); 
        } else {
            if ($dateend == '1969-12-31') {
                $dateend = "null";            
            } else {
                $datedone = "'$datedone'";    
            }
            $res = do_sql("insert r_rules (reconid, type, period, dateend, amount) values ('$reconid', 'sponsorship','$period',$dateend,'$amount') on duplicate key update reconid = '$reconid'");
        }
    }
    
    #  end form processing    
}

# get the first row from the unmatched transactions
$sql = "select * from r_paypal_unmatched";
$res = do_sql($sql);
if (($res === false) or (mysqli_num_rows($res) == 0)) {
    throw new EXCEPTION("No unmatched transactions remaining.",ERROR_MINOR);
}
$skip = getinput('skip') +1;
debug ("skip is set to $skip");

#  skip over skipped rows
for ($i = 1; $i < $skip; $i ++  ) {
    $data = mysqli_fetch_assoc($res);
}
$data = mysqli_fetch_assoc($res);
$transactionid = $data['transaction_id'];

#  find potential civicrm matches to sponsor
$sponsor_list = '';
$sql = "select id, sort_name, email, modified_date from cvcontacts where sort_name is not null and sort_name not like '%@%' order by sort_name";
$res = do_sql($sql);
while ($row = mysqli_fetch_assoc($res) ) {
    $tmp = strftime('%m/%d/%y', strtotime($row['modified_date']) );
    $sponsor_list .= "<option value='{$row['sort_name']}, {$row['email']}, $tmp    |{$row['id']}'</option>\n";
}


#  find potential child name matches.
$select_children = '';
$sql = "select itemid,title from items order by title";
$res = do_sql($sql);
while ($row = mysqli_fetch_assoc($res) ) {
    $select_children .= "<option value='{$row['itemid']}'>{$row['title']}</option>\n";
}

?>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link href="../../css/style.css" rel="stylesheet" type="text/css">
<title>KOIKOI Administration</title>
<script type='text/javascript' src='ajax.js'></script>
</head>
<body>
<H2 align="center">Resolve an unmatched tranaction</H2>
<form action="" method = "post">
<Table width="80%" border="1" cellpadding="3" cellspacing="3" align="center">
<tr>
<th>Transaction Information</th>
</tr>
<tr>
<td>Payment by <?php print $data['name'] ?> &nbsp;&nbsp;(<?php print $data['email'] ?>)&nbsp;&nbsp; on <?php print us_date($data['datedone']) ?> &nbsp;for <?php print $data['gross'] ?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="missing_payment_match.php?skip=<?php print $skip ?>">Skip this one</a><br />
Address:  <span class="bold"><?php print $data['shipping_address'] ?></span>
</tr>
<tr><td>
Matching sponsor name is:  <input id="sponsor" name="sponsor" list="sponsors" size="60" type="text" autocomplete="off" onblur="doWork(this.value); return false;">
<datalist id="sponsors">
<?php print $sponsor_list ?>
</datalist>
<br />&nbsp;<br />
<div id="ajax_text">

</div>

Matching child name is:  <select name="child">
<option value="">Choose...</option>
<?php print $select_children ?>
</select>
</td>
</tr>
<tr>
<td>

Is this a recurring payment? <input type = "checkbox" name="recur" value="Y"> Yes
&nbsp;&nbsp;&nbsp;
<input type = "checkbox" name="recur" value="Y"> No
<br />&nbsp;<br />
Amount to pay:  <input type="text" size="12" max_length="10" name="amount">
<br />&nbsp;<br />
Payment period: <select name="period">
<option value= "">None</option>
<option value= "month">Every month</option>
<option value= "quarter">Every 3 months</option>
<option value= "semi">Semi-annually</option>
<option value= "annual">Annual</option>
</select>
<br />&nbsp;<br />
End date (if any) <input type="date" name="dateend" min="<?php dbdate( time() )?>">
</td>
</tr>
<tr><td align = "center" bgcolor = "FFFFCC">
<input type="hidden" name="txn" value = "<?php print $transactionid ?>">
<a href="index.php">Go Home</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="submit" value="Record Match">
</td></tr>
</Table>
</form>
<?php
} catch(Exception $err) {
	$trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
	logerr($err->getMessage(),$err->getCode(),$trace);
}

?>
