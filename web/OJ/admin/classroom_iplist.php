<?php
/**
 * This file is created
 * by lixun516@qq.com
 * @2021.09.04
 **/
?>

<?php
require("admin-header.php");
if (!HAS_PRI("inner_function")) {
    echo "Permission denied!";
    exit(1);
}

if(isset($_POST['do'])){
    require "../include/check_post_key.php";
    $do = $_POST['do'];
}
$action = trim($_GET['action']);
$view = array();
$cnt = 1;
if ($action == ""){
    if ($do == $MSG_ADD){
        $err_str = "";
        $err_cnt = 0;
        $pcname = htmlspecialchars($mysqli->real_escape_string(trim($_POST['pcname'])));
        $ip = trim($_POST['ip']);
        if (!preg_match("/^[\u{4e00}-\u{9fa5}_a-zA-Z0-9]{1,60}$/", $pcname) || mb_strlen($pcname, 'utf-8')>20) {
            $err_str .= "输入的{$MSG_ComputerName}限20个以内的汉字、字母、数字或下划线 ！\\n";
            $err_cnt++;
        } else if (!filter_var($ip, FILTER_VALIDATE_IP)){
            $err_str .= "请输入合法的IP地址 ！\\n";
            $err_cnt++;
        } else {
            $overwrite = trim($_POST['overwrite']);
            switch($overwrite){
                case "none":
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `pcname`='$pcname' OR `ip`='$ip'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $err_str .= "存在重复的{$MSG_ComputerName}或者IP地址 ！\\n";
                        $err_cnt++;
                    }
                    break;
                case "pcname":
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `ip`='$ip'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $err_str .= "存在重复的IP地址 ！\\n";
                        $err_cnt++;
                    }
                    break;
                case "ip":
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `pcname`='$pcname'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $err_str .= "存在重复的{$MSG_ComputerName} ！\\n";
                        $err_cnt++;
                    }
                    break;
            }
        }
        if ($err_cnt > 0) {
            echo "<script language='javascript'>\n";
            echo "alert('$err_str');";
            echo "</script>";
        } else {
            switch($overwrite){
                case "none":
                    $sql = "INSERT INTO `ip_list`(`pcname`, `ip`) VALUES('$pcname','$ip')";
                    break;
                case "pcname":
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `pcname`='$pcname' AND `ip`<>'$ip'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $sql = "UPDATE `ip_list` SET `ip`= '$ip' WHERE `pcname`='$pcname'";
                    } else $sql = "INSERT INTO `ip_list`(`pcname`, `ip`) VALUES('$pcname','$ip')";
                    break;
                case "ip":
                    $sql = "INSERT INTO `ip_list`(`pcname`, `ip`) VALUES('$pcname','$ip') ON DUPLICATE KEY UPDATE `pcname`=VALUES(pcname)";
                    break;
            }
            $mysqli->query($sql);
            if ($mysqli->affected_rows>0){
                echo $MSG_ADD.' 【'.$pcname.'】'.$ip;
            }
        }
    } else if ($do == $MSG_DEL){
        $sql = "DELETE FROM `ip_list` WHERE `ip_id` IN (". implode(",", $_POST['ip_id']) .")";
        $mysqli->query($sql);
        if ($mysqli->affected_rows>=0){
            echo '成功删除'.$mysqli->affected_rows.'个IP';
        }
    }
    $sql = "SELECT * FROM `ip_list` ORDER BY pcname";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_object()) {
        $view[$cnt][0] = "<input type=checkbox name='ip_id[]' value='$row->ip_id' />&nbsp;" . $cnt;
        $view[$cnt][1] = $row->pcname;
        $view[$cnt][2] = $row->ip;
        $cnt++;
    }
} else if ($action == "import"){
    if ($do == $MSG_IMPORT){
        $overwrite = trim($_POST['overwrite']);
        $pcname = explode("\n", trim($_POST['pcname']));
        $ip = explode("\n", trim($_POST['ip']));
        $report = array();
        foreach ($pcname as $key => $value) {
            $pcname[$key] = $mysqli->real_escape_string(str_replace("\r", "", trim($pcname[$key])));
            $report[$key]['pcname'] = $pcname[$key];
            $ip[$key] = $mysqli->real_escape_string(str_replace("\r", "", trim($ip[$key])));
            $report[$key]['ip'] = $ip[$key];
            if (!preg_match("/^[\u{4e00}-\u{9fa5}_a-zA-Z0-9]{1,60}$/", $pcname[$key]) || mb_strlen($pcname[$key], 'utf-8')>20) {
                $report[$key]['status'] =  "{$MSG_ComputerName}限20个以内的汉字、字母、数字或下划线";
                continue;
            } else if (!filter_var($ip[$key], FILTER_VALIDATE_IP)){
                $report[$key]['status'] =  "不合法的IP地址";
                continue;
            }
            $report[$key]['status'] = "Success";
            switch($overwrite){
                case "none":
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `pcname`='".$pcname[$key]."' OR `ip`='".$ip[$key]."'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $report[$key]['status'] = "存在重复的{$MSG_ComputerName}或者IP地址";
                        continue;
                    }
                    $sql = "INSERT INTO `ip_list`(`pcname`, `ip`) VALUES('".$pcname[$key]."','".$ip[$key]."')";
                    break;
                case "pcname":
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `ip`='".$ip[$key]."'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $report[$key]['status'] = "存在重复的IP地址";
                        continue;
                    }
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `pcname`='".$pcname[$key]."' AND `ip`<>'".$ip[$key]."'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $sql = "UPDATE `ip_list` SET `ip`= '".$ip[$key]."' WHERE `pcname`='".$pcname[$key]."'";
                    } else $sql = "INSERT INTO `ip_list`(`pcname`, `ip`) VALUES('".$pcname[$key]."','".$ip[$key]."')";
                    break;
                case "ip":
                    $sql = "SELECT COUNT(*) FROM `ip_list` WHERE `pcname`='".$pcname[$key]."'";
                    if ($mysqli->query($sql)->fetch_array()[0] > 0) {
                        $report[$key]['status'] = "存在重复的$MSG_ComputerName";
                        continue;
                    }
                    $sql = "INSERT INTO `ip_list`(`pcname`, `ip`) VALUES('".$pcname[$key]."','".$ip[$key]."') ON DUPLICATE KEY UPDATE `pcname`=VALUES(pcname)";
                    break;
            }
            $mysqli->query($sql);
            if ($mysqli->affected_rows<0){
                $report[$key]['status'] = "Fail";
            }
        }
    }

}
?>
<title><?php echo $html_title . $MSG_classroom . 'IP' . $MSG_LIST ?></title>
<h1><?php echo $MSG_classroom . 'IP' . $MSG_LIST ?></h1>
<div class="am-avg-md-1" style="margin-top: 20px; margin-bottom: 20px;">
    <ul class="am-nav am-nav-tabs">
        <li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>
        <?php if ($action=="") { ?>
        <li class="am-active"><a href="classroom_iplist.php"><?php echo 'IP' . $MSG_LIST ?></a></li>
        <li><a href="classroom_iplist.php?action=import"><?php echo $MSG_IMPORT . 'IP' ?></a></li>
        <?php } else { ?>
        <li><a href="classroom_iplist.php"><?php echo 'IP' . $MSG_LIST ?></a></li>
        <li class="am-active"><a href="classroom_iplist.php?action=import"><?php echo $MSG_IMPORT . 'IP' ?></a></li>
        <?php } ?>
    </ul>
