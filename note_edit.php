<?php 
try {
    
    include_once("php_header.php");
    include_once("../../lib/r_payment_tools.php");
    include_once("../../lib/write_notes.php");
    
    $title = "Show Reconciliation";
    $header = "<script type='text/javascript' src='ajax.js'></script>";
    include "page_header.php";

    $reconid = getinput('reconid');
    if ($reconid == null) {
        throw NEW exception ("I don't know which sponsorship to assign this note.",ERROR_MAJOR);
    }
    $res = do_sql("select * from r_recon where reconid = '$reconid'");
    $recon = mysqli_fetch_assoc($res);
    $now = strftime('%F %T', time() );
    
    $noteid = getinput('noteid');
# -----------------------------  process form submission  ------------------------------------
    if ( (isset($_POST['submit']) ) and ($_POST['submit'] == "Save this item") ) {
        $errors = '';
        debug("Post variables are\n". dump_array($_POST));
        $in = form_validate('edit_note', $errors);
        debug("Parsed variables are\n". dump_array($in));if ($errors <> '') {
            throw NEW exception("$errors",ERROR_MINOR); 
        }
        $noteid = write_notes($in);
    }
    
# ------------------------------   End form submission  -------------------------------------- 
    
    
    
    if ($noteid == null) {
        $action = "insert";
        # set default values
        $old = array (
            'changedby' => $login,
            'reconid' => $reconid,
            'author' => $login,
            'changedby' => $login,
            'scope'  => 'U',
            'is_alert' => 'N',
            'is_active' => 'Y',
            'datedone' => $now,
        );
        # preset titles
        $title = "Create a new Note or Alert";
    } else {
        $action = 'update';
        # get existing data
        $sql = "select * from r_notes where noteid = '$noteid' and effective_end_ts is null";
        $res = do_sql($sql);
        $old = mysqli_fetch_assoc($res);
        $title = "Update an existing Note or Alert";
    }
        
    ?>
<form action = "" method = "post" >
<table width="80%" cellspacing = "3" cellpadding = "3" border = "1" align="center">



<tr>
<td bgcolor="#ffffcc" align="center"  colspan="2">
<h2><?php print $title ?></h2>
</td>
</tr>
<tr>
<td>
Current editor of item
</td>
<td>
<input type="text" name="author" size="40" value="<?php print $login ?>">
</td>
</tr>
<tr>
<td>
Type of item
</td>
<td>
<input type="radio" name='is_alert' value="N" <?php if ($old['is_alert'] == 'N') {print "checked";}?>> Note
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name='is_alert' value="Y" <?php if ($old['is_alert'] == 'Y') {print "checked";}?>> Alert
</td>
</tr>
<tr>
<td>
Retire this item
</td>
<td>
<input type="radio" name='is_active' value="Y" <?php if ($old['is_active'] == 'Y') {print "checked";}?>> No
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name='is_active' value="N" <?php if ($old['is_active'] == 'N') {print "checked";}?>> Yes, delete it
</td>
</tr>

<tr>
<td colspan="2">
Content of note/alarm<br />
<textarea rows="10" cols="80" name="note" wrap="hard" id="note"><?php print stripslashes($old['note'])?></textarea>
</td>
</tr>
<?php if ($action == 'update') {?>
<tr>
<td colspan="2">
First created by <?php print $old['author']?> on <?php print us_date($old['datedone'])?>, &nbsp;&nbsp;&nbsp; Last edited by <?php print $old['changedby']?> on <?php print us_date($old['effective_start_ts'])?> 
</td>
</tr>
<?php }?>
<tr>
<td colspan="2" bgcolor="#ffffcc" align="center">
<input type="hidden" name="reconid" value="<?php print $reconid?>">
<input type="hidden" name="noteid" value="<?php print $noteid?>">
<input type="hidden" name="changedby" value="<?php print $login?>">
<input type="hidden" name="datedone" value="<?php print $old['datedone'] ?>">
<input type="hidden" name="scope" value="U">

<a href="index.php">Main menu</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="submit" value = "Save this item">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="show_recon.php?civicrmid=<?php print $recon['civicrmid']?>&itemid=<?php print $recon['itemid']?>">Recon Page</a>
</td>
</tr>
</table>
</form>    
    <?php
} catch(Exception $err) {
    $trace = $err->getFile()." Line:".$err->getLine().", ".$err->getTraceAsString();
    logerr($err->getMessage(),$err->getCode(),$trace);
    }
    
    ?>
    