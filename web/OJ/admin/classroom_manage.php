<?php
/**
 * This file is created
 * by lixun516@qq.com
 * @2021.09.05
 **/
?>

<?php
require("admin-header.php");
if (!HAS_PRI("inner_function")) {
    echo "Permission denied!";
    exit(1);
}
?>
<title><?php echo $html_title . $MSG_roomManage ?></title>
<h1><?php echo $MSG_roomManage?></h1>

<?php
if(!isset($_GET['do'])){
    if(isset($_POST['do'])){
        $do = $_POST['do'];
        require("../include/check_post_key.php");
    }
    $view = array();
    $cnt = 1;
    if ($do != ""){
        $err_str = "";
        $err_cnt = 0;
        $classroom = htmlspecialchars($mysqli->real_escape_string(trim($_POST['classroom'])));
        $rows = intval($_POST['rows']);
        $columns = intval($_POST['columns']);
        $forbid_multiUser = intval($_POST['seat_forbid_multiUser_login']);
        $forbid_multiIP = intval($_POST['user_forbid_multiIP_login']);
        if ($do == $MSG_ADD || $do == $MSG_SUBMIT ){
            if (!preg_match("/^[\u{4e00}-\u{9fa5}_a-zA-Z0-9]{1,60}$/", $classroom) || mb_strlen($classroom, 'utf-8')>20) {
                $err_str .= "输入的{$MSG_classroom}{$MSG_Name}限20个以内的汉字、字母、数字或下划线 ！\\n";
                $err_cnt++;
            } else if (!preg_match("/^[1-9][0-9]{0,1}$/", $rows)) {
                $err_str .= "输入的{$MSG_Rows}要求是介于1~99的整数 ！\\n";
                $err_cnt++;
            } else if (!preg_match("/^[1-9][0-9]{0,1}$/", $columns)) {
                $err_str .= "输入的{$MSG_Cols}要求是介于1~99的整数 ！\\n";
                $err_cnt++;
            } else {
                $sql = "SELECT COUNT(*) FROM `ip_classroom` WHERE `classroom`='$classroom' ";
                if ($do == $MSG_SUBMIT) {
                    $room_id = intval($_POST['room_id']);
                    $sql .= "AND `room_id`<>$room_id";
                    $sql2 = "SELECT COUNT(*) FROM `ip_seat` WHERE `room_id`='$room_id'";
                    $old_nums = $mysqli->query($sql2)->fetch_array()[0];
                }
                if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                    $err_str .= "存在重复的{$MSG_classroom}{$MSG_Name} ！\\n";
                    $err_cnt++;
                }
            }
            if ($err_cnt > 0) {
                echo "<script language='javascript'>\n";
                echo "alert('$err_str')";
                echo "</script>";
            } else {
                $nums = $rows*$columns;
                if ($do == $MSG_SUBMIT) {//修改机房
                    $sql = "UPDATE `ip_classroom` SET `classroom`='$classroom', `rows`='$rows', `columns`='$columns', `seat_forbid_multiUser_login`='$forbid_multiUser', `user_forbid_multiIP_login`='$forbid_multiIP' WHERE `room_id`='$room_id'";
                    $mysqli->query($sql);
                    $nums = $nums - $old_nums;
                    if ($nums>0){ //座位增加,插入空座位
                        $sql = "INSERT INTO `ip_seat`(`room_id`) VALUES('$room_id')";
                        for($i = 2; $i<=$nums; $i++){
                            $sql .= ",('$room_id')";
                        }
                        $mysqli->query($sql) or die("Error! ".$mysqli->error);
                    } else if ($nums<0){//座位减少，把后面多的座位删除
                        $nums = -$nums;
                        $sql = "DELETE FROM `ip_seat` WHERE `seat_id` IN (SELECT t.`seat_id` FROM
                            (SELECT `seat_id` FROM `ip_seat` WHERE `room_id`='$room_id' ORDER BY `seat_id` DESC LIMIT $nums) AS t)";
                        $mysqli->query($sql) or die("Error! ".$mysqli->error);;
                    }
                } else { //新增机房
                    $sql = "INSERT INTO `ip_classroom`(`classroom`, `rows`, `columns`) VALUES('$classroom', '$rows', '$columns')";
                    $mysqli->query($sql);
                    if ($mysqli->affected_rows>0){
                        $insert_id = $mysqli->insert_id;
                        $sql = "INSERT INTO `ip_seat`(`room_id`) VALUES('$insert_id')";
                        for($i = 2; $i<=$nums; $i++){
                            $sql .= ",('$insert_id')";
                        }
                        $mysqli->query($sql) or die("Error! ".$mysqli->error);
                    }
                }
                
                if ($mysqli->affected_rows>0){
                    echo $do.'成功';
                } else {
                    echo $do.'失败';
                }
            }
            if ($do == $MSG_ADD){
                $classroom_add = $classroom;
                $rows_add = $rows;
                $columns_add = $columns;
            }
        } else if ($do == $MSG_DEL) {
            $room_id = intval($_POST['room_id']); 
            $sql = "UPDATE `contest` SET `room_id`=0 WHERE `room_id`='$room_id'";
            $mysqli->query($sql);
            $sql = "DELETE FROM `ip_seat` WHERE `room_id`='$room_id'";
            $mysqli->query($sql);
            $sql = "DELETE FROM `ip_classroom` WHERE `room_id`='$room_id'";
            $mysqli->query($sql);
            if ($mysqli->affected_rows>0){
                echo $do.'成功';
            } else {
                echo $do.'失败';
            }
        }
    }
    $sql = "SELECT * FROM `ip_classroom` ORDER BY `room_id`";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_object()) {
        $view[$cnt][0] = $cnt."<input type='hidden' name='room_id' value='{$row->room_id}'>";
        $view[$cnt][1] = "<input type='text' style='width:200px;' maxlength='20' pattern='^[\u4e00-\u9fa5_a-zA-Z0-9]{1,20}$' name='classroom' value='$row->classroom' required />";
        $view[$cnt][2] = "<input type='number' style='width:50px;' name='rows' min='1' max='99' value='$row->rows' required />";
        $view[$cnt][3] = "<input type='number' style='width:50px;' name='columns' min='1' max='99' value='$row->columns' required />";
        if($row->seat_forbid_multiUser_login==1){
            $check1="checked";
        } else $check1="";
        if($row->user_forbid_multiIP_login==1){
            $check2="checked";
        } else $check2="";
        $view[$cnt][4] = "<label title='禁止在同一个座位/IP上登录多个账号（公网服务器建议解除禁止）'><input type='checkbox' name='seat_forbid_multiUser_login' value='1' $check1 />&nbsp;$MSG_seat_forbid_multiUser_login</label>";
        $view[$cnt][4] .="<br><label title='禁止同一个账号在不同的座位/IP上登录（公网服务器建议解除禁止）'><input type='checkbox' name='user_forbid_multiIP_login' value='1' $check2 />&nbsp;$MSG_user_forbid_multiIP_login</label>";
        $view[$cnt][5] = "<input type='submit' name='do' value='{$MSG_SUBMIT}' class='btn btn-primary'>";
        $view[$cnt][6] = "<a class='btn btn-primary' href='classroom_manage.php?do=edit&room={$row->room_id}'>{$MSG_EDIT}</a>";
        $view[$cnt][7] = "<input type='submit' name='do' value='{$MSG_DEL}' class='btn btn-danger' onclick='javascript:if(confirm(\"{$MSG_DEL}?\")) return true; else return false;'>";
        $cnt++;
    }

    ?>
    <!-- 罗列机房 start -->
    <hr>
    <div class="am-g" style="max-width: 1200px;">
    <div class="am-u-sm-8">
        <table class="table table-hover table-bordered table-condensed table-striped" style="white-space: nowrap;">
            <thead>
            <tr>
                <th width="10px"><?php echo $MSG_ID ?></th>
                <th><?php echo $MSG_classroom.$MSG_Name ?></th>
                <th><?php echo $MSG_Rows ?></th>
                <th><?php echo $MSG_Cols ?></th>
                <th><?php echo $MSG_Login_Option ?></th>
                <th colspan="3" style="text-align: center"><?php echo $MSG_Operations ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($view as $row) {
                echo "<form action='classroom_manage.php' method='post'><tr>\n";
                foreach ($row as $cell) {
                    echo "<td style='vertical-align:middle;'>";
                    require("../include/set_post_key.php");
                    echo $cell;
                    echo "</td>\n";
                }
                echo "</tr></form>\n";
            }
            ?>
            </tbody>
        </table>
    </div>

    <div class="am-u-sm-4">
        <section class="am-panel am-panel-primary">
            <header class="am-panel-hd">
                <h3 class="am-panel-title"><b><?php echo $MSG_ADD . $MSG_classroom ?></b></h3>
            </header>
            <main class="am-panel-bd" style="margin-left: 0px;">
                <form class="am-form am-form-horizontal" action="classroom_manage.php" method="POST">
                    <?php require("../include/set_post_key.php"); ?>
                    <div class="am-form-group" style="white-space: nowrap;">
                        <label class="am-u-sm-4 am-form-label"><?php echo $MSG_classroom.$MSG_Name ?> :</label>
                        <input type="text" style="width:220px;" class="am-u-sm-8 am-u-end" maxlength="20" name="classroom" placeholder="机房一" value="<?php echo $classroom_add ?>" pattern="^[\u4e00-\u9fa5_a-zA-Z0-9]{1,20}$" required/>
                    </div>
                    <div class="am-form-group" style="white-space: nowrap;">
                        <label class="am-u-sm-4 am-form-label"><?php echo $MSG_Rows ?> :</label>
                        <input type='number' style='width:220px;' name='rows' min='1' max='99' placeholder="8" value="<?php echo $rows_add ?>" required />
                    </div>
                    <div class="am-form-group" style="white-space: nowrap;">
                        <label class="am-u-sm-4 am-form-label"><?php echo $MSG_Cols ?> :</label>
                        <input type='number' style='width:220px;' name='columns' min='1' max='99' placeholder="6" value="<?php echo $columns_add ?>" required />";
                    </div>
                    <div class="am-form-group">
                        <div class="am-u-sm-8 am-u-sm-offset-4">
                            <input type="submit" value="<?php echo $MSG_ADD ?>" name="do" class="am-btn am-btn-success">
                        </div>
                    </div>
                </form>
            </main>
            <footer class="am-panel-footer"><b><?php echo $MSG_classroom.$MSG_Name ?></b>限20个以内汉字、字母、数字及下划线</footer>
        </section>
    </div>
    </div>
