<?php

$id = $_GET['id'];

//ini_set("default_socket_timeout", 60);

$conn=mysqli_connect("192.168.199.199","admin","123456","sql_db","3306");
mysqli_query($conn,"set names 'utf8'");
$result = mysqli_query($conn,"SELECT a.ip,a.dbname,a.user,a.pwd,a.port,b.ops_content,b.binlog_information FROM dbinfo a JOIN sql_order_wait b ON a.dbname = b.ops_db WHERE b.id='".$id ."'");
while($row = mysqli_fetch_array($result))
	{
  		$ip=$row[0];
  		$db=$row[1];
  		$user=$row[2];
  		$pwd=$row[3];
  		$port=$row[4];
		$ops_content=$row[5];
		$binlog_information=preg_split("/[\s]+/", $row[6]);
	}
mysqli_close($conn);

echo "为防止手滑误更改数据，提供反向SQL回滚功能，结果供参考，如有问题请与DBA联系。"."</br></br>";
echo "你刚才上线时的SQL如下：</br>";
echo '<pre style="font-size:14px">' .$ops_content. '</pre>';
echo "<hr style=FILTER: progid:DXImageTransform.Microsoft.Glow(color=#987cb9,strength=10) width=100% color=#987cb9 SIZE=1>";
echo "</br>";
echo "生成反向SQL如下：</br>";

//print_r($binlog_information);

$rollback_sql="cd /usr/local/binlog2sql/binlog2sql;/usr/bin/python binlog2sql.py --flashback -h${ip} -u${user} -p'${pwd}' -P${port} --start-file='$binlog_information[0]' --stop-file='$binlog_information[2]' --start-position='$binlog_information[1]' --stop-position='$binlog_information[3]'";

//echo $rollback_sql;


##########执行回滚###################
	$remote_user="root";
	$remote_password="gta@2015";
	$script=$rollback_sql;
	$connection = ssh2_connect('192.168.199.199',22);
	ssh2_auth_password($connection,$remote_user,$remote_password);
	$stream = ssh2_exec($connection,$script,NULL,$env=array(),10,10);
	$correctStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
	$errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
	//stream_set_blocking($errorStream, true);
	stream_set_blocking($correctStream, true);
	$message=stream_get_contents($errorStream);
	$measage_stdio=stream_get_contents($correctStream);

		//echo $message."<br>";
		echo '<pre>' .nl2br($measage_stdio). '</pre>'."<br>";
		//echo  nl2br(nl2br($measage_stdio)) . "<br>";
    		echo "<br>";
		echo "<a href='my_order.php'>点击返回工单界面</a></br></br>";
	fclose($stream);
	fclose($errorStream);
	#######################################
?>