</div>
<?php
if ($action == ""){
?>
<!-- 罗列IP start -->
<div class="am-g" style="max-width: 1000px;">
<div class="am-u-sm-7">
    <form class="form-inline" method='POST' action='classroom_iplist.php'>
        <?php require_once("../include/set_post_key.php"); ?>
        <table class="table table-hover table-bordered table-condensed table-striped" style="white-space: nowrap;">
            <thead>
            <tr>
                <td colspan="3">
                <input type='submit' name='do' class='btn btn-default' value='<?php echo $MSG_DEL ?>' onclick='javascript:if(confirm("<?php echo $MSG_DEL ?>?")) return true; else return false;'>
                </td>
            </tr>
            <tr>
                <th width="10px"><input type=checkbox style='vertical-align:2px;' onchange='$("input[type=checkbox]").prop("checked", this.checked)'>&nbsp;<?php echo $MSG_ID ?></th>
                <th><?php echo $MSG_ComputerName ?></th>
                <th>IP</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($view as $row) {
                echo "<tr>\n";
                foreach ($row as $table_cell) {
                    echo "<td style='vertical-align:middle;'>";
                    echo $table_cell;
                    echo "</td>\n";
                }
                echo "</tr>\n";
            }
            ?>
            </tbody>
        </table>
    </form>
</div>
<div class="am-u-sm-5">
    <section class="am-panel am-panel-primary">
        <header class="am-panel-hd">
            <h3 class="am-panel-title"><b><?php echo $MSG_ADD . ' IP' ?></b></h3>
        </header>
        <main class="am-panel-bd" style="margin-left: 0px;">
            <form class="am-form am-form-horizontal" action="classroom_iplist.php" method="POST">
                <?php require("../include/set_post_key.php"); ?>
                <div class="am-form-group" style="white-space: nowrap;">
                    <label class="am-u-sm-4 am-form-label"><?php echo $MSG_ComputerName ?> :</label>
                    <input type="text" style="width:220px;" class="am-u-sm-8 am-u-end" maxlength="20" name="pcname" placeholder="1A1" value="<?php echo $pcname ?>" pattern="^[\u4e00-\u9fa5_a-zA-Z0-9]{1,20}$" required/>
                </div>
                <div class="am-form-group" style="white-space: nowrap;">
                    <label class="am-u-sm-4 am-form-label">IP :</label>
                    <input type="text" style="width:220px;" class="am-u-sm-8 am-u-end" name="ip" placeholder="192.168.1.1" value="<?php echo $ip ?>" required/>
                </div>
                <div class="am-form-group" style="white-space: nowrap;">
                    <label class="am-form-label"><input type="radio" name="overwrite" value="none" checked />不覆盖同名数据</label><br>
                    <label class="am-form-label"><input type="radio" name="overwrite" value="pcname"/>覆盖同名计算机的IP地址</label><br>
                    <label class="am-form-label"><input type="radio" name="overwrite" value="ip"/>覆盖相同IP地址的计算机名</label>
                </div>
                <div class="am-form-group">
                    <div class="am-u-sm-8 am-u-sm-offset-4">
                        <input type="submit" value="<?php echo $MSG_ADD ?>" name="do" class="am-btn am-btn-success">
                    </div>
                </div>
            </form>
        </main>
        <footer class="am-panel-footer"><b><?php echo $MSG_ComputerName ?></b>限20个以内汉字、字母、数字及下划线</footer>
    </section>
</div>
</div>
<!-- 罗列IP end -->
<?php
} else if ($action == "import"){
?>
<div class="am-g" style="max-width: 1000px;">
<div class="am-u-sm-10">
<?php
    if ($do == $MSG_IMPORT){
?>
<!-- 输出导入结果 start -->
    <input type="button" name="submit" value="返回" onclick="javascript:history.go(-1);" style="margin-bottom: 20px;">
    <table class="table table-hover table-bordered table-condensed table-striped" style="white-space: nowrap;width:600px">
      <thead>
       <tr>
          <th colspan="3">导入模式：
          <?php 
            switch($overwrite){
                case "none":
                    echo "不覆盖同名数据";
                    break;
                case "pcname":
                   echo "覆盖同名计算机的IP地址";
                    break;
                case "ip":
                    echo "覆盖相同IP地址的计算机名";
                    break;
            }
          ?>
        </th>
        </tr>
        <tr>
		  <th><?php echo $MSG_ComputerName ?></th>
		  <th>IP</th>
          <th><?php echo $MSG_STATUS ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($report as $row) {
          echo "<tr>\n";
          foreach ($row as $x){
            echo "<td>" . $x . "</td>\n";
          }
          echo "</tr>\n";
        }
        ?>
      </tbody>
      </table>
<!-- 输出导入结果 end -->
<?php
} else {
?>
<form class="am-form am-form-horizontal" action="classroom_iplist.php?action=import" method="post">
    <?php require_once("../include/set_post_key.php"); ?>
    <div class="am-form-group" style="white-space: nowrap;">
         <div class="am-u-sm-3">
            <label class="am-form-label"><input type="radio" name="overwrite" value="none" checked />不覆盖同名数据</label><br>
            <label class="am-form-label"><input type="radio" name="overwrite" value="pcname"/>覆盖同名计算机的IP地址</label><br>
            <label class="am-form-label"><input type="radio" name="overwrite" value="ip"/>覆盖相同IP地址的计算机名</label>
        </div>
        <div class="am-u-sm-2 am-u-end">
            <input type="submit" value="<?php echo $MSG_IMPORT ?>" name="do" class="am-btn am-btn-success">
        </div>
    </div>
    <div class="am-form-group" style="white-space: nowrap;">
        <div class="am-u-sm-4">
            <label class="am-form-label"><font color='red'><b>*</b></font>&nbsp;<?php echo $MSG_ComputerName ?>:</label>
            <textarea name="pcname" rows="20" style="width:205px;" placeholder="*示例：1个<?php echo $MSG_ComputerName ?>占1行，中间不留空行<?php echo "\n1A1\n1A2\n1A3\n\n每个限20个以内汉字、字母、数字及下划线" ?>" required></textarea>
        </div>
        <div class="am-u-sm-4 am-u-end">
            <label class="am-form-label"><font color='red'><b>*</b></font>&nbsp;IP:</label>
            <textarea name="ip" rows="20" style="width:205px;" placeholder="*示例：1个IP占1行，中间不留空行<?php echo "\n192.168.1.1\n192.168.1.2\n192.168.1.3\n" ?> "></textarea>
        </div>
    </div>
</form>
<?php } ?>
</div></div>
<?php
}
require_once("admin-footer.php")
?>