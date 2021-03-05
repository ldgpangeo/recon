<?php
try {
    
    include_once("php_header.php");
    include_once("../../lib/r_payment_tools.php");
    
    $title = "Show Reconciliation";
    $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";
    
    #  ------------------------  process a posted payment  ---------------------------------
    if ( (isset($_POST['submit'])) and ($_POST[submit] == "Save payment") ) {
        #  parse form variables
        debug ("Processing form post\n".dump_array($_POST) );
        $errors = '';
        $in = form_validate('addpayment',$errors);
        debug ("cleaned form input is \n". dump_array($in) );
        $civicrmid = $in['civicrmid'];
        $itemid = $in['itemid'];
        $reconid = get_reconid($itemid, $civicrmid, false);
        if ($reconid == false) {
            $errors .= "Unable to find the reconciliation entry<br />";
        }
        
        if ($errors <> '') {
            throw NEW exception ("Bad data.<br />$errors", ERROR_MINOR);
        }
        $sql = "insert r_payments ( reconid, type, source, datedone, amount, is_active, transactionid ) values ";
        $sql .= "('$reconid', '{$in['type']}', '{$in['source']}', '{$in['datedone']}', '{$in['amount']}', 'Y', '{$in['transactionid']}' )";
        $res = do_sql($sql);
    }
    #  -------------------------  end of post processing   ---------------------------------
    debug ("Post variables are\n". dump_array($_POST));
    
    $civicrmid = html_entity_decode(getinput('civicrmid', null));
    if ($civicrmid == null) {
        $civicrmid = html_entity_decode(getinput('civicrmid2', null));
    }
    if ($civicrmid == null) {
        throw NEW exception("No sponsor name.", ERROR_MINOR);
    }
    $civicrmid = array_pop( explode("|",$civicrmid ) );
    if (filter_var($civicrmid, FILTER_VALIDATE_INT) === false) {
        throw NEW exception ("Invalid sponsor | $sponsor", ERROR_MINOR);
    }
    $itemid = html_entity_decode(getinput('itemid', ""));
    if ($itemid == "") {
        $itemid = html_entity_decode(getinput('itemid2', ""));
    }
    if ($itemid == "") {
        throw NEW exception("No child name.", ERROR_MINOR);
    }
    debug ("itemid is now $itemid");
    $itemid = array_pop( explode("|",$itemid ) );
    debug ("itemid is cleaned to ");
    if (filter_var($itemid, FILTER_VALIDATE_INT) === false) {
        throw NEW exception ("Invalid Child | $sponsor", ERROR_MINOR);
    }
    
    # get the recon id
    $reconid = get_reconid( $itemid, $civicrmid, false);
    if ($reconid == false) {
        throw NEW exception ("Can not find the recon information | reconid is $reconid, itemid is $itemid and civicrmid is $civicrmid", ERROR_MAJOR);
    }
    
    # collect sponsor information
    $res = do_sql("select * from cvcontacts where id = '$civicrmid' ");
    $sponsor_data = mysqli_fetch_assoc($res);
    # Collect additional email addresses
    $res = do_sql("select * from $cv.civicrm_email where contact_id = '$civicrmid' and is_primary=0 and on_hold = 0");
    $sponsor_emails = array ();
    while ($row = mysqli_fetch_assoc($res)) {
        array_push($sponsor_emails, $row);
    }
    #  Colect additional phone numbers
    $res = do_sql("select * from $cv.civicrm_phone where contact_id = '$civicrmid' and is_primary=0");
    $sponsor_phones = array ();
    while ($row = mysqli_fetch_assoc($res)) {
        array_push($sponsor_phones, $row);
    }
    #  collect sponsor aliases
    $res = do_sql("select * from r_search where civicrmid = '$civicrmid'");
    $sponsor_aliases = array();
    while ($row = mysqli_fetch_assoc($res) ) {
        if (! isset($sponsor_aliases[$row['source']]) ) { $sponsor_aliases[$row['source']] = array (); }
        array_push($sponsor_aliases[$row['source']], $row);
    }
    
    
    # collect child information
    $sql = "select i.* ,d.label groupname, case when i.monthly is null then g.monthly else i.monthly end final_monthly, ";
    $sql .= "case when c.koikoi = 'Y' then 'Koi Koi orphan' else '' end koikoi, ";
    $sql .= "case when i.yearly is null then  g.yearly else i.yearly end final_yearly from items i ";
    $sql .= "left join dictionary d on area='groups' and d.setting = i.groupid left join groups g on i.groupid = g.groupid ";
    $sql .= "left join r_child_info c on c.itemid = i.itemid ";
    $sql .= " where i.itemid = '$itemid' ";
    $res = do_sql($sql);
    $child_data = mysqli_fetch_assoc($res);
    
    #  collect child aliases
    $res = do_sql("select * from r_names where itemid = '$itemid'");
    $child_aliases = array();
    while ($row = mysqli_fetch_assoc($res) ) {
        if (! isset($child_aliases[$row['source']]) ) { $child_aliases[$row['source']] = array (); }
        array_push($child_aliases[$row['source']], $row);
    }
    
    
    #does this child have other sponsors?
    $sql = "select reconid, itemid, civicrmid, sponsor from recon_data where itemid = '$itemid' and civicrmid <> '$civicrmid' ";
    $res = do_sql($sql);
    $other_sponsors = array();
    while ($row = mysqli_fetch_assoc($res)) {
        array_push($other_sponsors, $row);
    }
    if ( count($other_sponsors) > 0 ) {$show_others = true; } else {$show_others = false; }
    
    # collect payment history
    $show_payments = false;
    $payment_data = array();
    $res = do_sql("select * from r_payments where reconid = '$reconid' and is_active = 'Y' order by datedone desc limit 5");
    while ( $row = mysqli_fetch_assoc($res) ){
        array_push($payment_data, $row);
    }
    if ( count($payment_data) > 0 ) { $show_payments = true; }
    
    # Collect the payment rules
    $rules = array();
    $res = do_sql("select * from r_rules where reconid = '$reconid'");
    while ($row = mysqli_fetch_assoc($res)) {
        $rules[$row['type']] = $row;
    }
    if (count($rules) > 0) {$show_rules = true; } else { $show_rules = false; }
    
    #  collect the last_payment info
    $res = do_sql("select * from last_payments where reconid = '$reconid' and is_active = 'Y'");
    $last_payments = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $last_payments[$row['type']] = $row;
    }
    
    # collect notes
    $show_notes = false;
    $show_alerts = false;
    $notes_data = array();
    $alerts_data = array ();
    $sql = "select * from r_notes where reconid = '$reconid' and effective_end_ts is null and is_active = 'Y' order by effective_start_ts desc ";
    $res = do_sql($sql);
    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['is_alert'] == 'Y') {
            array_push($alerts_data, $row);
            $show_alerts = true;
        } else {
            array_push($notes_data, $row);
            $show_notes = true;
        }
    }
    
    ?>
