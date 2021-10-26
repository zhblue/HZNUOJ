<?php
/**
 * This file is created
 * by lixun516@qq.com
 * @2021.09.10
 **/
?><?php
$title = $MSG_SeatMap;
require_once("contest_header.php");
function printSeat($seat){
    global $cid, $problem_nums, $MSG_AC, $MSG_WA, $MSG_Activity, $MSG_Idle, $MSG_SUBMISSIONS, $MSG_HINTS;
    if($seat["ip"]!=""){
        echo "<section class='am-panel am-panel-{$seat["style"]}'>\n";
        echo "<header class='am-panel-hd'>\n";
        echo "<b><span class='am-icon-laptop'></span>&nbsp;{$seat["pcname"]}</b>\n";
        if(count($seat["student"])>=1 && $seat["student"][1]["ac_problem"]==$problem_nums){
            echo "<lable style='margin-left: 5px;cursor: pointer;'><font color='red 'title='任务全部完成'><span class='am-icon-trophy'></span></font></lable>";
        } else if($seat["student"][1]["ac_problem"]/$problem_nums<=1/3 && $seat["alarm"]>=2 || $seat["student"][1]["WA"]/$seat["student"][1]["ac_problem"]>=5){
            echo "<lable style='margin-left: 5px;cursor: pointer;' title='急需援助！！'><font color='red'><span class='am-icon-wheelchair'></span></font></lable>";
        } else {
            if($seat["student"][1]["ac_problem"]/$problem_nums>=0.5){
                echo "<lable style='margin-left: 5px;cursor: pointer;'><font color='red'><span title='任务完成过半' class='am-icon-star-half-o'></span></font></lable>";
            }
            if ($seat["alarm"]>2){
                echo "<lable style='margin-left: 5px;cursor: pointer;'><font color='red'><span class='am-icon-warning'></span>{$seat["alarm"]}</font></lable>";
            }
        }
        echo "</header>\n";
        foreach($seat["student"] as $stu){
            echo "<table class='am-table am-table-compact' width='100%'>\n";
            if($stu["real_name"]!=""){
                $str = $stu["real_name"];
            } else $str = $stu["nick"];
            $str .="/".$stu["user_id"];
            echo "<tr><th colspan='2' class='am-text-nowrap'>\n";
            echo "<table width='100%'><tr><td><a style='margin-left: 5px;' href='contestrank.php?cid=$cid#{$stu["user_id"]}'>$str</a></td>";
            echo "<td style='text-align: right'>";
            echo "<a href='javascript:void(0);' title='展开/收起'><span style='margin-left: 5px;cursor: pointer;' class='am-icon-bars' data-am-collapse=\"{target: '#stu-{$stu["user_id"]}'}\"></span></a>";
            if($stu["allow_change_seat"]){
                echo "<a href='seat_map.php?cid=$cid&lock={$stu["id"]}' title='点击锁定用户座位'><font color='red'><span style='margin-left: 5px;' class='am-icon-unlock'></span></font></a>";
            } else {
                echo "<a href='seat_map.php?cid=$cid&lock={$stu["id"]}' title='需要更换座位时点击解锁该用户'><span style='margin-left: 5px;' class='am-icon-lock'></span></a>";
            }
            echo "<a href='seat_map.php?cid=$cid&kickout={$stu["id"]}' title='点击踢出机房'><span style='margin-left: 5px;' class='am-icon-close'></span></a>";
            echo "</td></tr></table>\n";
            echo "</th></tr>\n";
            echo "<tr {$stu["ac_problem_style"]}><th width='1%' class='am-text-nowrap'>".$MSG_AC."：</th><td>".$stu["ac_problem_bar"]."</td></tr>\n";
            echo "<tr {$stu["WAStyle"]}><th class='am-text-nowrap'>".$MSG_WA."：</th><td>".$stu["WA"]."个代码</td></tr>\n";
            echo "</table>\n";
            if(isset($_SESSION['mode']) && $_SESSION['mode']=="all"){
                echo "<div id='stu-{$stu["user_id"]}' class='am-collapse am-in'>";
            } else {
                echo "<div id='stu-{$stu["user_id"]}' class='am-collapse'>";
            }
            echo "<table class='am-table' width='100%'>\n";
            echo "<tr {$stu["activeTimeStyle"]}><th class='am-text-nowrap' width='1%'>".$MSG_Activity."：</th><td>".formatTimeLength($stu["activeTime"])."</td></tr>\n";
            echo "<tr {$stu["idleTimeStyle"]}><th class='am-text-nowrap'>".$MSG_Idle."：</th><td>".formatTimeLength($stu["idleTime"])."</td></tr>\n";
            echo "<tr><th class='am-text-nowrap'>".$MSG_SUBMISSIONS."：</th><td>".$stu["submit"]."个代码</td></tr>\n";
            echo "<tr><th class='am-text-nowrap'>".$MSG_HINTS ."：</th><td>".$stu["hits"]."次</td></tr>\n";
            echo "</table>\n";
            echo "</div>";
        }
        echo "</section>\n";
    } else {
        echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }
}
function printList($seat){
    global $cid, $problem_nums, $i;
    $stuCount = count($seat["student"]);
    foreach($seat["student"] as $stu){
        echo "<tr><td style='vertical-align: center;'>$i</td>\n";
        echo "<td style='vertical-align: center;'>";
        if($seat["pcname"]!=""){
            $pcname=$seat["pcname"];
        } else $pcname=$seat["ip"];
        echo "<b><span class='am-icon-laptop'></span>&nbsp;$pcname</b>\n";
        echo "</td>";
        if($stu["real_name"]!=""){
            $str = $stu["real_name"];
        } else $str = $stu["nick"];
        $str .="/".$stu["user_id"];
        echo "<td>";
        if($stu["ac_problem"]==$problem_nums){
            echo "<lable style='margin-left: 5px;cursor: pointer;'><font color='red 'title='任务全部完成'><span class='am-icon-trophy'></span></font></lable>";
        } else if($stu["ac_problem"]/$problem_nums<=1/3 && $stu["alarm"]>=1 || $stu["WA"]/$stu["ac_problem"]>=5){
            echo "<lable style='margin-left: 5px;cursor: pointer;' title='急需援助！！'><font color='red'><span class='am-icon-wheelchair'></span></font></lable>";
        } else {
            if($stu["ac_problem"]/$problem_nums>=0.5){
                echo "<lable style='margin-left: 5px;cursor: pointer;'><font color='red'><span title='任务完成过半' class='am-icon-star-half-o'></span></font></lable>";
            }
            if ($stu["alarm"]>2){
                echo "<lable style='margin-left: 5px;cursor: pointer;'><font color='red'><span class='am-icon-warning'></span>{$stu["alarm"]}</font></lable>";
            }
        }
        echo "<a style='margin-left: 5px;' href='contestrank.php?cid=$cid#{$stu["user_id"]}'>$str</a></td>\n<td>";
        if($stu["allow_change_seat"]){
            echo "<a href='seat_map.php?cid=$cid&lock={$stu["id"]}' title='点击锁定用户座位'><font color='red'><span style='margin-left: 5px;' class='am-icon-unlock am-icon-sm'></span></font></a>";
        } else {
            echo "<a href='seat_map.php?cid=$cid&lock={$stu["id"]}' title='需要更换座位时点击解锁该用户'><span style='margin-left: 5px;' class='am-icon-lock am-icon-sm'></span></a>";
        }
        echo "<a href='seat_map.php?cid=$cid&kickout={$stu["id"]}' title='点击踢出机房'><span style='margin-left: 5px;' class='am-icon-close am-icon-sm'></span></a></td>\n";
        echo "<td>".$stu["ac_problem_bar"]."</td>\n";
        echo "<td>".$stu["submit"]."个代码</td>\n";
        echo "<td {$stu["WAStyle"]}>".$stu["WA"]."个代码</td>\n";
        echo "<td>".$stu["hits"]."次</td>\n";
        echo "<td {$stu["activeTimeStyle"]}>".formatTimeLength($stu["activeTime"])."</td>\n";
        echo "<td {$stu["idleTimeStyle"]}>".formatTimeLength($stu["idleTime"])."</td>\n";
        echo "</tr>\n";
        $i++;
    }
}
function printOtherStu($stu){
    global $cid;
    if($stu["real_name"]!=""){
        $str = $stu["real_name"];
    } else $str = $stu["nick"];
    $str .="/".$stu["user_id"];
    echo "<lable style='margin-left: 20px;'>";
    if($stu["id"]==Null || $stu["ip"]=="0.0.0.0" ){
        echo "<label>$str";
    } else {
        if($stu["pcname"]==Null){
            echo "<a href='seat_map.php?cid=$cid&kickout={$stu["id"]}' title='从{$stu["ip"]}登入,点击踢出登录'>【?】$str</a>";
        } else{
            echo "<a href='seat_map.php?cid=$cid&kickout={$stu["id"]}' title='点击踢出机房'>【{$stu["pcname"]}】$str</a>";
        }
    }
    echo "</label>";
}
?>

