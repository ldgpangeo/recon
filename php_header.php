<?php 
#  This goes at the top of each php file 

    include_once("../../lib/common-init.php");
    
    # test if user belongs here
    if ( isset($_COOKIE['childsponsor']) ) {
        $sessionid = $_COOKIE['childsponsor'];
    } else {
        $sessionid = getinput('id');
    }
    debug ("session cookie is $sessionid");
    $tmparray = require_login($sessionid);
    debug ("session value after login is $sessionid");
    if ($tmparray === false) {
        $redirect = "$webroot/admin/login.php";
        $referrer = $_SERVER['REQUEST_URI'];
        include ("page_header.php");
    } else {
        $is_ok = true;
        $uid = $tmparray[0];
        $login = $tmparray[1];
        $expires = time() + (60*50*8);
        
        setcookie("childsponsor",$sessionid);
        debug ("sesseion cookie set to $sessionid");
    }
    
?>