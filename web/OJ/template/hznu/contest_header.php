<?php
  /**
   * This file is created
   * by yybird
   * @2016.03.24
   * last modified
   * by yybird
   * @2016.05.26
  **/
?>
<?php
 // 是否显示tag的判断
require_once "include/db_info.inc.php";
if(!isset($mysqli))exit(0);
$url=basename($_SERVER['REQUEST_URI']);
if(isset($OJ_NEED_LOGIN)&&$OJ_NEED_LOGIN&&(
  $url!='loginpage.php' && $url!='registerpage.php'&&
  $url!='lostpassword.php'&& $url!='lostpassword2.php'&&
  $url!='faqs.php' && $url!='index.php' && $url!=''
  ) && !isset($_SESSION['user_id'])){
  header("location:loginpage.php");
  exit();
}
/*Count the hit time START*/
//if($_SERVER['REMOTE_ADDR']!='127.0.0.1') {
  $user_id2="";
  if(isset($_SESSION['user_id'])) $user_id2=$_SESSION['user_id'];
  $require_path=$mysqli->real_escape_string($_SERVER['REQUEST_URI']);
  $sql="INSERT INTO hit_log (ip, time, path, user_id) VALUES ('{$_SERVER['REMOTE_ADDR']}', NOW(), '$require_path', '$user_id2')";
  $mysqli->query($sql);
  $sql="INSERT INTO `log_chart`(`log_date`,`hit_log`) VALUES(CURRENT_DATE(),1) ON DUPLICATE KEY UPDATE `hit_log`=`hit_log`+1";
  //$sql.="(SELECT count(`time`) FROM `hit_log` WHERE `time`<date_add(CURRENT_DATE(), interval 1 day) AND `time`>=CURRENT_DATE())";
  $mysqli->query($sql);//更新点击量数据
//}
/*Count the hit time END*/

$show_tag = false;
if(isset($OJ_show_tag) && $OJ_show_tag){
  if (isset($_SESSION['user_id']) && !isset($_SESSION['contest_id'])) {
    $uid = $_SESSION['user_id'];
    $sql = "SELECT tag FROM users WHERE user_id='$uid'";
    $result = $mysqli->query($sql);
    $row_h = $result->fetch_array();
    $result->free();
    if ($row_h['tag'] == "Y") $show_tag = true;
  } else if (isset($_SESSION['tag'])) {
    if ($_SESSION['tag'] == "N") $show_tag = false;
    else $show_tag = true;
  }
  if ($show_tag) $_SESSION['tag'] = "Y";
  else $_SESSION['tag'] = "N";
}