<!-- 罗列机房 end -->
<?php
} else {
    if(isset($_POST['seat'])){
        require("../include/check_post_key.php");
        $room_id = intval($_POST['room']);
        $seat_id = intval($_POST['seat']);
        $tmp = explode("|", $_POST['ip']);
        $ip = $mysqli->real_escape_string($tmp[0]);
        $pcname = $mysqli->real_escape_string($tmp[1]);
        if($ip=="-1"){
            $sql = "UPDATE `ip_seat` SET `ip`='' WHERE `room_id`='$room_id'";
        } else $sql = "UPDATE `ip_seat` SET `ip`='$ip' WHERE `seat_id`='$seat_id'";
        $result = $mysqli->query($sql);
    }
    $view = array();
    $view_ip = array();
    if(isset($_GET['room'])){
        $room_id = intval($_GET['room']);
    }
    $sql = "SELECT COUNT(*) FROM `ip_seat` WHERE `room_id`='$room_id'";
    $seat_num = $mysqli->query($sql)->fetch_array()[0];
    $sql = "SELECT * FROM `ip_classroom` WHERE `room_id`='$room_id'";
    $result = $mysqli->query($sql);
    if ($result->num_rows>0) {
        $row = $result->fetch_object();
        $classroom = $row->classroom;
        $rows = $row->rows;
        $cols = $row->columns;
        $nums = $rows*$cols - $seat_num;
        if ($nums>0){ //规划座位比实际座位多增加,插入空座位
            $sql = "INSERT INTO `ip_seat`(`room_id`) VALUES('$room_id')";
            for($i = 2; $i<=$nums; $i++){
                $sql .= ",('$room_id')";
            }
            $mysqli->query($sql) or die("Error! ".$mysqli->error);
        } else if ($nums<0){//规划座位比实际座位少，把后面多的座位删除
            $nums = -$nums;
            $sql = "DELETE FROM `ip_seat` WHERE `seat_id` IN (SELECT t.`seat_id` FROM
                (SELECT `seat_id` FROM `ip_seat` WHERE `room_id`='$room_id' ORDER BY `seat_id` DESC LIMIT $nums) AS t)";
            $mysqli->query($sql) or die("Error! ".$mysqli->error);;
        }
        $sql = "SELECT s.*, i.`pcname` FROM `ip_seat` as s LEFT JOIN `ip_list` as i ON s.`ip`=i.`ip` WHERE `room_id`='$room_id' ORDER BY s.`seat_id` DESC";
        $result = $mysqli->query($sql);
        $r = 1;
        $c = 1;
        while($row = $result->fetch_object()){
            $view[$r][$c]["seat_id"] = $row->seat_id;
            $view[$r][$c]["ip"] = $row->ip;
            $view[$r][$c]["pcname"] = $row->pcname;
            $c++;
            if($c > $cols){
                $r++;
                $c = 1;
            }
        }
       
        $sql = "SELECT * FROM `ip_list` WHERE `ip` NOT IN (SELECT `ip` FROM `ip_seat` WHERE `room_id`='$room_id') ORDER BY `pcname`";
        $result = $mysqli->query($sql);
        $r = 1;
        $c = 1;
        $cols_ip = 4;
        $view_ip[$r][$c]["ip_post"] = "";
        $view_ip[$r][$c]["pcname"] = "&nbsp;&nbsp;解绑单个座位";
        $view_ip[$r][$c]["ip"] = "";
        $view_ip[$r][$c]["checked"] = $ip==""?"checked":"";
        $c++;
        if($c > $cols_ip){
            $r++;
            $c = 1;
        }
        $view_ip[$r][$c]["ip_post"] = "-1";
        $view_ip[$r][$c]["pcname"] = "&nbsp;&nbsp;解绑全部座位";
        $view_ip[$r][$c]["ip"] = "";
        $view_ip[$r][$c]["checked"] = $ip=="-1"?"checked":"";
        $c++;
        if($c > $cols_ip){
            $r++;
            $c = 1;
        }
        if($ip=="" || $ip=="-1") $is_checked=true; else $is_checked=false;
        if($row = $result->fetch_object()){
            $view_ip[$r][$c]["ip_post"] = $row->ip."|".$row->pcname;
            $view_ip[$r][$c]["pcname"] = "【".$row->pcname."】";
            $view_ip[$r][$c]["ip"] = $row->ip;
            if(!$is_checked && "a".$row->pcname > "a".$pcname){//"a".  避免例如2E6>4E5这种情况出现
                $view_ip[$r][$c]["checked"] = "checked";
                $is_checked=true;
            } else $view_ip[$r][$c]["checked"] = "";
            $c++;
            if($c > $cols_ip){
                $r++;
                $c = 1;
            }
        }
        while($row = $result->fetch_object()){
            $view_ip[$r][$c]["ip_post"] = $row->ip."|".$row->pcname;
            $view_ip[$r][$c]["pcname"] = "【".$row->pcname."】";
            $view_ip[$r][$c]["ip"] = $row->ip;
            if(!$is_checked && "a".$row->pcname > "a".$pcname){
                $view_ip[$r][$c]["checked"] = "checked";
                $is_checked=true;
            } else $view_ip[$r][$c]["checked"] = "";
            $c++;
            if($c > $cols_ip){
                $r++;
                $c = 1;
            }
        }
    } else {
        $sql = "DELETE FROM `ip_classroom` WHERE `room_id`='$room_id'";
        $mysqli->query($sql);
    }

