<?php 
  require_once ("admin-header.php");
  require_once("../include/check_post_key.php");
  if (!HAS_PRI("edit_news")) {
    require_once("error.php");
    exit(1);
  }
?>
<?php require_once ("../include/db_info.inc.php"); ?>

<?php // contest_id
  $title=$mysqli->real_escape_string(trim($_POST['title']));
  $content=$mysqli->real_escape_string($_POST['content']);
  $user_id=$mysqli->real_escape_string($_SESSION['user_id']);
  $importance=$mysqli->real_escape_string($_POST['importance']);
  $content=str_replace("<br />\r\n<!---->","",$content);//火狐浏览器中kindeditor会在空白内容的末尾加入<br />\r\n<!---->
  $content=str_replace("<!---->","",$content);//火狐浏览器中kindeditor会在内容的末尾加入<!---->
  $sql="insert into news(`user_id`,`title`,`content`,`time`, `importance`) values('$user_id','$title','$content',now(), '$importance')";
  $mysqli->query ( $sql );
  echo "<script>window.location.href=\"news_list.php\";</script>";
?>

