<?php
/*
* 功能实现模块
*/
require "config.php";
//增加条目
if ($fun == "edit") {
    //图片上传,返回图片地址
    if (isset($_FILES['picture']) && !$_FILES['picture']['error']) {
        $fileUrl = file_update($_FILES['picture']['tmp_name']);
    } else {
        $fileUrl = (isset($_POST['img']) && $_POST['img'])? $_POST['img']: '';
    }
    //消息类型
    if (!in_array($_POST['type'], array(1,2,3)) || ($_POST['type'] == 1) && ($_SESSION['rank'] != 9)) {
        if (isset($api)) {
            $answer = array('code' => -2, 'msg' => '发布类型选择异常！', 'data' => '');
            echo json_encode($answer);
        } else {
            echo '<script>alert("发布失败，可能是数据有误！");window.history.back();</script>';
        }
        exit;
    }
    //日期
    if (!isset($_POST['data']) || !$_POST['data']) {
        $_POST['data'] = date("Y-m-d");
    }

    //api提交判断重复
    if (isset($api)) {
        $localSt = $localPdo -> prepare("SELECT `id` FROM `laf` WHERE `title` = ? AND `details` = ? AND `details` != ''");
        $localSt -> execute(array($_POST['title'], $_POST['details']));
        $result = $localSt -> fetch();
        $editId = $result? $result[0]: '';
    }
    //数据库操作
    if (!isset($api) || !$editId) {
        if(isset($_POST['id']) && $_POST['id']) {
            //sql语句
            $sql = "SELECT `userId` FROM `laf` WHERE `id` = ? AND `deleted` = 0";
            $sqlArr = array($_POST['id']);
            //pdo
            $localSt = $localPdo -> prepare($sql);
            $localSt -> execute($sqlArr);
            //选取sql中一条
            $result = $localSt -> fetch();
            //判断修改权限
            if (!isset($_SESSION['userId']) || !$_SESSION['userId']) {
                $pass = false;
            } else if ($_SESSION['userId'] == $result['userId']) {
                $pass = true;
            } else if ($_SESSION['rank'] == 9) {
                $pass = true;
            } else {
                $pass = false;
            }
            //权限验证未通过
            if (isset($api) && !$pass) {
                $answer = array('code' => -2, 'msg' => '权限不足！', 'data' => '');
                echo json_encode($answer);
                exit;
            } else if (!$pass) {
                echo '<script>alert("修改失败，非文章所有者！");window.history.back();</script>';
                exit;
            }
            //sql语句
            $sql = "UPDATE `laf` SET 
                `type` = ?,
                `title` = ?,
                `date` = ?,
                `where` = ?,
                `linkmen` = ?,
                `studentId` = ?,
                `phone` = ?,
                `qq` = ?,
                `details` = ?,
                `picture` = ?,
                `ip` = ?
                 WHERE `id` = ? ";
            //sql数组
            $sqlArr = array(
                $_POST['type'],
                $_POST['title'],
                $_POST['date'],
                $_POST['where'],
                $_POST['linkmen'],
                $_POST['studentId'],
                $_POST['phone'],
                $_POST['qq'],
                $_POST['details'],
                $fileUrl,
                get_ip(),
                $_POST['id']
            );
            //PDO
            $localSt = $localPdo -> prepare($sql);
            $localSt -> execute($sqlArr);
            //返回插入数组的自增id
            $editId = $_POST['id'];
        //增加
        } else {
            //sql语句
            $sql = "INSERT INTO `laf` VALUES(NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '[]', 0, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0)";
            //sql数组
            $sqlArr = array(
                $_SESSION['userId'],
                $_POST['type'],
                $_POST['title'],
                $_POST['date'],
                $_POST['where'],
                $_POST['linkmen'],
                $_POST['studentId'],
                $_POST['phone'],
                $_POST['qq'],
                $_POST['details'],
                $fileUrl,
                get_ip()
            );
            //PDO
            $localSt = $localPdo -> prepare($sql);
            $localSt -> execute($sqlArr);
            //返回插入数组的自增id
            $editId = $localPdo -> lastInsertId();
        }
    }
    if (isset($api)) {
        $answer = array('code' => 0, 'msg' => '发布成功！', 'data' => '', 'info' => array('editId' => $editId));
        echo json_encode($answer);
    } else {
        header('Location: ./#/details?id='.$editId);
    }
}
//查看详情
else if (($fun == 'details') && isset($_GET['id'])) {
    //sql语句
    $sql = "SELECT * FROM `laf` WHERE `id` = ? AND `deleted` = 0";
    $sqlArr = array($_GET['id']);
    //pdo
    $localSt = $localPdo -> prepare($sql);
    $localSt -> execute($sqlArr);
    //选取sql中一条
    $result = $localSt -> fetch();
    if ($result) {
        //清除未读评论
        if ($_SESSION['userId'] == $result['userId']) {
            $sqlArr = array($_GET['id']);
            $localSt = $localPdo -> prepare("UPDATE laf SET notice=0 WHERE id=?");
            $localSt -> execute($sqlArr);
        }

        //判断所有者
        if (isset($_SESSION['userId']) && $_SESSION['userId'] && ($_SESSION['userId'] == $result['userId'])) {
            $result['own'] = 1;
        } else if ($_SESSION['rank'] == 9) {
            $result['own'] = 1;
        } else {
            //非所有者
            $result['own'] = 0;
            //提示为0
            $result['notice'] = 0;
        }
        //去除不该输出的东西
        $count = count($result)/2;
        for ($i = 0;$i < $count;$i++) {
            unset($result[$i]);
        }
        unset($result['ip']);
        unset($result['time']);
        unset($result['userId']);
        unset($result['deleted']);

        //获取评论内容
        $result['comments'] = json_decode($result['comments'], 1);
        //去除评论中的敏感信息
        for ($i = 0; $i < count($result['comments']); $i++) {
            unset($result['comments'][$i]['userId']);
            unset($result['comments'][$i]['ip']);
        }
        $answer = array('code' => 0, 'msg' => '获取成功！', 'data' => $result);
    } else {
        $answer = array('code' => -11, 'msg' => '资源不存在！', 'data' => '');
    }
    echo json_encode($answer);
}
//删除
else if (($fun == 'del') && isset($_GET['id'])) {
    //sql与sql数组
    $sql = "SELECT * FROM `laf` WHERE `id` = ? AND `deleted` = 0";
    $sqlArr = array($_GET['id']);
    //pdo
    $localSt = $localPdo -> prepare($sql);
    $localSt -> execute($sqlArr);
    //获取其中一条
    $result = $localSt -> fetch();

    //判断权限
    if (!isset($_SESSION['userId']) || !$_SESSION['userId']) {
        $pass = false;
    } else if ($_SESSION['userId'] == $result['userId']) {
        $pass = true;
    } else if ($_SESSION['rank'] == 9) {
        $pass = true;
    } else {
        $pass = false;
    }

    if ($result && $pass) {
        //将条目的删除属性置为1
        $sql = "UPDATE laf SET deleted=1 WHERE id=?";
        $sqlArr = array($_GET['id']);
        $localSt = $localPdo -> prepare($sql);
        $localSt -> execute($sqlArr);
        $answer = array('code'=>0,'msg'=>'删除成功！','data'=>'');
    } else {
        $answer = array('code'=>-12,'msg'=>'删除失败，可能是已经被删除或者非所有者！','data'=>'');
    }
    echo json_encode($answer);
}
//用户评论
else if (($fun == 'comment') && isset($_GET['id']) && isset($_GET['comment'])) {
    //sql与sql数组
    $sql = "SELECT * FROM `laf` WHERE `id` = ? AND `deleted` = 0";
    $sqlArr = array($_GET['id']);
    //pdo
    $localSt = $localPdo -> prepare($sql);
    $localSt -> execute($sqlArr);
    //取其中一条
    $result = $localSt -> fetch();

    if ($result) {
        //获取所有评论
        @$comments = json_decode($result['comments'], 1);
        //当前评论
        $comment = array(
            'nick' => $_SESSION['nick'],
            'userId' => $_SESSION['userId'],
            'content' => $_GET['comment'],
            'time' => date('y-m-d H:i:s', time()),
            'ip' => get_ip()
        );
        //添加评论
        if ($comments) {
            $comment['id'] = $comments[0]['id'] + 1;
            array_unshift($comments, $comment);
        } else {
            $comment['id'] = 1;
            $comments = array($comment);
        }

        //不是本人评论则未读评论+1
        if (isset($_SESSION['userId']) && ($_SESSION['userId'] == $result['userId'])) {
            $notice = $result['notice'];
        } else {
            $notice = $result['notice'] + 1;
        }

        //加入数据库
        $sql = "UPDATE `laf` SET `comments` = ?, `notice` = ? WHERE `id` = ?";
        $sqlArr = array(json_encode($comments), $notice, $_GET['id']);
        $localSt = $localPdo -> prepare($sql);
        $localSt -> execute($sqlArr);

        //输出评论
        unset($comment['ip']);
        unset($comment['userId']);
        $answer = array('code' => 0, 'msg' => '评论成功！', 'data' => $comment);
    } else $answer = array('code' => -11, 'msg' => '资源不存在！', 'data' => '');
    echo json_encode($answer);
}
else if (($fun == "list") || ($fun == 'user')) {
    //构造SQL语句
    $sql = "SELECT * FROM `laf` WHERE (`deleted` = 0)";
    $sqlArr = array();

    if (($fun == "user") && !$_SESSION['userId']) {
        $answer = array('code' => -1, 'msg' => '尚未登陆！', 'data' => '');
        echo json_encode($answer);
        exit;
    }

    //获取要构造SQL的参数
    $userId = $fun == "user" ? $_SESSION['userId']: '';
    $type = (isset($_GET['type']) && in_array($_GET['type'], array('1', '2', '3'))) ? $_GET['type']: '';
    $key = isset($_GET['key']) ? '%'.$_GET['key'].'%': '';

    //添加条件
    if ($userId || $type || $key) {
        $sql .= " AND";
        if ($userId) {
            $sql .= " (`userId` = ?)";
            array_push($sqlArr, $userId);
        }
        if ($userId && $type) {
            $sql .= " AND";
        }
        if ($type) {
            $sql .= " (`type` = ?)";
            array_push($sqlArr, $type);
        }
        if ($userId && $key || $type && $key) {
            $sql .= " AND";
        }
        if ($key) {
            $sql .= " (`title` LIKE ? OR `date` LIKE ? OR `linkmen` LIKE ? OR `phone` LIKE ? OR `qq` LIKE ? OR `details` LIKE ?)";
            $sqlArr = array_merge($sqlArr, array($key, $key, $key, $key, $key, $key));
        }
    }
    //按ID排序
    $sql .= " ORDER BY `upTime` DESC";

    //返回分页信息
    $list = array();
    $page = isset($_GET['page'])? $_GET['page']:1;
    $pagInfo = paging($localPdo, $sql, $sqlArr, $page);
    //分页的限制条件
    $localSt = $localPdo -> prepare($sql . " LIMIT " . $pagInfo['startCount'] . "," . $pagInfo['perNum']);
    //输出的页面信息
    $info = array(
        'page' => $pagInfo['page'],
        'totalPage' => $pagInfo['totalPage'],
        'totalNum' => $pagInfo['totalNum']
    );
    //数据库查询
    $localSt -> execute($sqlArr);
    $result = $localSt -> fetchAll();
    foreach ($result as $item) {
        $notice = isset($_SESSION['userId']) && ($_SESSION['userId'] == $item['userId']) ? $item['notice']: 0;
        $picture = ($item['picture'] ? $item['picture']: "http://7sbqrs.com1.z0.glb.clouddn.com/nopic.jpg") . '?imageView2/1/w/200/h/200';
        $list[]=array(
            'id' => $item['id'],
            'type' => $item['type'],
            'title' => $item['title'],
            'date' => $item['date'],
            'where' => $item['where'],
            'linkmen' => $item['linkmen'],
            'studentId' => $item['studentId'],
            'qq' => $item['qq'],
            'phone' => $item['phone'],
            'picture' => $picture,
            'notice' => $notice,
            'upTime' => $item['upTime']
        );
    }
    $answer = array('code' => 0, 'msg' => '获取列表成功', 'data' => $list, 'info' => $info);
    echo json_encode($answer);
}
?>