if(isset($_GET['cid'])){
  $warnning_percent=90;
  $cid = $mysqli->real_escape_string($_GET['cid']);
  $sql="SELECT UNIX_TIMESTAMP(start_time) AS start_time, UNIX_TIMESTAMP(end_time) AS end_time,`unlock`,lock_time,title,user_limit
        ,c.room_id,`private`,`defunct`,i.`seat_forbid_multiUser_login`,i.`user_forbid_multiIP_login`,`start_by_login_time`,`duration`,`enable_overtime`,cl.`overTime`,cl.`startTime` FROM contest c
        LEFT JOIN ip_classroom i ON c.room_id=i.room_id 
        LEFT JOIN (SELECT * FROM `contest_loginTime` WHERE `contest_loginTime`.`user_id`='$user_id2') cl ON cl.contest_id=c.contest_id
        WHERE c.contest_id='$cid'";
  $res=$mysqli->query($sql);
  $contest_time=$res->fetch_array();

  $now = time();
  $loginTime = "";
  if($contest_time['start_by_login_time'] && $user_id2!="" && !HAS_PRI("edit_contest")){//记录contest登入时间
    $sql = "SELECT `user_id` FROM `solution` WHERE contest_id='$cid' AND `user_id`='$user_id2' LIMIT 1";
    if($mysqli->query($sql)->num_rows==0 && is_null($contest_time['startTime']) && $confirmlogin){//还没提交代码而且还没有登入记录且确认登入，就记录contest登入时间
      if($contest_ok && is_running($cid)){
        $loginTime = date('Y-m-d H:i:s',$now);
        $sql = "INSERT INTO `contest_loginTime`(`contest_id`,`user_id`,`startTime`) VALUES('$cid','$user_id2','$loginTime') ON DUPLICATE KEY UPDATE `startTime`='$loginTime'";
        $mysqli->query($sql);
      }
    } else {
      if(!is_null($contest_time['startTime'])) $loginTime = $contest_time['startTime'];
    }
  }
  $contest_starttime = $contest_time['start_time'];
  $contest_endtime=$contest_time['end_time'];
  if($contest_time['start_by_login_time'] && $contest_time['duration'] > 0 && $loginTime != ""){
    $contest_starttime = strtotime($loginTime);
    $contest_endtime = strtotime($loginTime) + intval(floatval($contest_time['duration'])*3600);//登入时间+持续时间
  }
  if($contest_time['enable_overtime']){
    $contest_endtime = $contest_endtime + intval($contest_time['overTime'])*60;
  }

  $bar_percent=0;
  $is_started=false;
  if($now>=$contest_time['start_time'])$is_started=true;
  $dur=$now-$contest_starttime;
  $contest_len = $contest_endtime - $contest_starttime;
  if($dur>=$contest_len)$dur=$contest_len;
  $bar_percent=$dur/$contest_len*100;
  
  $bar_color="am-progress-bar-success";
  if($bar_percent==100)$bar_color="am-progress-bar-secondary";
  else if($bar_percent>=$warnning_percent)$bar_color="am-progress-bar-danger";

  if(!$is_started){
    $bar_percent=100;
    $bar_color="am-progress-bar-secondary";
  }
  $unlock=$contest_time['unlock'];
  switch($unlock){
      case 0: //用具体时间来控制封榜
          $view_lock_time=$contest_time['end_time'] - $contest_time['lock_time'];
          break;
      case 2: //用时间比例来控制封榜
          $view_lock_time=$contest_time['end_time'] - ($contest_time['end_time'] - $contest_time['start_time']) * $contest_time['lock_time'] / 100;
          break;
  }
  $contest_title=$contest_time['title'];
  $room_id=$contest_time['room_id'];
  if($contest_time['seat_forbid_multiUser_login']==NUll){
    $seat_forbid_multiUser_login = 1;
  } else $seat_forbid_multiUser_login = $contest_time['seat_forbid_multiUser_login']; //是否禁止一个IP(座位上)登入多个不同用户
  if($contest_time['user_forbid_multiIP_login']==NUll){
    $user_forbid_multiIP_login = 1;
  } else $user_forbid_multiIP_login = $contest_time['user_forbid_multiIP_login']; //是否禁止同一个用户登录到不同的IP(座位)
  //if(!isset($_SESSION['mode']) && (!$seat_forbid_multiUser_login || !$user_forbid_multiIP_login)) $_SESSION['mode']="list";
  $user_limit = $contest_time['user_limit']=="Y"?1:0;
  $title.="<".$contest_title.">";
}
?>
<!doctype html>
<html>
  <head lang="en">
    <meta charset="UTF-8">
    <title><?php echo $OJ_NAME."--".$title?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="Cache-Control" content="no-siteapp"/>
    <link rel="alternate icon" type="image/png" href="<?php echo $ICON_PATH ?>">
    <link rel="stylesheet" href="plugins/AmazeUI/css/amazeui.min.css"/>
    <!-- <link rel="stylesheet" href="http://cdn.amazeui.org/amazeui/2.7.2/css/amazeui.min.css"/> -->
    <style type="text/css">
	 .well{
    display: block;
    padding: 1rem;
    margin: 1rem 0;
    /*font-size: 1.3rem;*/
    line-height: 1.6;
    word-break: break-all;
    word-wrap: break-word;
    color: #555;
    background-color: #f8f8f8;
    border: 1px solid #dedede;
    border-radius: 0;
 }
      .blog-footer {
        padding: 10px 0;
        text-align: center;
      }
      .am-container {
        margin-left: auto;
        margin-right: auto;
        width: 100%;
        max-width: 1400px;
      }
      .am-badge {
        font-weight: normal;
      }
    </style>
  </head>
<body style="padding-top: 50px;">
<?php 
    if(isset($_GET['cid']))
      $cid=intval($_GET['cid']);
    if (isset($_GET['pid']))
      $pid=intval($_GET['pid']);
