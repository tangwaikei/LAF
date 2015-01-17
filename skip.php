<?php
/*
* 页面跳转，获取用户权限
*/
//开启session 
session_start();
require "func.php";

//获取用户信息
if (isset($_GET['user'])) {
    $request = array('url' => "http://aick.sinaapp.com/api.php?method=getinfo&user=".$_GET['user']);
    $result = requests($request);
    $userInfo = json_decode($result['content'], 1);
    if ($userInfo['状态'] == 0) {
        $_SESSION['userId'] = $userInfo['学号'] ? $userInfo['学号']: $_GET['user'];
        $_SESSION['nick'] = $userInfo['昵称'] ? $userInfo['昵称']: '微信用户';
        $_SESSION['rank'] = ($userInfo['权限'] == 9)? '9': '2';
    }
}
//设置SESSION
if (!isset($_SESSION['rank'])) {
    $_SESSION['rank'] = (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) ? 1: 0;
}
//设置COOKIES且返回首页
setcookie('rank', $_SESSION['rank']);
header('Location: ./?20150117-2');
?>