<body>
<H2 align="center"><?php print $sponsor_data['sort_name']?> sponsoring <?php print $child_data['title']?></H2>
<form action="" method = "post">
<input type="hidden" name="civicrmid" value="<?php print $civicrmid ?>">
<input type="hidden" name="itemid" value="<?php print $itemid ?>">
<Table width="80%" border="1" cellpadding="3" cellspacing="3" align="center">
<?php if ($show_alerts){ ?>
<tr><td>
<h2>Alerts</h2>
<table cellspacing = "3" cellpadding = "3" border = "1" align="center">
<?php 
$i = 0;
foreach ($alerts_data as $note) {
$i ++; 
?>
<tr>
<td valign="top">
<?php print us_date($note['datedone'])?> <br />
<?php print $note['author']?>
</td>
<td valign="top" bgcolor = "#ff7777">
<?php 
$tmp = stripslashes($note['note']);
if (strlen($tmp) <= 80)  {
    $txt = $tmp;
} else {
    $short = substr($tmp, 0, 80);
    $txt =  $short . " <a href=\"javascript:ReplaceDiv('alert_$i','$tmp')\" >more...</a>";
    
}
?>
<span style="font-weight: bold;font-size: 18px;"><pre><div id="alert_<?php print $i ?>"><?php print $txt ?></div></pre></span></pre>
</td>
<td><a href="note_edit.php?reconid=<?php print $reconid?>&noteid=<?php print $note['noteid']?>">EDIT alert</a>
</td>
</tr>
<?php } # end notes data?>
</table>
</td></tr>

<?php } #  end alerts?>
<tr><td><h3>Recent payments: (<?php print $reconid ?>)</h3><br />
<table align="center" width="80%"  border="1" cellpadding="3" cellspacing="3">
<?php if (! $show_payments) {?>
<tr><td>
<p align="center">There are no logged payments available.</p>
</td></tr>
<?php } else { ?>
<table align="center" width="80%"  border="1" cellpadding="3" cellspacing="3">
<tr>
<th width="20%">Type</th>
<th width="20%">Source</th>
<th width="15%">Date</th>
<th width="15%">Amount</th>
<th width="15%">ID</th>
<th width="20%">Action</th>
</tr>
<?php  foreach ($payment_data as $row) {?>
<tr>
<td align="center"><?php print $row['type']?></td>
<td align="center"><?php print $row['source']?></td>
<td align="center"><?php print us_date($row['datedone'])?></td>
<td align="center"><?php print $row['amount']?></td>
<td align="center"><?php print $row['transactionid']?></td>
<td align="center"><a href="payment_edit.php?paymentid=<?php print $row['paymentid']?>&civicrmid=<?php print $civicrmid?>&itemid=<?php print $itemid?>">Edit</a></td>
</tr>
<?php }  # end looping through data?>
<?php }  #  end show payments ?>
<tr><td colspan="6">
<p align="center">
<?php if (! $show_rules) {?>
There are no payment rules defined for this sponsorship.
<?php } else {
    $first = "Payment Rules:  &nbsp;"; 
    foreach (array_keys($rules) as $key) {
        print $first . $key . ": &nbsp; ". $rules[$key]['period'] . ": &nbsp; $" .$rules[$key]['amount'];
        if ($rules[$key]['dateend'] <> null) { print ": &nbsp; until " . us_date($rules[$key]['dateend']) ; }
#        debug ("collecting next payment for {$last_payments[$key]['datedone']} with period {$rules[$key]['period']}\n".dump_array($last_payments));
        $next = next_payment_date($last_payments[$key]['datedone'], $rules[$key]['period']);
        if (time() > strtotime($next)) { $late = " LATE"; } else {$late = '';}
        if ($next <> false ) {print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Next payment: " . us_date($next). $late;}
        $first = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }
    
    ?>

<?php }  # end show rules?>
&nbsp;&nbsp;
(<a href="rule_edit.php?reconid=<?php print $reconid ?>">Edit</a>)</p>
</td></tr>
</table>
<P>Add a payment:
&nbsp;&nbsp; &nbsp;  <span style = "background-color: #ddd;"> Type: <select name="type">
<option>sponsorship</option>
<option>nest egg</option>
<option>spending</option>
<option>other</option>
</select>
&nbsp;&nbsp; &nbsp;   
Source: <select name="source">
<option value="">Choose...</option>
<option value="quickbooks">QuickBooks</option>
<option value="paypal">PayPal</option>
<option value="check">Check</option>
</select>
&nbsp;&nbsp; &nbsp;   
Date: <input type="date" name="datedone">
&nbsp;&nbsp; &nbsp;   
Amount: <input type="text" name="amount" size="10">
&nbsp;&nbsp; &nbsp; 
Transactionid: <input type="text" name="transactionid" size="20">
<input   
<input type="submit" name="submit" value="Save payment">&nbsp;
</span>

</P>
</td>
</tr>
<tr><td>
<h3>Notes</h3>
<table cellspacing = "3" cellpadding = "3" border = "1" width = "80%" align = "center" >
<?php foreach ($notes_data as $note) {?>
<tr>
<td valign="top">
<?php print us_date($note['effective_start_ts'], false)?> <br />
<?php print $note['author']?>
</td>
<td valign="top">
<?php 
if (strlen($note['note']) > 80) {
    if (getinput("more") == $note['noteid']) {
        #  show everything
        $tmp = $note['note'];
        $link = " <a href=\"show_recon.php?civicrmid=$civicrmid&itemid=$itemid\">Show less</a><br />";
    } else {
        $tmp = substr($note['note'], 0, 80) . " ...";
        $link = " <a href=\"show_recon.php?civicrmid=$civicrmid&itemid=$itemid&more={$note['noteid']}\">Show more </a><br />";
    } 
} else {
    $tmp = $note['note'];
    $link = "";
}
?>
<pre><?php print $tmp ?></pre>
</td>
<td valign="top"><?php print $link ?><a href="note_edit.php?reconid=<?php print $reconid?>&noteid=<?php print $note['noteid']?>">Edit note</a>
</td>
</tr>
<?php } # end notes data?>
</table>
<a href="note_edit.php?reconid=<?php print $reconid?>">NEW NOTE</a>
</td></tr>
<tr>
<td>
<h3>Sponsor Information 
(<a href="https://www.thegivingcircle.org/wordpress/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&reset=1&cid=<?php print $civicrmid?>" target="_blank"><?php print $civicrmid?></a>)</h3>
<table width="90%" cellspacing="3" cellpadding="3" align="center">
<tr>
<td>
Address:<br />
<?php if ($sponsor_data['street_address'] <> '') {print "&nbsp;&nbsp;&nbsp;".$sponsor_data['street_address']."<br />";}?>
<?php if ($sponsor_data['city'] <> '') {print "&nbsp;&nbsp;&nbsp;".$sponsor_data['city'].", ".$sponsor_data['state']."  ".$sponsor_data['postal_code']."<br />";}?>
</td>
<td>
Email:<br />
&nbsp;&nbsp;&nbsp;<?php print $sponsor_data['email']?> (Preferred) <br />
<?php foreach ($contact_emails as $item) {
    print "&nbsp;&nbsp;&nbsp;".$item['email']."<br />";
}
?>
</td>
<td>
phone:<br />
&nbsp;&nbsp;&nbsp;<?php print $sponsor_data['phone']?> (Preferred) <br />
<?php foreach ($contact_phones as $item) {
    print "&nbsp;&nbsp;&nbsp;".$item['phone']."<br />";
}
?>
</td>
</tr>
</table>
<hr noshade width="80%" align="center"><br />Aliases:
<?php foreach (array_keys($sponsor_aliases) as $source) {
    print $source. ": ";
    $sep = "";
    foreach ($sponsor_aliases[$source] as $row) {
        print $sep . $row['name'];
        if ($sep == '') { $sep = ", &nbsp;"; }
    }
    print " &nbsp;&nbsp;&nbsp;&nbsp";
}
    
    ?>
(<a href="sponsor_alias_edit.php?civicrmid=<?php print $civicrmid ?>&itemid=<?php print $itemid?>">Edit</a>)
</td>
</tr>
<tr>
<td>
<h3>Child Information (<a href="https://www.thegivingcircle.org/childsponsor/admin/child_edit.php?childid=<?php print $itemid?>&gid=<?php print $child_data['groupid']?>" target="_blank"><?php print $itemid?></a>)

<?php if ($show_others) {
    print "&nbsp;&nbsp;&nbsp;Also sponsored by: ";
    foreach ($other_sponsors as $item) {
        print "<a href=\"show_recon.php?itemid=$itemid&civicrmid={$item['civicrmid']}\">{$item['sponsor']}</a>&nbsp;&nbsp";
    }
 }?>
</h3>

<table width="90%" cellspacing="3" cellpadding="3" align="center">
<tr>
<td>
Group: <?php print $child_data['groupname'] ?>
</td>
<td>
<?php print $child_data['koikoi'] ?>
</td>
<td>
Payment:  Monthly: <?php print $child_data['final_monthly']?> Yearly: <?php print $child_data['final_yearly']?>
</td>
<td>
Maximum sponsors: <?php print $child_data['max_sponsors']?>
</td>
</tr>
</table>
<hr noshade width="80%" align="center"><br />Aliases:
<?php foreach (array_keys($child_aliases) as $source) {
    print $source. ": ";
    $sep = "";
    foreach ($child_aliases[$source] as $row) {
        print $sep . $row['name'];
        if ($sep == '') { $sep = ", &nbsp;"; }
    }
    print " &nbsp;&nbsp;&nbsp;&nbsp";
}
    
    ?>
(<a href="child_alias_edit.php?civicrmid=<?php print $civicrmid ?>&itemid=<?php print $itemid?>">Edit</a>)
</td>
<tr>
<td align="center" bgcolor="#ffffcc">
<a href="index.php" class="MyGreen">Home</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="show_detail.php" class="MyGreen">Different sponsorship</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="show_recon.php?civicrmid=<?php print $civicrmid ?>&itemid=<?php print $itemid ?>"  class="MyGreen">Refresh this page</a>
</td>
</tr>

</Table>
</form>


<?php
} catch(Exception $err) {
    $trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
    logerr($err->getMessage(),$err->getCode(),$trace);
    }
    
    ?>
    