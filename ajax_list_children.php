<?php 
try {
include ("../../lib/common-init.php");
$tmp = urldecode($_GET['choice']);
$civicrmid = array_pop( explode("|",$tmp ) );
debug ("found $civicrmid from {$_GET['choice']}");
if (filter_var($civicrmid, FILTER_VALIDATE_INT) === false) {
    throw NEW exception ("Invalid sponsor | $sponsor", ERROR_MAJOR);
}

$out = '';
$sql = "select itemid, child, datedone, amount from sponsorship_summary s left join last_payments p on s.reconid = p.reconid  where s.civicrmid = '$civicrmid'";
$res = do_sql($sql);
if (mysqli_num_rows($res) == 1) {$selected = "checked"; } else { $selected = ''; }
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['datedone'] == null) { $tmp = '';} else { $tmp = "(" . us_date($row['datedone'], false) . "&nbsp;-&nbsp;$".$row['amount'].")" ; }
    $out .= "<input type = 'radio' $selected name='itemid' value='{$row['itemid']}'>{$row['child']} $tmp &nbsp;&nbsp;&nbsp;&nbsp; ";
}
print $out;

?>
<?php
} catch(Exception $err) {
	$trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
	logerr($err->getMessage(),$err->getCode(),$trace);
}

?>
