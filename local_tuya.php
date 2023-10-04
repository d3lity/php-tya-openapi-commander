<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');

if (@$_REQUEST['tuya_get']){ // sse of ip's of devices

// These variables need to be filled in
// Get them form https://eu.iot.tuya.com/cloud   (after registering, etc.)
// -------------------------------------------------------------------------------------------------
// You can find these at:   Cloud -> Your project -> Overview
$ACCESS_ID    = "";
$ACCESS_SECRET= "";
// You can find this at:    Cloud -> Your project -> Devices -> Link Tuya App Account -> UID
$USER_ID      = "";
// Same page (select box) upper right corner
$ENDPOINT     = "https://openapi.tuyaeu.com";
// -------------------------------------------------------------------------------------------------

if (strlen($ACCESS_ID)==0 || strlen($ACCESS_SECRET)==0 || strlen($USER_ID)==0 ) die('Set access variables in code.');

function get_users_device_list(){
	global $ACCESS_ID,$ACCESS_TOKEN,$USER_ID;
	
	// tuya Iot Platform
	// Smart Home Device System
	// \ Device Management
	//   \ Get User's Device List
	
//curl --request GET "https://openapi.tuyaeu.com/v1.0/users/{uid}/devices" --header "sign_method: HMAC-SHA256" --header "client_id: xxx" --header "t: 1669235052095" --header "mode: cors" --header "Content-Type: application/json" --header "sign: xxx" --header "access_token: xxx"

	$url = "https://openapi.tuyaeu.com/v1.0/users/$USER_ID/devices";
	$p=parse_url($url);
	$body="";
	$t=round(microtime(true)*1000);
	$fp = fsockopen('ssl://'.$p['host'],443,$er,$es,30);
	$req="GET $p[path] HTTP/1.1\r\n"
	."Host: $p[host]\r\n"
	."sign_method: HMAC-SHA256\r\n"
	."client_id: $ACCESS_ID\r\n"
	."t: $t\r\n"
	."mode: cors\r\n"
	."Content-Type: application/json\r\n"
	."sign: ".sign($ACCESS_ID,$ACCESS_TOKEN, $t, "GET", "", "$p[path]")."\r\n"
	."access_token: $ACCESS_TOKEN\r\n"
	."Content-Length: ".strlen($body)."\r\n"
	."Connection: Close\r\n\r\n$body";
	fwrite($fp,$req);
	$contents='';
	while (!feof($fp)) {
    $contents .= fread($fp, 8192);
	}
	fclose($fp);
	
	$r=$contents;
	if (preg_match("/Transfer-Encoding: chunked/",$r)) {
		$str=preg_replace("/^[^§]+?\r\n\r\n/",'',$r);
		for ($res = ''; !empty($str); $str = trim($str)) {
		 $pos = strpos($str, "\r\n");
		 $len = hexdec(substr($str, 0, $pos));
		 $res.= substr($str, $pos + 2, $len);
		 $str = substr($str, $pos + 2 + $len);
		}
	}else{
		$res=preg_replace("/^[^§]+?\r\n\r\n/",'',$r);
	}
	$ar=json_decode($res,true);
	if (isset( $ar['code'] ) && $ar['code']==1010) {
		get_token();
		get_users_device_list();
		return;
	}
	echo "<pre>".$res;
	file_put_contents("devices.txt",$res);	
}

function get_token(){
	global $ACCESS_ID,$ACCESS_TOKEN;
//curl --request GET "https://openapi.tuyaeu.com/v1.0/token?grant_type=1" --header "sign_method: HMAC-SHA256" --header "client_id: xxx" --header "t: 1669235980076" --header "mode: cors" --header "Content-Type: application/json" --header "sign: xxx" --header "access_token: "	
	$ACCESS_TOKEN="";
	$url = "https://openapi.tuyaeu.com/v1.0/token?grant_type=1";
	$p=parse_url($url);
	$body="";
	$t=round(microtime(true)*1000);
	$fp = fsockopen('ssl://'.$p['host'],443,$er,$es,30);
	$req="GET $p[path]?$p[query] HTTP/1.1\r\n"
	."Host: $p[host]\r\n"
	."sign_method: HMAC-SHA256\r\n"
	."client_id: $ACCESS_ID\r\n"
	."t: $t\r\n"
	."mode: cors\r\n"
	."Content-Type: application/json\r\n"
	."sign: ".sign($ACCESS_ID,$ACCESS_TOKEN, $t, "GET", "", "$p[path]?$p[query]")."\r\n"
	."Content-Length: ".strlen($body)."\r\n"
	."Connection: Close\r\n\r\n$body";
	fwrite($fp,$req);
	$contents='';
	while (!feof($fp)) {
    $contents .= fread($fp, 8192);
	}
	fclose($fp);
	
	$r=$contents;
	if (preg_match("/Transfer-Encoding: chunked/",$r)) {
		$str=preg_replace("/^[^§]+?\r\n\r\n/",'',$r);
		for ($res = ''; !empty($str); $str = trim($str)) {
		 $pos = strpos($str, "\r\n");
		 $len = hexdec(substr($str, 0, $pos));
		 $res.= substr($str, $pos + 2, $len);
		 $str = substr($str, $pos + 2 + $len);
		}
	}else{
		$res=preg_replace("/^[^§]+?\r\n\r\n/",'',$r);
	}
	
	echo "<pre>".$res;
	file_put_contents("token.txt",$res);
	$ar=json_decode( $res, true );
	$ACCESS_TOKEN = $ar["result"]["access_token"];
}


// Hardest part
function sign($ACCESS_ID,$access_token, $t, $METHOD, $body, $url_end){
global $ACCESS_SECRET;
$content_SHA256=hash("sha256",$body);
$c=<<<END
${ACCESS_ID}${access_token}${t}${METHOD}
${content_SHA256}

${url_end}
END;
return strtoupper(hash_hmac('sha256',$c,$ACCESS_SECRET));
}

	get_token();
	get_users_device_list();

	die('tuya info');
}