?>
<header class="am-topbar-inverse am-topbar-fixed-top">
  <button class="am-topbar-btn am-topbar-toggle am-btn am-btn-sm am-btn-primary am-show-sm-only" data-am-collapse="{target: '#collapse-head'}">
    <span class="am-sr-only">导航切换</span>
    <span class="am-icon-bars"></span>
  </button>
  <div class="am-container">
    <div class="am-collapse am-topbar-collapse" id="collapse-head">
      <ul class="am-nav am-nav-pills am-topbar-nav">
      <?php if (!isset($_SESSION['contest_id'])) { ?>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="index.php"){echo "class='am-active'";} ?>><a class="am-icon-chevron-left" href="./contest.php<?php echo $_SESSION['my'] ?>"> <?php echo $BACK_TO_CONTEST ?></a></li>
      <?php } ?>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="contest.php" || basename($_SERVER['SCRIPT_NAME'])=="problem.php"){echo "class='am-active'";} ?>><a href='./contest.php?cid=<?php echo $cid?>'><?php echo $MSG_PROBLEM ?></a></li>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="status.php"){echo "class='am-active'";} ?>><a href='./status.php?cid=<?php echo $cid?>'><?php echo $MSG_STATUS ?></a></li>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="contestrank.php"){echo "class='am-active'";} ?>><a href='./contestrank.php?cid=<?php echo $cid?>'><?php echo $MSG_RANKLIST ?></a></li>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="contestrank-oi.php"){echo "class='am-active'";} ?>><a href='./contestrank-oi.php?cid=<?php echo $cid?>'>OI <?php echo $MSG_RANKLIST ?></a></li>
      <?php if (HAS_PRI('enter_admin_page') && $room_id>0) { ?>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="seat_map.php"){echo "class='am-active'";} ?>><a href='./seat_map.php?cid=<?php echo $cid?>'><?php echo $MSG_SeatMap?></a></li>
      <?php } ?>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="conteststatistics.php"){echo "class='am-active'";} ?>><a href='./conteststatistics.php?cid=<?php echo $cid?>'><?php echo $MSG_STATISTICS ?></a></li>
        <?php if(isset($OJ_show_PrinterAndDiscussInContest)&&$OJ_show_PrinterAndDiscussInContest){?>
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="contest_code_printer.php"){echo "class='am-active'";} ?>><a href='./contest_code_printer.php?cid=<?php echo $cid?>'>Printer</a></li>     
        <li <?php if(basename($_SERVER['SCRIPT_NAME'])=="contest_discuss.php"){echo "class='am-active'";} ?>><a href='./contest_discuss.php?cid=<?php echo $cid?>'>Discuss</a></li>   
        <?php }?>
      </ul>
        <!-- 用户部分 start -->
        <?php
        if (!isset($_SESSION['user_id'])){
echo <<<BOT
          <div class="am-topbar-right">
            <ul class="am-nav am-nav-pills am-topbar-nav">
              <li class="am-dropdown" data-am-dropdown>
                <a class="am-dropdown-toggle" data-am-dropdown-toggle href="javascript:;"> $MSG_LOGIN <span class="am-icon-caret-down"></span></a>
                  <ul class="am-dropdown-content">
                    <li><a href="loginpage.php"><span class="am-icon-user"></span> $MSG_LOGIN</a></li>
                    <li><a href="registerpage.php"><span class="am-icon-pencil"></span> $MSG_REGISTER</a></li>
                  </ul>
              </li>
            </ul>
        </div>
BOT;
        }else{
          $user_session = $_SESSION['user_id'];
echo <<<BOT
          <div class="am-topbar-right">
               <ul class="am-nav am-nav-pills am-topbar-nav">
                <li class="am-dropdown" data-am-dropdown>
                  <a class='am-dropdown-toggle' data-am-dropdown-toggle href='javascript:;'><span class='am-icon-user'></span> {$_SESSION['user_id']} <span class='am-icon-caret-down'></span></a>
                    <ul class="am-dropdown-content">
BOT;
                    if (!isset($_SESSION['contest_id'])) {
echo <<<BOT
                      <li><a href="modifypage.php"><span class="am-icon-edit"></span> $MSG_MODIFY_USER</a></li>
                      <li><a href="userinfo.php?user={$_SESSION['user_id']}"><span class="am-icon-info-circle"></span> $MSG_USERINFO</a></li>
                      <!-- <li><a href="mail.php"><span class="am-icon-comments"></span> Mail</a></li> -->
                      <li><a href="status.php?user_id=$user_session"><span class="am-icon-keyboard-o"></span> $MSG_MY_SUBMISSIONS</a></li>
					  <li><a href="./contest.php?my"><span class="am-icon-leaf"></span> $MSG_MY_CONTESTS </a></li> 
BOT;
          if(isset($OJ_points_enable)&&$OJ_points_enable){
            $sql="SELECT `points` FROM `users` WHERE `user_id`='{$_SESSION['user_id']}'";
            $result=$mysqli->query($sql);
            if ($rowp=$result->fetch_object()){
              $points=$rowp->points;
            } else $points=0;
            echo "<li><a href='./points_history.php?user={$_SESSION['user_id']}'><span class='am-icon-apple'></span> ". round($points,2)." $MSG_points</a></li>";
            // if(isset($OJ_points_reChange)&&$OJ_points_reChange){
            //   echo "<li><a href='./points_rechange.php'><span class='am-icon-credit-card-alt'></span> $MSG_points$MSG_Recharge </a></li>";
            // }
          }
          if(isset($OJ_show_tag) && $OJ_show_tag){
            if ($show_tag) echo "<li><a href='./changeTag.php'><span class='am-icon-toggle-on'></span> $MSG_HIDETAG</a></li>";
            else echo "<li><a href='./changeTag.php'><span class='am-icon-toggle-off'></span> $MSG_SHOWTAG</a></li>";
           }
          }
		  echo "<li><a href='./logout.php'><span class='am-icon-reply'></span> $MSG_LOGOUT</a></li>";
          if(HAS_PRI('enter_admin_page')){
            echo <<<BOT
              <li><a href="admin/index.php"><span class="am-icon-cog"></span> $MSG_ADMIN</a></li>
                      </ul>
                  </li>
                </ul>
          </div>
BOT;
          }else{
            echo <<<BOT
                      </ul>
                  </li>
                </ul>
          </div>
BOT;
          }
        }
        ?>
        <!-- 用户部分 end -->
        <?php 
        if($contest_time['start_by_login_time'] && $contest_time['duration'] > 0){
          $tmp = floatval($contest_time['duration'])*60+$contest_time['overTime']*$contest_time['enable_overtime'];
          if(floor($tmp / 60) > 0) $h .= floor($tmp / 60)." 小时 ";
          if($tmp % 60 > 0) $h .= $tmp % 60 ." 分钟";
        }
        
        ?>
    </div>
  </div>
