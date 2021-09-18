<?php require_once("admin-header.php");
if (!HAS_PRI("inner_function")) {
  echo "Permission denied!";
  exit(1);
}
?>
<?php if (isset($_POST['do'])) {
  require_once("../include/check_post_key.php");
  $sid = intval($mysqli->real_escape_string($_POST['sid']));
  if($sid>0){
    $sql="DELETE FROM `source_code_user` WHERE `solution_id`='$sid'";
    $mysqli->query($sql);
    $sql="DELETE FROM `source_code` WHERE `solution_id`='$sid'";
    $mysqli->query($sql);
    $sql="DELETE FROM `solution` WHERE `solution_id`='$sid'";
    $mysqli->query($sql);
    echo $mysqli->affected_rows . " source code(RUNID=$sid) deleted!";
    if($mysqli->affected_rows>0){
        $sql="SELECT max(`solution_id`) FROM `solution`" ;
        $max_sid=$mysqli->query($sql)->fetch_row()[0];
        $max_sid++;
        if($max_sid<1000) {
            $sql="ALTER TABLE `solution` AUTO_INCREMENT = 1000";
            $mysqli->query($sql);
        } else if($max_sid<=$sid){
            $sql="ALTER TABLE `solution` AUTO_INCREMENT = $max_sid";
            $mysqli->query($sql);
        }
    }
  }
}
?>
<title><?php echo $html_title . $MSG_DEL .$MSG_Solution ?></title>
<h1><?php echo $MSG_DEL .$MSG_Solution ?></h1>
<hr>
<form class="form-inline" method=post>
    <input class="form-control" type=input name='sid' placeholder="<?php echo $MSG_RUNID ?>"><input type='hidden' name='do' value='do'>
    <button type="submit" class="btn btn-default"><?php echo $MSG_SUBMIT ?></button>
    <?php require("../include/set_post_key.php");?>
</form>
<?php
require_once("admin-footer.php")
?>