if (@$_REQUEST['listen']){ // sse of ip's of devices

	$devices=json_decode( file_get_contents( 'devices.txt' ), true );
	if (!is_array($devices)) $devices=array();
	$ips=json_decode( file_get_contents( 'ips.txt' ), true );
	if (!is_array($ips)) $ips=array();

	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
	header('X-Accel-Buffering: no'); //nginx cache
	
	function echoo($msg) {
		$msg=json_encode( $msg, JSON_UNESCAPED_UNICODE);
		echo "data: $msg".PHP_EOL.PHP_EOL;
		ob_flush();flush(); // flushing important
	}
	function keepalive(){
		echo ": comment".PHP_EOL.PHP_EOL;
		ob_flush();flush(); // flushing important
	}
	echo "retry: 10000". PHP_EOL. "data: sse-client start". PHP_EOL. PHP_EOL;
	

	//Create a UDP socket
	if(!($sock = socket_create(AF_INET, SOCK_DGRAM, 0)))
	{
	 $errorcode = socket_last_error();
	 $errormsg = socket_strerror($errorcode);
	
	 die("Couldn't create socket: [$errorcode] $errormsg \n");
	}
	echoo( "Socket created \n");
	
	// Bind the source address
	if( !socket_bind($sock, "0.0.0.0" , 6667) )
	{
	 $errorcode = socket_last_error();
	 $errormsg = socket_strerror($errorcode);
	
	 die("Could not bind socket : [$errorcode] $errormsg \n");
	}
	
	echoo( "Socket bind OK \n");
	
	socket_set_nonblock($sock);
	
	if (!socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
	    echoo( 'Unable to set option on socket: '. socket_strerror(socket_last_error()) );
	}
	$start=time();$ips_updated=0;
	while ((time()-$start)<10) {
	    
		$r = socket_recvfrom($sock, $buf, 1024, MSG_DONTWAIT, $remote_ip, $remote_port);
	   if (strlen($buf)>0){
	   	echoo( "$remote_ip : $remote_port -- " . bin2hex($buf)."\n");
	   	$json=openssl_decrypt(substr($buf,20,-8), 'AES-128-ECB',  hex2bin(md5('yGAdlopoPVldABfn')), OPENSSL_RAW_DATA);
	   	// file_put_contents("dev/udp_json.txt", $json );
	   	echoo( $json);
	   	$ar=json_decode( $json, true);
	   	if (isset($devices['result'])) $devices=$devices['result'];
	   	foreach( $devices as $k=>$d){
	   		if ($d['id']==$ar['gwId']) {
	   			$ips[ $d['id'] ]=[
	   				'ip'=>$ar['ip'],
	   				'name'=>$d['name'],
	   				'local_key'=>$d['local_key']
	   				];
	   			$ips_updated++;
	   		}
	   	}
	   	// if (trim($buf)!=='') break;
	   }
	
		usleep(123*1000);
	}
	
	socket_close($sock);
	if ($ips_updated>0) {
		file_put_contents( 'ips.txt', json_encode( $ips) );
		echoo( $ips );
	}
	echoo( "done.");
	die();




	
}