<div class="am-container">
    <div class="am-avg-md-1" style="margin-top: 40px;">
        <section class="am-panel am-panel-default am-scrollable-horizontal">
            <header class="am-panel-hd">
            <table width="100%"><tr>
                <?php 
                echo "<td><b><span class='am-icon-desktop'></span>【{$classroom}】$MSG_SeatMap</b><label style='margin-left: 20px;'>额定 $total 人，已登入 $total_login 人，$total_haveNotStart 人未登入";
                $i = $total_login+$total_haveNotStart-$total;
                if($i>0) echo "，外来 $i 人";
                echo "</td>\n<td style='text-align: right'>";
                echo $MSG_Class;  ?>
                <select id='class'>
                    <option value="" <?php if ($_SESSION['class']=="") echo "selected"; ?> ><?php echo $MSG_ALL ?></option>
                    <option value="null" <?php if ($_SESSION['class']=="null") echo "selected"; ?> >其它</option>
                    <!-- don't remove "其它" option to for loop, if both null and "null" exist, there will occur two options -->
                    <?php 
                    $sz = count($classSet);
                    for ($i=0; $i<$sz; $i++) {
                        if ($classSet[$i]==null || $classSet[$i]=="null" || $classSet[$i]=="其它") continue; 
                    ?>
                        <option value="<?php echo urlencode($classSet[$i]); ?>" <?php if ($_SESSION['class']==$classSet[$i]) echo "selected"; ?> ><?php echo $classSet[$i]; ?></option>
                    <?php } ?>
                </select>
                 <!-- 选择班级后自动跳转页面的js代码 start -->
                <script type="text/javascript">
                var oSelect=document.getElementById("class");
                oSelect.onchange=function() { //当选项改变时触发
                    var valOption=this.options[this.selectedIndex].value; //获取option的value
                    var url = window.location.search;
                    var cid = <?php echo $cid?>;
                    var url = window.location.pathname+"?cid="+cid;
                    url += "&class="+valOption;
                    window.location.href = url;
                }
                </script>
                <!-- 选择班级后自动跳转页面的js代码 end -->
                <?php
                if($is_seat_mode){//座位表模式
                    if(!isset($_SESSION['mode']) || $_SESSION['mode']!="all"){//默认精简模式
                        echo "<label><a style='margin-left: 10px;' title='详细模式' href='./seat_map.php?cid=$cid&mode=all'><span class='am-icon-folder-open-o am-icon-sm'></span></a></label>";
                    } else {
                        echo "<label><a style='margin-left: 10px;' title='精简模式' href='./seat_map.php?cid=$cid&mode=lite'><span class='am-icon-folder-o am-icon-sm'></span></a></label>";
                    }
                    echo "<label><a style='margin-left: 10px;' title='列表模式' href='./seat_map.php?cid=$cid&mode=list'><span class='am-icon-list-alt am-icon-sm'></span></a></label>";
                } else { //列表模式
                    echo "<label><a style='margin-left: 10px;' title='座位表模式' href='./seat_map.php?cid=$cid&mode=lite'><span class='am-icon-table am-icon-sm'></span></a></label>";
                }
                echo "</label><a style='margin-left: 10px;' href='seat_map.php?cid=$cid&kickroom=$room_id";
                if($user_limit) echo "&team";
                echo "' title='把所有登入踢出'><span class='am-icon-trash-o am-icon-sm'></span></a>";
                echo "</td>";
                ?>
            </tr></table>
            </header>
            <main class="am-panel-bd">
            <table width="100%">
            <?php if($is_seat_mode){ ?>
            <!-- 座位表 start -->
                <?php
                    foreach ($view as $row) {
                        echo "<tr>\n";
                        foreach ($row as $cell) {
                            echo "<td style='vertical-align:top;'>";
                            printSeat($cell);
                            echo "</td>\n";
                        }
                        echo "</tr>\n";
                    }
                ?>
                <tr><td align='center' style='vertical-align:middle;' colspan='<?php echo $cols ?>'><label title='讲台'><span class='am-icon-desktop am-icon-lg'></span></label></td></tr>
            <!-- 座位表 end -->
            <?php } else { ?>
            
            <tr><td>
                <!-- 登入列表 start -->
            <style type="text/css" media="screen">
                th.header:hover{
                cursor: pointer;
                }
                th.header:after{
                content: " \f0dc";
                }
                th.headerSortUp:after{
                content: " \f161";
                }
                th.headerSortDown:after{
                content: " \f160";
                }
            </style>
            
            <table class="am-table am-table-striped am-table-hover am-text-nowrap" id="rank_table">
            <thead>
                <tr class="am-text-nowrap" title="按住shift键后再点击排序，可进行多列排序">
                    <th width="1%"><?php echo $MSG_ID ?></th>
                    <th width="5%" class="header"><?php echo $MSG_Seat ?>/IP</th>
                    <th width="5%" class="header"><?php echo $MSG_USER ?></th>
                    <th width="2%"><?php echo $MSG_Operations ?></th>
                    <th width="20%" class="header"><?php echo $MSG_AC ?></th>
                    <th width="5%" class="header"><?php echo $MSG_SUBMISSIONS ?></th>
                    <th width="5%" class="header"><?php echo $MSG_WA ?></th>
                    <th width="5%" class="header"><?php echo $MSG_HINTS ?></th>
                    <th class="header"><?php echo $MSG_Activity.$MSG_Times ?></th>
                    <th class="header"><?php echo $MSG_Idle.$MSG_Times ?></th>

                </tr>
            </thead>
            <?php
                $i = 1;
                foreach ($view as $row) {
                    printList($row[1]);
                }
            ?>
            </table></td></tr>
            <!-- 登入列表 end -->
            <?php } ?>
                <tr><td align='left' colspan='<?php echo $cols ?>'>
                <?php
                    foreach($view_u as $row){
                        printOtherStu($row);
                    }
                ?>
                </td></tr>
            </table>
            </main>
        </section>
    </div>
