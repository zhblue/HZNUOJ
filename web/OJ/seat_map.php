<?php
/**
 * This file is created
 * by lixun516@qq.com
 * @2021.09.10
 **/
?>
<?php
////////////////////////////Common head
    $cache_time=10;
    $OJ_CACHE_SHARE=false;
    require_once('./include/cache_start.php');
    require_once('./include/db_info.inc.php');
    require_once('./include/const.inc.php');
    require_once('./include/setlang.php');
    require_once('./include/my_func.inc.php');
    $view_title= "Welcome To Online Judge";
///////////////////////////MAIN
if (!HAS_PRI('enter_admin_page')) {
    $view_errors="You don't have the privilege to view this page!";
    require("template/".$OJ_TEMPLATE."/error.php");
    exit(0);
}
if (!isset($_GET['cid'])) {
    $view_errors="No Such Contest!";
    require("template/".$OJ_TEMPLATE."/error.php");
    exit(0);
}
$cid=intval($_GET['cid']);
$mode=$_GET['mode'];
if($mode!=""){
    $_SESSION['mode']=$mode;
}
$is_seat_mode = (!isset($_SESSION['mode']) || $_SESSION['mode']!="list"); //座位表模式
if(isset($_GET['lock'])){
    $tmp = intval($_GET['lock']);
    $sql ="UPDATE `contest_online` SET `allow_change_seat`= 1-`allow_change_seat` WHERE `contest_id`='$cid' AND `id`='$tmp'";
    $mysqli->query($sql);
} else if(isset($_GET['kickout'])){
    $tmp = intval($_GET['kickout']);
    $sql ="UPDATE `contest_online` SET `ip`='0.0.0.0' WHERE `contest_id`='$cid' AND `id`='$tmp'";//标记成0.0.0.0的都要踢出机房
    $mysqli->query($sql);
} else if(isset($_GET['kickroom'])){
    $tmp = intval($_GET['kickroom']);
    $sql ="UPDATE `contest_online` SET `ip`='0.0.0.0' WHERE `contest_id`='$cid' AND `room_id`='$tmp'";//标记成0.0.0.0的都要踢出机房
    $mysqli->query($sql);
}
if(isset($_GET['class'])) {
    $_SESSION['class'] = $mysqli->real_escape_string($_GET['class']);
}
$cls = $_SESSION['class'];
switch($cls){
    case "":
        $sql_filter = " WHERE 1 ";
        break;
    case "null":
        $sql_filter = " WHERE (u.class='null' or u.class is null or u.class='其它') ";
        break;
    default:
        $sql_filter = " WHERE u.class='$cls' ";
}
$is_running = is_running($cid);
$sql="SELECT c1.`room_id`,c1.`end_time`,c2.`classroom`,c2.`rows`,c2.`columns`,c3.pnums,c1.user_limit FROM contest as c1 
      LEFT JOIN `ip_classroom` as c2 ON c1.`room_id`=c2.`room_id` 
      LEFT JOIN (SELECT `contest_id`, COUNT(*) pnums FROM `contest_problem` WHERE contest_id='$cid' GROUP BY `contest_id`) AS c3 ON c1.contest_id= c3.contest_id
      WHERE c1.contest_id='$cid'";
