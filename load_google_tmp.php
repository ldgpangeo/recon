<?php 
/*
 *   This opens the google recon spreadsheet export file and loads the raw data into the paypal table.
 */

require ("../../lib/common-init.php");

$infile = '/home/geoffrion15983/recon.csv';

$fhdl = fopen($infile, 'r');
$rowct = 0;
$data = array();

while (($row = fgetcsv($fhdl, 0,",")) !== false ) {
    $rowct ++;
    if ($rowct > 3) {
        $row[11] = strftime('%F', strtotime($row[11]));
        $row[14] = strftime('%F', strtotime($row[14]));
    }
    array_push($data, $row);
}
fclose($fhdl);
print "csv has $rowct rows \n";

# drop the first three rows
$junk = array_shift($data);
$junk = array_shift($data);
$junk = array_shift($data);

print_r($data[0]); print "\n\n";

#die ("debug stop");

foreach ($data as $row) {
#    print_r($row);
#    print "\n\n";
    $coltmp = array();
    foreach ($row as $col) {
        if ( strpos($col,"'") > 0) {
            $col = addslashes($col);
        } 
        array_push($coltmp, $col);
    }
#    print_r($coltmp); print "\n\n";
    
    $tmp = implode ("','", $coltmp);
    $sql = "insert r_recon_incoming values ( '$tmp' )";
    print "$sql\n\n";
    do_sql($sql);
}

?>