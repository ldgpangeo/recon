<?php 
try {

    include_once("php_header.php");
    include_once("../../lib/r_payment_tools.php");
    
    $title = "Reconciliation Home";
 #   $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";

# collect list of Alerts
    $sql = "select n.*, r.reconid, r.itemid, r.civicrmid, r.child, r.sponsor ";
    $sql .=" from r_notes n, recon_data r where n.reconid = r.reconid and r.is_active = 'Y'";
    $sql .=" and n.is_alert = 'Y' and n.is_active = 'Y' and n.effective_end_ts is null ";
    $sql .=" order by n.effective_start_ts desc";
    $res = do_sql($sql);
    $alert_data = array();
    while ($row = mysqli_fetch_assoc($res)) {
        array_push ($alert_data, $row);
    }

#  collect list of missing payments
    $sql = "select * from recon_data where reconid not in (select distinct reconid from r_payments where type = 'sponsorship')";
    $res = do_sql($sql);
    $missing_payments = array();
    while ($row = mysqli_fetch_assoc($res)) {
        array_push ($missing_payments, $row);
    }
#  collect list of late payments
    $sql = "select d.*, p.type, p.datedone, p.source, p.amount, p.transactionid, r.period from recon_data d ";
    $sql .= " left join r_rules r on r.reconid = d.reconid and r.type = 'sponsorship', ";
    $sql .= " last_payments p ";
    $sql .= "where d.reconid = p.reconid and p.type='sponsorship' and p.is_active = 'Y'";
    $res = do_sql($sql);
    $late_payments = array ();
    $now = time();
    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['period'] <> '') { 
            $next_payment = next_payment_date($row['datedone'],$row['period']);
            if ($now > (strtotime($next_payment)+86400)) {
                $row['next_date'] = $next_payment;
                array_push($late_payments, $row);
            }
        } else {
            array_push($late_payments, $row);
        }

    }
    
#  Collect count of unimported data
    $sql = "select count(*) total from r_paypal_unmatched";
    $res = do_sql($sql);
    $unimported = mysqli_result($res, 0, 'total');
    
?>
<?php $count = count($alert_data);
if ($count > 0) {$tmp = $count; } else {$tmp = "no";}
?>
<h2>There are <?php print $tmp?> active alerts</h2>
<blockquote>
<?php 
if ($count > 5) { $max = 5; } else {$max = $count;}
for ($i = 1; $i <= $max; $i ++) {
    $row = array_shift($alert_data);
    print "<h3>Sponsor: {$row['sponsor']} Child: {$row['child']} " . substr($row['note'],0,40);
    print " &nbsp;&nbsp;<a href=\"show_recon.php?civicrmid={$row['civicrmid']}&itemid={$row['itemid']}\" class=\"myBlue myButtons\">Visit</a></h3>";
}
?>
</blockquote>
<h2>There are <?php print count($missing_payments)?> Sponsorships with no payments <a href="show_missing_payments.php" class="myBlue myButtons">Visit</a></h2>

<h2>There are <?php print count($late_payments)?> overdue payments <a href="show_late_payments.php" class="myBlue myButtons">Visit</a></h2>

<h2>There are <?php print $unimported ?> PayPal records that need repair. <a href="missing_payment_match.php" class="myBlue myButtons">Visit</a></h2>

<p><a href="show_detail.php" class="myGreen myButtons">Find a sponsorship</a></p>

<p><a href="import_paypal.php" class="myGReen myButtons">Import a Paypal Report (CSV)</a>  </p>

<?php
} catch(Exception $err) {
    $trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
    logerr($err->getMessage(),$err->getCode(),$trace);
    }
    
    ?>
