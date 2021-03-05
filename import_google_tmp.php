<?php 
/*
 * This reads the r_recon_incoming table to set up the new appliction.   
 * 
 * It should only be needed once when we're ready to reetire the recon spreadsheet.
 * 
 * Hence, this code is not as optimized and tested as the other ones.
 */

include "../../lib/common-init.php";
include "../../lib/write_notes.php";
include "../../lib/r_payment_tools.php" ;

# set up an input cursor

$inres = do_sql("select * from r_recon_incoming");

#  the input loop
while ($inrow = mysqli_fetch_assoc($inres)) {
#    print_r($inrow);  print "\n\n";
    #  make all data mysql safe
    foreach (array_keys($inrow) as $item) {
        if ( strpos($inrow[$item],"'") > 0) {
            $inrow[$item] = trim(addslashes($inrow[$item])) ;
        }
    }
#    print_r($inrow);  print "\n\n";
    
    #  create the recon table entry
    $reconid = get_reconid($inrow['itemid'], $inrow['civicrmid'], true);
    if ($reconid == '') { die ("failed to get a recon id"); }
    
    
    #  populate the names table
    $tmp = get_names ($inrow['itemid'], $inrow['name'], 'spreadsheet', true );
    
    #  populate the search table
    $tmp = get_search ($inrow['civicrmid'], $inrow['sponsor'], 'spreadsheet', true );

    #  create the payment rule
    #  convert amount into a number
    if ($inrow['amount'] <> '') {
        $tmp = str_replace('$','', str_replace(',', '', $inrow['amount'])) ;
    } else {
        $tmp = '';
    }
    $sql = "insert r_rules (reconid, type, period, amount) values ('$reconid', 'sponsorship','{$inrow['frequency']}', '$tmp' )";
    $sql .= " on duplicate key update reconid = '$reconid' ";
    $res = do_sql($sql);
    
    #  insert notes
    #  merge old notes together
    $tmp = trim($inrow['notes'] . " " . $inrow['notes2'] . " " . $inrow['notes3'] . " " . $inrow['notes4'] . " " . $inrow['notes5']);
    if ($tmp <> '') {
        $data = array (
            'note' => $tmp,
            'reconid' => $reconid,
        );
        $noteid = write_notes($data);
    }
    
    print ".";
/*    
    #  insert last payment information if one exists
    if ($inrow['last'] <> '1969-12-31') {
        #  convert amount into a number
        if ($inrow['last'] <> '') {
            $tmp = "'" . str_replace('$','', str_replace(',', '', $inrow['last'])) . "'" ;
        } else {
            $tmp = null;
        }
        $sql = "insert r_payments (reconid, type, datedone,source, amount,transactionid,is_active) ";
        $sql .= " values ('{$inrow['reconid']}', 'sponsorship', $tmp,'{$inrow['reconid']}'  "
    }
*/    
}  # end of reading the incoming table

print "\n";

?>