</div> <!-- /container -->
<?php require_once("footer.php") ?>
<script type="text/javascript" src="plugins/tablesorter/jquery.tablesorter.js"></script>
<script type="text/javascript">
$.tablesorter.addParser({
    id: "num", //指定一个唯一的ID
    is: function(s){
        return false;
    },
    format: function(s){
        var t = s.replaceAll(" ","");
        if(t.indexOf("个代码")>0 || t.indexOf("次")>0){
            t = t.replaceAll("个代码","");
            t = t.replaceAll("次","");
            return parseInt(t);
        }        
        //对 xx天xx时xx分xx秒 数据的处理
        var dayPos, hourPos, minutePos, secondPos;
        var dayNum, hourNum, minuteNum, secondNum;
        if(t.indexOf("秒")==-1){ //英文的时间
            t = t.replaceAll("Days","D");
            t = t.replaceAll("Hours","H");
            t = t.replaceAll("Minutes","M");
            t = t.replaceAll("Seconds","S");
            t = t.replaceAll("Day","D");
            t = t.replaceAll("Hour","H");
            t = t.replaceAll("Minute","M");
            t = t.replaceAll("Second","S");
            dayPos = t.indexOf("D");
            hourPos = t.indexOf("H");
            minutePos = t.indexOf("M");
            secondPos = t.indexOf("S")
        } else { //中文的时间
            dayPos = t.indexOf("天");
            t = t.replaceAll("小时","时");
            hourPos = t.indexOf("时");
            minutePos = t.indexOf("分");
            secondPos = t.indexOf("秒");
        }
        dayNum = parseInt(t.substring(0,dayPos));//xx天
        hourNum= parseInt(t.substring(dayPos+1,hourPos));//xx时
        minuteNum= parseInt(t.substring(hourPos+1,minutePos));//xx分
        secondNum= parseInt(t.substring(minutePos+1,secondPos));//xx秒
        if(dayPos==-1) dayNum = 0;
        if(hourPos==-1) hourNum = 0;
        if(minutePos==-1) minuteNum = 0;
        //将时间换算为秒
        var seconds=dayNum*86400+hourNum*3600+minuteNum*60+secondNum;
        return seconds;
    },
    type: "numeric" //按数值排序
});
$( document ).ready( function () {
    $( "#rank_table" ).tablesorter({headers:{0:{sorter:false},3:{sorter:false},5:{sorter:"num"},6:{sorter:"num"},7:{sorter:"num"},8:{sorter:"num"},9:{sorter:"num"},}});
});
</script>