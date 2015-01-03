<?php
/*
* 功能实现模块
*/
require "config.php";
//增加条目
if($fun=="add")
{
    //图片上传
    if(isset($_FILES['picture'])&&!$_FILES['picture']['error'])
        $fileUrl=file_update($_FILES['picture']['tmp_name']);
    else $fileUrl="";
    //类型
    if(!in_array($_POST['type'],array(1,2,3))||$_POST['type']==1&&$_SESSION['rank']!=9)
    {
        $answer=array('code'=>-2,'msg'=>'发布类型选择异常！','data'=>'');
        if($api)
            echo json_encode($answer);
        else echo '<script>alert("发布失败，可能是数据有误！");window.history.back();</script>';
        exit;
    }
    //日期
    if(!isset($_POST['data'])||!$_POST['data'])
        $_POST['data']=date("Y-m-d");

    //api提交判断重复
    if($api)
    {
        $localSt=$localPdo->prepare("select id from laf where title=? and details=? and details!=''");
        $localSt->execute(array($_POST['title'],$_POST['details']));
        $result=$localSt->fetch();
        $addId=$result? $result[0]:'';
    }
    //添加到数据库
    if(!$api||!$addId)
    {
        $localSt=$localPdo->prepare("insert into laf values(null,?,?,?,?,?,?,?,?,?,?,?,'[]',0,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,0)");
        $localSt->execute(array($_SESSION['userId'],$_POST['type'],$_POST['title'],$_POST['date'],$_POST['where'],$_POST['linkmen'],$_POST['studentId'],$_POST['phone'],$_POST['qq'],$_POST['details'],$fileUrl,get_ip()));
        $addId=$localPdo->lastInsertId();
    }
    $answer=array('code'=>0,'msg'=>'发布成功！','data'=>'','info'=>array('addId'=>$addId));
    if($api)
        echo json_encode($answer);
    else header('Location: ./#/details?id='.$addId);
}
//查看详情
else if($fun=='details'&&isset($_GET['id']))
{
    $localSt=$localPdo->prepare("select * from laf where id=? and deleted=0");
    $localSt->execute(array($_GET['id']));
    $result=$localSt->fetch();
    if($result)
    {
        //清除未读评论
        if($_SESSION['userId']==$result['userId'])
        {
            $localSt=$localPdo->prepare("update laf set notice=0 where id=?");
            $localSt->execute(array($_GET['id']));
        }
        //去除不该输出的东西
        $count=count($result)/2;
        for($i=0;$i<$count;$i++)
            unset($result[$i]);
        unset($result['ip']);
        unset($result['time']);
        unset($result['userId']);
        unset($result['deleted']);
        $result['comments']=json_decode($result['comments'],1);
        //去除评论中的敏感信息
        for($i=0;$i<count($result['comments']);$i++)
        {
            unset($result['comments'][$i]['userId']);
            unset($result['comments'][$i]['ip']);
        }
        $answer=array('code'=>0,'msg'=>'获取成功！','data'=>$result);
    }
    else $answer=array('code'=>-11,'msg'=>'资源不存在！','data'=>'');
    echo json_encode($answer);
}
//用户评论
else if($fun=='comment'&&isset($_GET['id'])&&isset($_GET['comment']))
{
    $localSt=$localPdo->prepare("select * from laf where id=? and deleted=0");
    $localSt->execute(array($_GET['id']));
    $result=$localSt->fetch();
    if($result)
    {
        //获取所有评论
        @$comments=json_decode($result['comments'],1);
        //当前评论
        $comment=array('nick'=>$_SESSION['nick'],
                       'userId'=>$_SESSION['userId'],
                       'content'=>$_GET['comment'],
                       'time'=>date('y-m-d H:i:s',time()),
                       'ip'=>get_ip());
        //添加评论
        if($comments)
        {
            $comment['id']=$comments[0]['id']+1;
            array_unshift($comments,$comment);
        }
        else
        {
            $comment['id']=1;
            $comments=array($comment);
        }

        //不是本人评论则未读评论+1
        if(isset($_SESSION['userId'])&&$_SESSION['userId']==$result['userId'])
            $notice=$result['notice'];
        else $notice=$result['notice']+1;
        //加入数据库
        $localSt=$localPdo->prepare("update laf set comments=?,notice=? where id=?");
        $localSt->execute(array(json_encode($comments),$notice,$_GET['id']));
        //输出评论
        unset($comment['ip']);
        unset($comment['userId']);
        $answer=array('code'=>0,'msg'=>'评论成功！','data'=>$comment);
    }
    else $answer=array('code'=>-11,'msg'=>'资源不存在！','data'=>'');
    echo json_encode($answer);
}
else if($fun=="list"||$fun=='user')
{
    //构造SQL语句
    $sql="select * from laf where (deleted=0)";
    $sqlArray=array();
    if($fun=="user"&&!$_SESSION['userId'])
    {
        $answer=array('code'=>-1,'msg'=>'尚未登陆！','data'=>'');
        echo json_encode($answer);
        exit;
    }
    //获取要构造SQL的参数
    $userId=$fun=="user"? $_SESSION['userId']:'';
    $type=isset($_GET['type'])&&in_array($_GET['type'],array('1','2','3'))? $_GET['type']:'';
    $key=isset($_GET['key'])? '%'.$_GET['key'].'%':'';
    //添加条件
    if($userId||$type||$key)
    {
        $sql.=" and";
        if($userId)
        {
            $sql.=" (userId=?)";
            array_push($sqlArray,$userId);
        }
        if($userId&&$type)
        {
            $sql.=" and";
        }
        if($type)
        {
            $sql.=" (type=?)";
            array_push($sqlArray,$type);
        }
        if($userId&&$key||$type&&$key)
        {
            $sql.=" and";
        }
        if($key)
        {
            $sql.=" (title like ? or date like ? or linkmen like ? or phone like ? or qq like ? or details like ?)";
            $sqlArray=array_merge($sqlArray,array($key,$key,$key,$key,$key,$key));
        }
    }
    //按ID排序
    $sql.=" order by upTime desc";

    //返回分页信息
    $list=array();
    $pagInfo=paging($localPdo,$sql,$sqlArray,isset($_GET['page'])? $_GET['page']:1);
    $localSt=$localPdo->prepare($sql." limit ".$pagInfo['startCount'].",".$pagInfo['perNum']);
    $info=array('page'=>$pagInfo['page'],'totalPage'=>$pagInfo['totalPage'],'totalNum'=>$pagInfo['totalNum']);
    $localSt->execute($sqlArray);
    $result=$localSt->fetchAll();
    foreach($result as $item)
    {
        $notice=isset($_SESSION['userId'])&&$_SESSION['userId']==$item['userId']? $item['notice']:0;
        $picture=($item['picture']? $item['picture']:"http://7sbqrs.com1.z0.glb.clouddn.com/nopic.jpg").'?imageView2/1/w/200/h/200';
        $list[]=array('id'=>$item['id'],
                      'type'=>$item['type'],
                      'title'=>$item['title'],
                      'date'=>$item['date'],
                      'where'=>$item['where'],
                      'linkmen'=>$item['linkmen'],
                      'studentId'=>$item['studentId'],
                      'qq'=>$item['qq'],
                      'phone'=>$item['phone'],
                      'picture'=>$picture,
                      'notice'=>$notice,
                      'upTime'=>$item['upTime']);
    }
    $answer=array('code'=>0,'msg'=>'获取列表成功','data'=>$list,'info'=>$info);
    echo json_encode($answer);
}
?>