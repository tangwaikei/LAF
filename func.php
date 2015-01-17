<?php
/*
× 公用函数模块
*/

//基于CURL
function requests($array)
{
    $result=array('headers'=>'','content'=>'','cookies'=>'');
    //初始化
    $url=isset($array['url'])&&$array['url']? $array['url']:'';
    $proxy=isset($array['proxy'])&&$array['proxy']? $array['proxy']:'';
    $data=isset($array['data'])&&$array['data']? $array['data']:'';
    $method=isset($array['method'])&&strcasecmp($array['method'],'POST')==0? 'POST':'';
    $timeout=isset($array['timeout'])&&$array['timeout']? $array['timeout']:0;
    $cookies=isset($array['cookies'])&&$array['cookies']? $array['cookies']:'';
    $referer=isset($array['referer'])&&$array['referer']? $array['referer']:'';
    $useragent=isset($array['useragent'])&&$array['useragent']? $array['useragent']:'';
    if(!$url)
        return $result;
    //CURL
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
    curl_setopt($ch,CURLOPT_HEADER,true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    if(!empty($proxy))
        curl_setopt($ch,CURLOPT_PROXY,$proxy);
    if(!empty($cookies))
        curl_setopt($ch,CURLOPT_COOKIE,$cookies);
    if($method=='POST')
        curl_setopt($ch,CURLOPT_POST,true);
    if(!empty($data))
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
    if(!empty($referer))
        curl_setopt($ch,CURLOPT_REFERER,$referer);
    if(!empty($useragent))
        curl_setopt($ch,CURLOPT_USERAGENT,$useragent);
    $response=curl_exec($ch);
    $headerSize=curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $result['headers']=substr($response,0,$headerSize);
    $result['content']=substr($response, $headerSize);
    preg_match_all("/set\-cookie:([^;]*)/i",$result['headers'],$temp);
    for($i=0;$i<count($temp[1]);$i++)
        $result['cookies'].=$temp[1][$i].";";
    curl_close($ch);
    return $result;
}

//分页
function paging($pdo,$sql,$array=array(),$page=1)
{
    $st=$pdo->prepare(str_ireplace("*","count(*)",$sql));
    $st->execute($array);
    $result=$st->fetchAll();
    $totalNum=$result[0][0];
    $perNum=15;
    $totalPage=ceil($totalNum/$perNum);
    if($page<1) $page=1;
    else if($page>$totalPage) $page=$totalPage;
    $startCount=($page-1)*$perNum>=0? ($page-1)*$perNum:0;
    return array("totalNum"=>$totalNum,"perNum"=>$perNum,"totalPage"=>$totalPage,"page"=>$page,"startCount"=>$startCount);
}

//获取用户IP
function get_ip()
{
    if(getenv("HTTP_CLIENT_IP")&&strcasecmp(getenv("HTTP_CLIENT_IP"),"unknown"))
    $ip=getenv("HTTP_CLIENT_IP");
    else if(getenv("HTTP_X_FORWARDED_FOR")&&strcasecmp(getenv("HTTP_X_FORWARDED_FOR"),"unknown"))
        $ip=getenv("HTTP_X_FORWARDED_FOR"); 
    else if(getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"),"unknown")) 
        $ip=getenv("REMOTE_ADDR"); 
    else if(isset($_SERVER['REMOTE_ADDR'])&&$_SERVER['REMOTE_ADDR']&&strcasecmp($_SERVER['REMOTE_ADDR'],"unknown"))
        $ip=$_SERVER['REMOTE_ADDR']; 
    else $ip="unknown";
    preg_match("((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d))))",$ip,$temp);
    return strlen($temp[0])==0? "unknown":$temp[0];
}

//UTF8字符串长度
function utf8_strlen($string=null)
{
    preg_match_all("/./us", $string, $match);
    return count($match[0]);
}

//安全的base64加密
function urlsafe_base64_encode($str)
{
    return str_replace(array('+','/'),array('-','_'),base64_encode($str));
}

//HMAC_SHA1
function hmac_sha1($data,$key,$blockSize=64,$opad=0x5c,$ipad=0x36)
{
    if(strlen($key)>$blockSize)
    {
        $key=sha1($key,true);
    }
    $key=str_pad($key,$blockSize,chr(0x00),STR_PAD_RIGHT);    
    $o_key_pad='';
    $i_key_pad='';
    for($i=0;$i<$blockSize;$i++) {
        $o_key_pad.=chr(ord(substr($key,$i,1)) ^ $opad);
        $i_key_pad.=chr(ord(substr($key,$i,1)) ^ $ipad);
    }
    return sha1($o_key_pad.sha1($i_key_pad.$data,true),true);
}

//文件上传到七牛
function file_update($fileName)
{
    //AK&SK
    $AccessKey="";
    $SecretKey="";
    //生成uploadToken
    $returnBody='{"key": $(key)}';
    $putPolicy=json_encode(array('scope'=>'hnust-laf','deadline'=>time()+600,'returnBody'=>$returnBody));
    $encodedPutPolicy=urlsafe_base64_encode($putPolicy);
    $sign=hmac_sha1($encodedPutPolicy,$SecretKey);
    $encodedSign=urlsafe_base64_encode($sign);
    $uploadToken=$AccessKey.':'.$encodedSign.':'.$encodedPutPolicy;
    $data=array("token"=>$uploadToken,"file"=>"@".$fileName);
    $request=array('url'=>"http://upload.qiniu.com/",'data'=>$data);
    $result=requests($request);
    $result=json_decode($result['content'],1);
    $fileUrl="http://7sbqrs.com1.z0.glb.clouddn.com/".$result['key'];
    return $fileUrl;
}
?>