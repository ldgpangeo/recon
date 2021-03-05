<?php 
try {

    include_once("php_header.php");
#    include_once("../../lib/r_payment_tools.php");
    
    $title = "Show Missing Payments";
 #   $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";
    
    #  collect list of missing payments
    $sql = "select * from recon_data where reconid not in (select distinct reconid from r_payments where type = 'sponsorship') order by sponsor,child";
    $res = do_sql($sql);
    $missing_payments = array();
    while ($row = mysqli_fetch_assoc($res)) {
        array_push ($missing_payments, $row);
    }
    
    
?>
<H2 align="center">Sponsorships with NO Payments</H2>
<table width="70%" border = "1" cellspacing="3" cellpadding="3" align ="center">
<tr>
<th width="40%">Sponsor</th>
<th width="40%">child</th>
<th width="40%">Action</th>
</tr>
<?php foreach ($missing_payments as $row) {?>
<tr>
<td align="center"><?php print $row['sponsor']?></td>
<td align="center"><?php print $row['child']?></td>
<td align="center"><a href="show_recon.php?civicrmid=<?php print $row['civicrmid']?>&itemid=<?php print $row['itemid']?>" class="myBlue">Visit</a></td>
</tr>
<?php } ?>
<tr><th colspan="3"><a href="index.php" class="myGreen">Return to main page</a></th></tr>
</table>
<?php
} catch(Exception $err) {
    $trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
    logerr($err->getMessage(),$err->getCode(),$trace);
    }
    
    ?>