$result=$mysqli->query($sql);
if($row = $result->fetch_object()){
    $login_stu=array();
    $room_id = $row->room_id;
    if($is_running) $now=time(); else $now=strtotime($row->end_time);
    $classroom = $row->classroom;
    $rows = $row->rows;
    $cols = $row->columns;
    $problem_nums = $row->pnums;
    $user_limit = $row->user_limit=="Y"?1:0;
    if($is_seat_mode){//座位表模式
        $sql = "SELECT s.*, i.`pcname` FROM `ip_seat` as s LEFT JOIN `ip_list` as i ON s.`ip`=i.`ip` WHERE `room_id`='$room_id' ORDER BY s.`seat_id` DESC";
    } else {  //列表模式
        $cols = 1;
        $sql = "SELECT DISTINCT c.`ip`, i.`pcname`, 0 AS `seat_id` FROM `contest_online` AS c LEFT JOIN `ip_list` as i ON c.`ip`=i.`ip` WHERE contest_id='$cid' AND `room_id`='$room_id' AND c.`ip`<>'0.0.0.0' ORDER BY i.`pcname`";
    }
    $result = $mysqli->query($sql);
    $r = 1;
    $c = 1;
    $view = array();
    while($row = $result->fetch_object()){
        $view[$r][$c]["seat_id"] = $row->seat_id;
        $view[$r][$c]["ip"] = $row->ip;
        $view[$r][$c]["pcname"] = $row->pcname;
        $view[$r][$c]["style"] = "default";
        $view[$r][$c]["alarm"] = 0;
        if($row->ip==""){
            $view[$r][$c]["student"]=array();
        } else {
            $sql = "SELECT c.*,u.`nick`,u.`real_name` FROM `contest_online` AS c ";
            if (!$user_limit) {
                $sql .= " LEFT JOIN `users` AS u";
            } else {
                $sql .= " LEFT JOIN (SELECT * FROM `team` WHERE `contest_id`='$cid') AS u";
            }
            $sql .= " ON c.`user_id`=u.`user_id` $sql_filter AND c.`contest_id`='$cid' AND c.`room_id`='$room_id' AND c.`ip`='$row->ip'";
            $res2=$mysqli->query($sql);
            $stu = array();
            $i=1;
            if($res2->num_rows>1) {
                $view[$r][$c]["style"]="danger";//一个座位有2个及以上登录，示警
                $view[$r][$c]["alarm"]++;
                $view[$r][$c]["pcname"].="($res2->num_rows)";
            }
            while($row2 = $res2->fetch_object()){
                $stu[$i]["id"] = $row2->id;
                $stu[$i]["user_id"] = $row2->user_id;
                $stu[$i]["nick"] = $row2->nick;
                $stu[$i]["real_name"] = $row2->real_name;
                $stu[$i]["allow_change_seat"] = $row2->allow_change_seat;
                $stu[$i]["activeTime"] = strtotime($row2->lastmove)-strtotime($row2->firsttime);//用户在这个contest、这个座位的活动时间（秒）
                $stu[$i]["activeTimeStyle"] = "";
                $stu[$i]["idleTime"] = $now-strtotime($row2->lastmove);//闲置时间
                $stu[$i]["idleTimeStyle"] = "";
                $stu[$i]["WAStyle"] = "";
                $stu[$i]["alarm"] = 0;
                if( $stu[$i]["activeTime"]==0){
                    $stu[$i]["activeTimeStyle"] = "class='am-danger'";
                    $view[$r][$c]["alarm"]++;
                    $stu[$i]["alarm"]++;
                } else if($stu[$i]["activeTime"]+$stu[$i]["idleTime"]>600){
                    if($stu[$i]["activeTime"]<600) { //活动时间10分钟以下，示警
                        $stu[$i]["activeTimeStyle"] = "class='am-danger'";
                        $view[$r][$c]["alarm"]++;
                        $stu[$i]["alarm"]++;
                    }
                    if($stu[$i]["idleTime"]>600) {
                        $stu[$i]["idleTimeStyle"] = "class='am-danger'"; //闲置10分钟以上，示警
                        $view[$r][$c]["alarm"]++;
                        $stu[$i]["alarm"]++;
                    }
                }
                $sql = "SELECT COUNT(*) FROM `hit_log` WHERE `user_id`='$row2->user_id' AND `time` >='$row2->firsttime' AND `time`<='$row2->lastmove'";
                $stu[$i]["hits"]=$mysqli->query($sql)->fetch_array()[0];//用户在这个contest活动时间内的点击数
                $sql = "SELECT COUNT(*) FROM `solution` WHERE `user_id`='$row2->user_id' AND `contest_id`='$cid' AND `in_date` >='$row2->firsttime' AND `in_date`<='$row2->lastmove'";
                $stu[$i]["submit"]=$mysqli->query($sql)->fetch_array()[0];//用户在这个contest活动时间内的代码提交数
                $sql = "SELECT COUNT(*) FROM `solution` WHERE `user_id`='$row2->user_id' AND `contest_id`='$cid' AND `in_date` >='$row2->firsttime' AND `in_date`<='$row2->lastmove' AND result>4";
                $stu[$i]["WA"]=$mysqli->query($sql)->fetch_array()[0];//用户在这个contestd活动时间内的错误代码数
                $sql = "SELECT DISTINCT `problem_id` FROM `solution` WHERE `user_id`='$row2->user_id' AND `contest_id`='$cid' AND `result`= 4 GROUP BY `problem_id`";
                $stu[$i]["ac_problem"]=$mysqli->query($sql)->num_rows;//截止到目前为止用户在这个contest解决了几道题目
                if($stu[$i]["WA"]/$stu[$i]["ac_problem"]>=5){
                    $stu[$i]["WAStyle"] = "class='am-danger'"; //1题错误5次及以上，示警
                    $view[$r][$c]["alarm"]++;
                    $stu[$i]["alarm"]++;
                }
                $tmp = round($stu[$i]["ac_problem"]/$problem_nums, 2)*100;
                $stu[$i]["ac_problem_bar"]='<div style="margin-bottom: 0px; cursor: pointer;" class="am-progress am-progress-striped am-active">';
                $stu[$i]["ac_problem_bar"].='<div title="已完成" class="am-progress-bar" style="width: '.$tmp.'%">'.$stu[$i]["ac_problem"].'/'.$problem_nums.'</div>';
                $stu[$i]["ac_problem_bar"].='<div title="未完成"  class="am-progress-bar am-progress-bar-warning" style="width: '.(100-$tmp).'%">'.($problem_nums-$stu[$i]["ac_problem"]).'/'.$problem_nums.'</div></div>';
                if($tmp <= 100/3){
                    $stu[$i]["ac_problem_style"]="class='am-danger'";
                    $view[$r][$c]["alarm"]++;
                    $stu[$i]["alarm"]++;
                }else {
                    $stu[$i]["ac_problem_style"]="";
                }

                array_push($login_stu,$row2->user_id);
                $i++;
            }
            $view[$r][$c]["student"] = $stu;
        }
        $c++;
        if($c > $cols){
            $r++;
            $c = 1;
        }
    }
    $sql="SELECT p.`user_id` FROM `privilege` AS p";
    if (!$user_limit) {
        $sql .= " JOIN `users` AS u";
    } else {
        $sql .= " JOIN (SELECT * FROM `team` WHERE `contest_id`='$cid') AS u";
    }
    $sql .= " ON p.`user_id`=u.`user_id` $sql_filter AND `rightstr`='c$cid' ORDER BY p.`user_id`";
    $res=$mysqli->query($sql) or die($mysqli->error);
    $haveNotStart_ulist=array_column($res->fetch_all(MYSQLI_ASSOC), 'user_id');
    $haveNotStart_ulist=array_unique($haveNotStart_ulist);
    $total = count($haveNotStart_ulist);
    $login_stu = array_unique($login_stu);
    $total_login = count($login_stu);
    $haveNotStart_ulist = array_diff($haveNotStart_ulist, $login_stu);
    $haveNotStart_ulist = array_unique($haveNotStart_ulist);
    $total_haveNotStart = count($haveNotStart_ulist);
    if (!$user_limit) {
        $sql = "SELECT u.`user_id`, `nick`, `real_name`, c.`id`, c.`ip`, i.`pcname` FROM `users` AS u ";
    } else {
        $sql = "SELECT u.`user_id`, `nick`, `real_name`, c.`id`, c.`ip`, i.`pcname` FROM `team` AS u ";
    }
    $sql .= "LEFT JOIN (SELECT * FROM `contest_online` WHERE `contest_id`='$cid' AND `room_id`='$room_id') AS c ON u.`user_id`=c.`user_id`
    LEFT JOIN `ip_list` AS i ON c.`ip`=i.`ip` $sql_filter AND u.`user_id` in ('".implode("','", $haveNotStart_ulist)."') ORDER BY c.`ip`,u.`user_id`";
    //echo $sql;
    $view_u = array();
    $i = 1;
    $result = $mysqli->query($sql);
    while($row = $result->fetch_object()){
        $view_u[$i]["user_id"] = $row->user_id;
        $view_u[$i]["nick"] = $row->nick;
        $view_u[$i]["real_name"] = $row->real_name;
        $view_u[$i]["id"] = $row->id;
        $view_u[$i]["ip"] = $row->ip;
        $view_u[$i]["pcname"] = $row->pcname;
        $i++;
    }
}
/* 获取班级列表 start */
$classSet = Array();
if(isset($OJ_NEED_CLASSMODE)&&$OJ_NEED_CLASSMODE){
    if (!$user_limit) {
        $sql = "SELECT
                DISTINCT(class)
                FROM
                (select * from solution where solution.contest_id='$cid' and num>=0 ) solution
                    left join users
                on users.user_id=solution.user_id
                ORDER BY class";
    } else {
        $sql = "SELECT
                DISTINCT(class)
                FROM
                (select * from solution where solution.contest_id='$cid' and num>=0 ) solution
                RIGHT JOIN (SELECT * FROM team WHERE contest_id='$cid') team
                on team.user_id=solution.user_id
                ORDER BY class";
    }
    $result = $mysqli->query($sql) or die($mysqli->error);
    while ($row=$result->fetch_object()) $classSet[] = $row->class;
    $result->free();
}
/* 获取班级列表 end */
/////////////////////////Template
require("template/".$OJ_TEMPLATE."/seat_map.php");
/////////////////////////Common foot
if(file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
?>
