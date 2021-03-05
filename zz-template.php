<?php 
try {

    include_once("php_header.php");
#    include_once("../../lib/r_payment_tools.php");
    
    $title = "Show Reconciliation";
 #   $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";
    
?>
<?php
} catch(Exception $err) {
    $trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
    logerr($err->getMessage(),$err->getCode(),$trace);
    }
    
    ?>