</header>
<style type="text/css" media="screen">
  .text-bold{
    font-weight: bold;
  }
</style>
<div class="am-container" style="margin-top: 20px;">
  <div class="am-g" style="padding-bottom: 7px;">
    <div class="am-u-sm-3">
      <span class="text-bold"><?php echo $MSG_StartTime ?>: </span>
      <span><?php echo date("Y-m-d, H:i:s",$contest_starttime) ?></span>
    </div>
    <div class="am-u-sm-6 am-text-center">
      <span class="text-bold" style="font-size: large;"><?php if(!is_null($defunct) && strtolower($view_defunct)=="y") echo "<i class='am-icon-eye-slash'></i>&nbsp;"; echo $contest_title; if($h!="") echo "【答题时间：".$h."】"; ?></span>
    </div>
    <div class="am-u-sm-3 am-text-right">
      <span class="text-bold"><?php echo $MSG_EndTime ?>: </span>
      <span><?php echo date("Y-m-d, H:i:s", $contest_endtime) ?></span>
    </div>
  </div>

  <div class="am-progress am-progress-striped am-active" id="contest-bar" style="margin-bottom: 0;">
    <div class="am-progress-bar <?php echo $bar_color ?>" style="width: <?php echo $bar_percent ?>%" id="contest-bar-progress">
      <?php if (!$is_started)
         echo $MSG_notStart;
     ?>
    </div>
  </div>

  <?php if ($is_started){ ?>
  <div class="am-g">
    <div class="am-u-sm-4">
      <span class="text-bold"><?php echo $MSG_TimeElapsed ?>: </span>
      <span id="time_elapsed"></span>
    </div>
    <div class="am-u-sm-4 am-text-center">
    <?php if ($unlock!=1) { ?>
      <span class="text-bold"><?php echo $MSG_LockTime ?>: </span>
      <span><?php echo date("Y-m-d, H:i:s",$view_lock_time) ?></span>
    <?php }?>
    </div>
    <div class="am-u-sm-4 am-text-right">
      <span class="text-bold"><?php echo $MSG_TimeRemaining ?>: </span>
      <span id="time_remaining"></span>
    </div>
  </div>
  <?php } 
    if(HAS_PRI("edit_contest")) {
        echo <<<HTML
        <div align="center" style="margin-top: 5px;">
          <span class="am-badge am-badge-success am-text-lg">
            <a href="./admin/contest_edit.php?cid={$_GET['cid']}" style="color: white;">$MSG_EDIT</a>
          </span>
        </div>
HTML;
    } else if($contest_time['start_by_login_time']&& $loginTime != ""){
      if($contest_time['duration'] > 0){
        $h = "从 ".$loginTime." 开始计时，答题时间为 ".$h;
      } else {
        $h = "从 ".$loginTime." 开始计时";
      }
      echo <<<HTML
      <div align="center" style="margin-top: 5px;">
        <label> $h </label>
      </div>
HTML;
    }

  /*记录用户的contest活动记录 start*/
  if (isset($_GET['cid']) && $user_id2!="" && is_running($cid) && $room_id > 0) {
    $old_ip = "";
    $new_ip = $_SERVER['REMOTE_ADDR'];
    $record_OK=true;
    if ($contest_time['private'] && !isset($_SESSION['c'.$cid])) $record_OK=false;
    if ($contest_time['defunct']=='Y') $record_OK=false;
    if (HAS_PRI("edit_contest")) $record_OK=false;
    if ($record_OK){
      $sql = "SELECT COUNT(*) FROM `contest_online` WHERE `contest_id`='$cid' AND `room_id`='$room_id' AND `user_id`='$user_id2' AND `ip`='0.0.0.0'";
      if($mysqli->query($sql)->fetch_array()[0]>0){//删除该账号所有ip的在线记录并注销本次登录
        $sql = "DELETE FROM `contest_online` WHERE `contest_id`='$cid' AND `room_id`='$room_id' AND `user_id`='$user_id2'";
        $mysqli->query($sql);
        unset($_SESSION['user_id']);
        session_destroy();
        header("Location:loginpage.php");
        exit(0);
      }
      $sql = "SELECT * FROM `contest_online` WHERE `contest_id`='$cid' AND `room_id`='$room_id' AND `user_id`='$user_id2' ORDER BY `firsttime` LIMIT 1";
      $online = $mysqli->query($sql);
      if($row = $online->fetch_object()){//这个账户在这个contest、room_id已有登录记录
        $old_ip = $row->ip;
        if($new_ip <> $old_ip && !$row->allow_change_seat && $user_forbid_multiIP_login){//一个普通用户只能登录到一个座位上(第一次登录即锁定IP)，其他座位非法登入都注销掉
          //echo "###########################111111111<br>";
          unset($_SESSION['user_id']);
          session_destroy();
          $view_errors= "<a href='./loginpage.php' >$user_id2 乖徒儿，你还在编号【".getPCNameByUserID($user_id2, $cid, $room_id)."】的洞府修炼，不要乱跑！<br>若要更换洞府记得给为师飞鸽传书哦，点击此处重新登录</a>\n";
          require("template/".$OJ_TEMPLATE."/error.php");
          exit(0);
        }
      }
      $sql = "SELECT * FROM `contest_online` WHERE `contest_id`='$cid' AND `room_id`='$room_id' AND `ip`='$new_ip' ORDER BY `firsttime` LIMIT 1";
      $result1 = $mysqli->query($sql);
      if($old_ip == $new_ip){//前后在同一个座位上
        $row = $result1->fetch_object();
        if($row->user_id!=$user_id2 && $seat_forbid_multiUser_login){//这个new_ip（座位）目前在这个contest、room_id已有账户登录
          //echo "###########################10101010<br>";
          unset($_SESSION['user_id']);
          session_destroy();
          $view_errors= "<a href='./loginpage.php'>呔！【".getPCNameByUserID($row->user_id, $cid, $room_id)."】乃我乖徒儿{$row->user_id}的修炼洞府，何方妖孽在此窥探！<br>年轻人要讲武德，还不速速退去，点击此处出门右拐重新登录！</a>\n";
          require("template/".$OJ_TEMPLATE."/error.php");
          exit(0);
        }
        $sql="UPDATE `contest_online` SET `lastmove`=NOW(),`allow_change_seat`=0 WHERE `contest_id`='$cid' AND `user_id`='$user_id2' AND `ip`='$new_ip' AND `room_id`='$room_id'";
        //echo "###########################22222222222222<br>";
        $mysqli->query($sql) or die("Error! ".$mysqli->error);
      } else {//前后不在同一个座位上，或者是第一次登录
        if($row = $result1->fetch_object()){//这个new_ip（座位）目前在这个contest、room_id已有账户登录
          if($online->num_rows==0){ //new_ip上后登录的这个账户在这个contest、room_id没有登录记录,本次第一次登录
            if(!$seat_forbid_multiUser_login ){//如果允许同一个IP（座位）登入不同的普通用户（学生）
              $sql="INSERT INTO `contest_online`(`contest_id`,`user_id`,`ip`,`room_id`,`firsttime`,`lastmove`) VALUES('$cid','$user_id2','$new_ip','$room_id',NOW(),NOW())";
              //echo "###########################3333333333333<br>";
              $mysqli->query($sql) or die("Error! ".$mysqli->error);
            } else {
              //echo "###########################4444444444444<br>";
              unset($_SESSION['user_id']);
              session_destroy();
              $view_errors= "<a href='./loginpage.php'>呔！【".getPCNameByUserID($row->user_id, $cid, $room_id)."】乃我乖徒儿{$row->user_id}的修炼洞府，何方妖孽在此窥探！<br>年轻人要讲武德，还不速速退去，点击此处出门右拐重新登录！</a>\n";
              require("template/".$OJ_TEMPLATE."/error.php");
              exit(0);
            }
          } else {//new_ip上后登录的这个账户在这个contest、room_id已有登录记录
            if($row->user_id==$user_id2){
              //echo "###########################5555555555<br>";
              $sql="DELETE FROM `contest_online` WHERE `contest_id`='$cid' AND `user_id`='$user_id2' AND `ip`='$old_ip' AND `room_id`='$room_id'";
              $mysqli->query($sql) or die("Error! ".$mysqli->error);
              $sql="UPDATE `contest_online` SET `lastmove`=NOW(),`allow_change_seat`=0 WHERE `contest_id`='$cid' AND `user_id`='$user_id2' AND `ip`='$new_ip' AND `room_id`='$room_id'";
              $mysqli->query($sql) or die("Error! ".$mysqli->error);
            } else {
              if(!$seat_forbid_multiUser_login){//如果允许同一个IP（座位）登入不同的普通用户（学生）
                $sql="UPDATE `contest_online` SET `ip`='$new_ip',`lastmove`=NOW(),`allow_change_seat`=0 WHERE `contest_id`='$cid' AND `user_id`='$user_id2' AND `ip`='$old_ip' AND `room_id`='$room_id'";
                //echo "###########################6666666666<br>";
                $mysqli->query($sql) or die("Error! ".$mysqli->error);
              } else {
                //echo "###########################7777777777<br>";
                unset($_SESSION['user_id']);
                session_destroy();
                $view_errors= "<a href='./loginpage.php'>呔！【".getPCNameByUserID($row->user_id, $cid, $room_id)."】乃我乖徒儿{$row->user_id}的修炼洞府，何方妖孽在此窥探！<br>年轻人要讲武德，还不速速退去，点击此处出门右拐重新登录！</a>\n";
                require("template/".$OJ_TEMPLATE."/error.php");
                exit(0);
              }
            }
          }
        } else {//这个IP（座位）目前在这个contest、room_id没有账户登录
          if($online->num_rows==0){ //这个账户在这个contest、room_id没有登录记录,本次第一次登录
            $sql="INSERT INTO `contest_online`(`contest_id`,`user_id`,`ip`,`room_id`,`firsttime`,`lastmove`) VALUES('$cid','$user_id2','$new_ip','$room_id',NOW(),NOW())";
            //echo "###########################888888888<br>";
            $mysqli->query($sql) or die("Error! ".$mysqli->error);
          } else { //场景：学生换位置到空座位
            $sql="UPDATE `contest_online` SET `ip`='$new_ip',`lastmove`=NOW(),`allow_change_seat`=0 WHERE `contest_id`='$cid' AND `user_id`='$user_id2' AND `ip`='$old_ip' AND `room_id`='$room_id'";
            //echo "###########################999999999<br>";
            $mysqli->query($sql) or die("Error! ".$mysqli->error);
          }
        }
      }
    }
  }
  /*记录用户的contest活动记录 end*/

    ?>
</div>