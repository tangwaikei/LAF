<?php

/*
* 预处理与权限控制文件
× 
× 用户权限
× -1 冻结
×  0 非微信浏览器游客
×  1 微信浏览器游客
×  2 普通用户
×  9 管理员
×
× 错误代码
×  0 正常
× -1 需要登陆
× -2 权限不足
* -11 资源不存在
* -12 结果异常
*/
//开启session 
session_start();
require "func.php";
header("Content-Type:text/html;charset=UTF-8");

//数据库连接
$localPdo = new PDO(
    "mysql:host=".SAE_MYSQL_HOST_M.";port=".SAE_MYSQL_PORT.";dbname=".SAE_MYSQL_DB.";charset=utf8", 
    SAE_MYSQL_USER, 
    SAE_MYSQL_PASS
);

//页面访问控制
$fun = isset($_GET['fun'])? $_GET['fun'] : (isset($_POST['fun']) ? $_POST['fun'] : 'list');
$access = array(
    "list" => 0, "details" => 0,
    "comment" => 1,
    "user" => 2, "edit" => 2, 'del' => 2
);

//API访问
$api = isset($_GET['api']) ? $_GET['api'] : (isset($_POST['api']) ? $_POST['api'] : '');
if ($api == "csbxfd") {
    $_SESSION['rank'] = 2;
} else {
	unset($api);
}

//变量赋值
$_SESSION['userId'] = isset($_SESSION['userId']) ? $_SESSION['userId'] : '';
$_SESSION['nick'] = isset($_SESSION['nick']) ? $_SESSION['nick'] : '微信用户';
if (!isset($_SESSION['rank'])){
    $_SESSION['rank'] = strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ? 1 : 0;
}

//浏览器方权限
if (!isset($_COOKIE['rank']) || ($_COOKIE['rank'] != $_SESSION['rank'])) {
    setcookie('rank', $_SESSION['rank']);
}

//判断访问权限
if (!isset($access[$fun]) || ($access[$fun] > $_SESSION['rank'])) {
    if($_SESSION['userId']) {
        $answer = array('code' => -2, 'msg' => '无权访问！', 'data' => '');
    } else {
    	$answer = array('code' => -1, 'msg' => '请先登陆！', 'data' => '');
    }
    echo json_encode($answer);
    exit;
}
?>