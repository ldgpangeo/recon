<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php 
if ($redirect <> '') {
    print "<meta http-equiv=\"refresh\" content=\"0;URL=$redirect";
    if ($referrer <> '') {
        print "?referrer=".urlencode($referrer);
    }
    print "\">";
}
?>
<link href="<?php print $webroot?>/css/style.css" rel="stylesheet" type="text/css">
<link href="<?php print $webroot?>/css/buttons.css" rel="stylesheet" type="text/css">
<title><?php if (isset($title)) { print $title; } else { print "Sponsorship Recon" ;} ?></title>
<?php if (isset($header)) { print $header; }?>
</head>
<body>