?>
<h4>先在下方IP列表中选择座位对应的IP地址，再点击对应的机位进行绑定，空机位在前台页面不显示（若整列都是空机位看起来就是过道）。</h4>
<hr>
<form class="am-form am-form-horizontal" action="classroom_manage.php?do=edit" method="POST">
<?php require("../include/set_post_key.php"); ?>
<input type='hidden' name='room' value='<?php echo $room_id ?>'>
<div class="am-g" style="max-width: 1200px;">
    <div class="am-u-sm-12">
        <section class="am-panel am-panel-primary">
            <header class="am-panel-hd">
                <h3 class="am-panel-title"><b><?php echo $classroom.$MSG_SeatMap ?></b></h3>
            </header>
            <main class="am-panel-bd" style="margin-left: 0px;">
                <table class="table table-condensed" style="white-space: nowrap;">
                    <?php
                    foreach ($view as $row) {
                        echo "<tr><td align='center' style='vertical-align:middle;'>$rows</td>\n";
                        foreach ($row as $cell) {
                            echo "<td align='center' style='vertical-align:middle;'>";
                            if($cell["ip"]!=""){
                                echo "<button type='submit' value='{$cell["seat_id"]}' name='seat' title='{$cell["ip"]}' class='am-btn am-btn-primary am-radius'><span class='am-icon-laptop'></span>&nbsp;{$cell["pcname"]}</button>";
                            } else {
                                echo "<button type='submit' value='{$cell["seat_id"]}' name='seat' class='am-btn am-btn-default'><span class='am-icon-laptop'></span></button>";
                            }
                            echo "</td>\n";
                        }
                        echo "</tr>\n";
                        $rows--;
                    }
                    echo "<tr><td>&nbsp;</td>";
                    for($i=1; $i<=$cols; $i++){
                        echo "<td align='center' style='vertical-align:middle;'>$i</td>";
                    }
                    echo "</tr>\n<tr><td align='center' style='vertical-align:middle;'><a href='classroom_manage.php'><span class='am-icon-reply am-icon-md'></span></a></td><td align='center' style='vertical-align:middle;' colspan='$cols'><span class='am-btn am-btn-default' title='讲台'><span class='am-icon-desktop am-icon-lg'></span></span></td></tr>";
                    ?>
                </table>
            </main>
        </section>
    <!-- </div>
    <div class="am-u-sm-5"> -->
        <section class="am-panel am-panel-primary">
            <header class="am-panel-hd">
                <h3 class="am-panel-title"><b>IP<?php echo $MSG_LIST ?></b></h3>
            </header>
            <main class="am-panel-bd" style="margin-left: 0px;">
                <table class="table table-condensed" style="white-space: nowrap;">
                    <?php
                    foreach ($view_ip as $row) {
                        echo "<tr>\n";
                        foreach ($row as $cell) {
                            echo "<td align='left' style='vertical-align:middle;'>";
                            echo "<span align='left' class='am-input-group-label' style='text-align: left;'><input type='radio' value='{$cell["ip_post"]}' name='ip' {$cell["checked"]}>&nbsp;<span class='am-icon-laptop'></span>{$cell["pcname"]}{$cell["ip"]}</span>";
                            echo "</td>\n";
                        }
                        echo "</tr>\n";
                    }
                    ?>
                </table>
            </main>
        </section>
    </div>
</div>
<?php
}
require_once("admin-footer.php")
?>
