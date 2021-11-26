<?php

/**
 * This file is created
 * by lixun516@qq.com
 * @2021.11.23
 **/
?>


<?php
require_once("../include/db_info.inc.php");
require_once("../include/setlang.php");
require_once("../include/my_func.inc.php");
if (!HAS_PRI("edit_contest")) {
    $view_error = "You don't have the privilege to view this page!";
    require_once("error.php");
    exit(1);
}
class user {
    var $user_id;
    var $nick;
    var $real_name;
    var $class;
    var $stu_id;
    var $submit;//提交次数
    var $solved;//AC题目个数
    var $wa_num;//错误代码次数
    var $time;//总时间
    var $score;//总分数
    var $status;//各个比赛情况
    var $p_wa_num;
    var $p_ac_sec;
    var $p_pass_rate;
    function __construct(){
        $this->submit = 0;
        $this->solved = 0;
        $this->wa_num = 0;
        $this->time = 0;
        $this->score = 0;
        $this->status = array(0);
        $this->p_wa_num = array(0);
        $this->p_ac_se = array(0);
        $this->p_pass_rate=array(0);
    }
    function Add($cid,$pid,$sec,$res){
        global $problem_score;
        if (!isset($this->status[$cid]['submit'])){
            $this->status[$cid]['submit']=0;
            $this->status[$cid]['solved']=0;
            $this->status[$cid]['wa_num']=0;
            $this->status[$cid]['time']=0;
            $this->status[$cid]['score']=0;
            foreach($problem_score as $id => $value){
                $this->p_wa_num[$cid][$id]=0;
                $this->p_ac_sec[$cid][$id]=0;
                $this->p_pass_rate[$cid][$id]=0;
            }
        }
        $this->submit++;
        $this->status[$cid]['submit']++;
        if(is_float($res) && $res >= 0 && $res <= 1.001){
            if($res * 100 < 99){
                if($res > $this->p_pass_rate[$cid][$pid]){
                    $this->score -= $problem_score[$pid] * $this->p_pass_rate[$cid][$pid];
                    $this->status[$cid]['score'] -= $problem_score[$pid] * $this->p_pass_rate[$cid][$pid];
                    $this->p_pass_rate[$cid][$pid] = $res;
                    $this->score += $problem_score[$pid] * $this->p_pass_rate[$cid][$pid];
                    $this->status[$cid]['score'] += $problem_score[$pid] * $this->p_pass_rate[$cid][$pid];
                }
                $res = 6;
            } else {
                $this->score -= $problem_score[$pid] * $this->p_pass_rate[$cid][$pid];
                $this->status[$cid]['score'] -= $problem_score[$pid] * $this->p_pass_rate[$cid][$pid];
                $res = 4;
            }
        }
        if($res!=4){
            $this->wa_num++;
            $this->status[$cid]['wa_num']++;
            $this->p_wa_num[$cid][$pid]++;
        } else {
            if ($this->p_ac_sec[$cid][$pid]>0) return;
            if ($sec<0) return; //抢跑的不算
            $this->p_ac_sec[$cid][$pid]=$sec;
            $this->solved++;
            $this->status[$cid]['solved']++;
            $this->score += $problem_score[$pid];
            $this->status[$cid]['score'] += $problem_score[$pid];
            $t = $sec + $this->p_wa_num[$cid][$pid] * 1200;
            $this->time += $t;
            $this->status[$cid]['time'] += $t;
        }
    }
}
function s_cmp($A,$B){
    if ($A->score!=$B->score) return $A->score<$B->score; //1看得分
    else if ($A->solved!=$B->solved) return $A->solved<$B->solved;//2看AC数
    else if ($A->time!=$B->time) return $A->time>$B->time;//3看累计耗时
    else if ($A->time==$B->time && $A->time==0) return $A->wa_num<$B->wa_num;//累计耗时都为0的情况下，谁的错误数多，谁排前面
    else return $A->wa_num>$B->wa_num;//累计耗时相等且都不为0的情况下，谁的错误数少，谁排前面
}
if (isset($_POST['submit'])) {
    require_once("../include/my_func.inc.php");
    $stuClass = $mysqli->real_escape_string($_POST['class']);
    $sql = "SELECT `user_id`,`nick`,`real_name`,`class`, `stu_id` FROM `users` WHERE `class`='$stuClass' ORDER BY `user_id` ";
    $result=$mysqli->query($sql);
    $students = $result->fetch_all(MYSQLI_ASSOC);
    foreach($students as $stu ){
        $U[$stu['user_id']] = new user();
        $U[$stu['user_id']]->user_id = $stu['user_id'];
        $U[$stu['user_id']]->nick = $stu['nick'];
        $U[$stu['user_id']]->real_name = $stu['real_name'];
        $U[$stu['user_id']]->class = $stu['class'];
        $U[$stu['user_id']]->stu_id = $stu['stu_id'];
    }
    $stu_list = array_column($students, 'user_id');
    $contest_list = array();//后面打印表头用
    $sql = "SELECT `contest_id`,`title`,`start_time`,`start_by_login_time` FROM `contest` WHERE `contest_id` IN ('".implode("','",$_POST['contests'])."') ORDER BY `contest_id`";
    $result=$mysqli->query($sql);
    $contests = $result->fetch_all(MYSQLI_ASSOC);
    $contest_total = $result->num_rows;
    $problem_total = 0;
    foreach($contests as $contest){
        $user_start_time = array();
        $cid = $contest['contest_id'];
        $contest_list[$cid]['title'] = "【{$cid}】".$contest['title'];

        //设定本次比赛每个用户的开始时间 start
        foreach($stu_list as $stu){
            $user_start_time[$stu]=strtotime($contest['start_time']);
        }
        if(intval($contest['start_by_login_time'])){
            $sql = "SELECT * FROM `contest_loginTime` WHERE `contest_id`='$cid' AND `user_id` IN ('".implode("','",$stu_list)."')";
            $result = $mysqli->query($sql);
            while($row = $result->fetch_object()){
                $user_start_time[$row->user_id]=strtotime($row->startTime);
            }
        }
        //设定本次比赛每个用户的开始时间 end

        //查询本次比赛中的题目题号及分值 start
        $problem_score = array();
        $sql = "SELECT `num`,`score` FROM `contest_problem` a 
        INNER JOIN (SELECT `problem_id` FROM `problem`) b 
        ON a.`problem_id` = b.`problem_id` 
        WHERE `contest_id` = $cid AND `num` >=0 ORDER BY `num`" ;
        $result=$mysqli->query($sql);
        $contest_list[$cid]['pid_cnt'] = $result->num_rows;
        $problem_total += $result->num_rows;
        while($row = $result->fetch_object()){
            $problem_score[$row->num] = intval($row->score);
        }
        
        //查询本次比赛中的题目题号 end

        //查询本次比赛中选定班级各个账号的答题情况 start
        $sql = "SELECT `user_id`,`result`,`num`,`in_date`,`pass_rate` FROM `solution` WHERE `contest_id`='$cid' and num>=0 AND `user_id` IN ('".implode("','",$stu_list)."') ORDER BY `in_date` ";
        $result=$mysqli->query($sql) or die($mysqli->error);
        while($row = $result->fetch_object()){
            if($_POST['mode']=="ACM"){
                $U[$row->user_id]->add($cid,$row->num,strtotime($row->in_date)-$user_start_time[$row->user_id],intval($row->result));
            } else {
                $U[$row->user_id]->add($cid,$row->num,strtotime($row->in_date)-$user_start_time[$row->user_id],floatval($row->pass_rate));
            }
            
        }
        //查询本次比赛中选定班级各个账号的答题情况 end
    }
    usort($U,"s_cmp");
    echo<<<html
    <style type="text/css" media="screen">
    .text{
      mso-number-format:"\@";
    }
    .excel_table{
      white-space: nowrap;
      table-layout: fixed;
    }
    .pcell{
      min-width: 150px;
    }
   </style>
html;
    echo "<meta http-equiv='Content-type' content='text/html;charset=UTF-8' /> \n";
    header("content-type: application/excel");
    header("Content-Disposition: attachment; filename=\"$stuClass"."_".$MSG_exportScore."_".date('Y-m-d_H-i-s',time()).".xls" . "\"");
    header("Content-Type: application/force-download");
    echo "<h3>$stuClass"." $MSG_exportScore</h3>";
    echo "<h4>当前选中{$contest_total}个{$MSG_CONTEST}， 共{$problem_total}题</h4>";
    echo "<table border=1 align='center' class='excel_table'><tr>";
    echo "<td rowspan='2' >$MSG_REAL_NAME</td>
    <td rowspan='2'>$MSG_NICK</td>
    <td rowspan='2'>$MSG_RANK</td>
    <td rowspan='2' style='mso-number-format:\"\\@\"'>$MSG_USER</td>
    <td rowspan='2'>$MSG_SCORE</td>
    <td rowspan='2'>$MSG_SOLVED</td>
    <td rowspan='2'>$MSG_CompletionRate</td>
    <td rowspan='2'>$MSG_SUNMITTOTAL</td>
    <td rowspan='2'>$MSG_Wrong</td>
    <td rowspan='2'>$MSG_PENALTY</td>";
    foreach($contest_list as $contest){
        echo "<td colspan='3'>".$contest['title']."</td>";
    }
    echo "</tr>\n<tr>";
    foreach($contest_list as $contest){
        echo "<td>$MSG_SOLVED({$contest['pid_cnt']})</td><td>$MSG_SUBMISSIONS</td><td>$MSG_Wrong</td>";
    }
    echo "</tr>\n";

    $rank=0;
    foreach($U as $stu){
        $rank++;
        echo "<tr><td>$stu->real_name</td>";
        echo "<td>$stu->nick</td>";
        echo "<td>$rank</td>";
        echo "<td>$stu->user_id</td>";
        echo "<td>".intval($stu->score)."</td>";
        echo "<td>$stu->solved</td>";
        echo "<td>". round($stu->solved/floatval($problem_total)*100,2)."%</td>";
        echo "<td>$stu->submit</td>";
        echo "<td>$stu->wa_num</td>";
        echo "<td>".sec2str($stu->time)."</td>";
        foreach($contest_list as $cid => $value){
            echo "<td>".$stu->status[$cid]['solved']."</td>";
            echo "<td>".$stu->status[$cid]['submit']."</td>";
            echo "<td>".$stu->status[$cid]['wa_num']."</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    exit();
}
?>

<?php require_once("admin-header.php"); ?>
<title><?php echo $html_title . $MSG_exportScore ?></title>
<h1><?php echo $MSG_exportScore ?></h1>
<h4><?php echo $MSG_HELP_exportScore ?></h4>
<hr>
<link href="../plugins/multi-select/css/multi-select.css" media="screen" rel="stylesheet" type="text/css" />
<div class="am-avg-md-1" style="margin-top: 20px; margin-bottom: 20px;max-width: 1900px">
    <form class="am-form am-form-horizontal" method="post">
    <div class="am-form-group" style="white-space: nowrap;">
        <label class="am-u-sm-3 am-form-label"><?php echo $MSG_Class ?>:</label>
        <div class="am-u-sm-9">
        <select name="class" class="selectpicker show-tick" data-live-search="true" data-width="340px">
            <?php
            require_once("../include/classList.inc.php");
            $classList = get_classlist(true, "");
            $class = '其它';
            foreach ($classList as $c){
                if($c[0]) echo "<optgroup label='$c[0]级'>\n"; else echo "<optgroup label='无入学年份'>\n";
                foreach ($c[1] as $cl){
                    if($cl == $class) $selected = "selected"; else $selected ="";
                    echo "<option value='$cl' $selected>$cl</option>\n";
                }
                echo "</optgroup>\n";
            }
            ?>
        </select>
        </div>
    </div>
    <div class="am-form-group" style="white-space: nowrap;">
        <label class="am-u-sm-3 am-form-label"><?php echo $MSG_CONTEST ?>:</label>
        <div class="am-u-sm-9">
          <select multiple class="searchable" name="contests[]" required>
            <?php
            $view_contest = get_contests(array("All" => true), " AND `defunct`='N' ");
            foreach ($view_contest as $view_con) {
                if ($view_con['data']) {
                    foreach ($view_con['data'] as $contest) {
                        echo "<option value='".$contest['contest_id']."'>【".$contest['contest_id']."】".$contest['title']."</option>";
                    }  
                }
            }
            ?>
         </select>
        </div>
        <div class="am-form-group" style="white-space: nowrap;">
            <label class="am-u-sm-3 am-form-label"><?php echo $MSG_ScoreMode ?>:</label>
            <div class="am-u-sm-9">
                <label class="am-radio ">
                    <input type="radio" name="mode" value="OI" checked data-am-check required> <?php echo $MSG_OI ?>
                </label>
                <label class="am-radio">
                    <input type="radio" name="mode" value="ACM" data-am-ucheck> <?php echo $MSG_ACM ?>
                </label>
            </div>
        </div>
        <div class="am-form-group" style="white-space: nowrap; margin-top: 20px; ">
            <div class="am-u-sm-8 am-u-sm-offset-4">
                <input type="submit" value="<?php echo $MSG_SUBMIT?>" name="submit" class="am-btn am-btn-success">&nbsp;
                <input type="button" value="<?php echo $MSG_Back ?>"  name="back" onclick="javascript:history.go(-1);" class="am-btn am-btn-secondary">
            </div>
        </div>
    </form>
</div>
<?php require_once("admin-footer.php") ?>
<script src="../plugins/multi-select/js/jquery.quicksearch.js"></script>
<script src="../plugins/multi-select/js/jquery.multi-select.js"></script>
<script language="javascript">
$('.searchable').multiSelect({
  selectionHeader: "<input type='text' class='search-input' autocomplete='off' placeholder='输入关键字进行筛选'>",
  selectableHeader: "<input type='text' class='search-input' autocomplete='off' placeholder='输入关键字进行筛选'>",
  
  afterInit: function(ms){
    var that = this,
        $selectableSearch = that.$selectableUl.prev(),
        $selectionSearch = that.$selectionUl.prev(),
        selectableSearchString = '#'+that.$container.attr('id')+' .ms-elem-selectable:not(.ms-selected)',
        selectionSearchString = '#'+that.$container.attr('id')+' .ms-elem-selection.ms-selected';

    that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
    .on('keydown', function(e){
      if (e.which === 40){
        that.$selectableUl.focus();
        return false;
      }
    });

    that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
    .on('keydown', function(e){
      if (e.which == 40){
        that.$selectionUl.focus();
        return false;
      }
    });
  },
});
</script>