if (@$_REQUEST['control']){
	define("ENCRYPTION_METHOD", "AES-128-ECB");
	$id=$_REQUEST['control'];
	
	$ips=json_decode( file_get_contents( 'ips.txt' ), true );
	if (!is_array($ips)) $ips=array();
	if (!isset($ips[$id])) die('Device not found on local network '.$id);
	$ip=$ips[$id]['ip'];
	$local_key=$ips[$id]['local_key'];
	
	$mes = [
	  "devId" => $id,
	  "uid" => $id,
	  "t" => "".time(),
	  "dps"=>[
	  		// "20"=>false,
	  		// "20"=>true,
			// "21"=>"white",		
			// "21"=>"colour",
			// "24"=>"003c03e803e8",
			// "24"=>"012c03e803e8"
		]	
	];
	$act=$_REQUEST['act'];
	
	if ($act=='on'){
		$mes['dps']['20']=true;
	}
	if ($act=='off'){
		$mes['dps']['20']=false;
	}

	$message=json_encode( $mes );
	
	$encryptedMessage=openssl_encrypt($message, ENCRYPTION_METHOD, $local_key, OPENSSL_RAW_DATA);
	
	$fullMessage = 
		hex2bin('000055aa').	// prefix
		hex2bin('00000002').	// sequence number (does not matter)
		hex2bin('00000007').	// command 7
		pack('N',strlen($encryptedMessage)+23).	// message length
		'3.3'.					// protocol version
		hex2bin('000000000000000000000000').	// 12 bytes, all zero
		$encryptedMessage.	// message
		hex2bin('00000000').	// crc (does not matter)
		hex2bin('0000aa55');	// suffix
	
	$fp=fsockopen('tcp://'.$ip,6668,$er,$es,30);echo $es;
	if ($fp) {
		fwrite($fp,$fullMessage);
		$b=fread($fp,1024);
		 $sta=35; $end=-8;
		var_dump(  openssl_decrypt( substr($b,$sta,$end), ENCRYPTION_METHOD, $local_key, OPENSSL_RAW_DATA) );
		fclose($fp);
	}
	
	
	
	die('ok.');
	
	
	
	
	
}


?><!DOCTYPE html>
<meta charset="UTF-8">
<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width">
<meta name="mobile-web-app-capable" content="yes">
<button onclick='tuya_get_info("#info")'>Get information (id, local_key) of registered devices from Tuya</button>
<button onclick='listen_up("#info")'>Listen local network for Tuya devices UDP messages</button>
<?php
$ar=json_decode(file_get_contents('ips.txt'),true);
echo "<table>";
foreach($ar as $k=>$v){
	echo "<tr>";
	echo "<td>".$v['ip']."</td>";
	echo "<td>".$v['name']."</td>";
	echo "<td><button onclick='control(\"$k\",\"off\")'>Off</button><button onclick='control(\"$k\",\"on\")'>On</button></td>";	
	echo "</tr>";
}
echo "</table>";
?>
<div id='info'></div>
<script type="text/javascript">
function control( id, act ){
  fetch('?control='+id+"&act="+act,{method:"GET"})
  .then(function(response) {
    return response.text();
  })
  .then(function(h) {
    document.querySelector( '#info' ).innerHTML=h;
  });
}
function tuya_get_info( result_selector ){
  fetch('?tuya_get=1',{method:"GET"})
  .then(function(response) {
    return response.text();
  })
  .then(function(h) {
    document.querySelector( result_selector ).innerHTML=h;
  });
}

function listen_up( result_selector ){
	var source = new EventSource('?listen=1');
	source.onmessage=function(event){
		try{
			var o=JSON.parse(event.data);
			if (o=='done.') source.close();
			var res=document.querySelector( result_selector );
			res.innerHTML=o+"<br>"+res.innerHTML;			
		}catch(err){
			console.log(event.data);
		}
	}
	source.onerror=function(event){
		console.log("sse Error",event);
		source.close(); // else it restarts again
	}
}
</script>
