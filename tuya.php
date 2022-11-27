<?php
// These variables need to be filled in
// Get them form https://eu.iot.tuya.com/cloud   (after registering, etc.)

// You can find these at:   Cloud -> Your project -> Overview
$ACCESS_ID    = "";
$ACCESS_SECRET= "";

// You can find this at:    Cloud -> Your project -> Devices -> Link Tuya App Account -> UID
$USER_ID      = "";
// Same page (select box) upper right corner
$ENDPOINT     = "https://openapi.tuyaeu.com";
/*
ENDPOINT urls:
China Data Center	https://openapi.tuyacn.com
Western America Data Center	https://openapi.tuyaus.com
Eastern America Data Center	https://openapi-ueaz.tuyaus.com
Central Europe Data Center	https://openapi.tuyaeu.com
Western Europe Data Center	https://openapi-weaz.tuyaeu.com
India Data Center	https://openapi.tuyain.com
*/

// No more variables to fill. Develop code further if need be.

echo "<pre>";
$ACCESS_TOKEN  = "";

if (isset($_REQUEST['control'])){
	$fc=file_get_contents("token.txt");
	$ar=json_decode( $fc, true );
	$ACCESS_TOKEN = $ar["result"]["access_token"];
	$val=$_REQUEST['value'];
	if ($val==='true') $val=true;
	if ($val==='false') $val=false;
	if (is_numeric($val)) $val=intval($val);
	$ar=[["code"=>$_REQUEST['code'], 'value'=> $val ]];
	control($_REQUEST['id'], $ar );
	echo '<meta http-equiv="refresh" content="0;URL=\'?\'" />';
	die();
}

if (file_exists("token.txt")){
	// echo "Using old token.\n";
	$fc=file_get_contents("token.txt");
	$ar=json_decode( $fc, true );
	$ACCESS_TOKEN = $ar["result"]["access_token"];
}else {
	echo "Getting token.\n";
	get_token();
}

if (file_exists("devices.txt") && !isset($_REQUEST['refresh'])){
	// echo "Using old device-list.\n";
	echo "\n";
	$fc=file_get_contents("devices.txt");
	$ar=json_decode( $fc, true );
	foreach($ar["result"] as $d){
		echo "<img style='height:20px;' src='https://images.tuyaeu.com/".$d['icon']."' /> ";
		if ($d['online']==1) echo "<b>";
			echo $d['name'];
			echo str_repeat(".",30-mb_strlen($d['name']));
		if ($d['online']==1) echo "</b>";

		foreach($d['status'] as $s){
			if ($s['code']=='switch_led' || $s['code']=='switch_1' || $s['code']=='switch'){
				echo " [ <b><a href='?control=1&id=$d[id]&code=$s[code]&value=true'>ON</a></b> ] / [ <a href='?control=1&id=$d[id]&code=$s[code]&value=false'>OFF</a> ] ";
			}
		}
		echo "\n";
	}
	echo "\n<a href='?refresh=1'>Refresh list</a>\n\n";
	print_r( $ar );
}else {
	echo "Getting new device list.\n";
	get_users_device_list();
}

function control($device_id,$control){
	global $ACCESS_ID,$ACCESS_TOKEN,$USER_ID;
	
	// tuya Iot Platform
	// Smart Home Device System
	// \ Device Control
	//   \ Control Device
	
///v1.0/devices/{device_id}/commands
/*
{
  "commands": [
    {
      "code": "switch_led",
      "value": true
    },
    {
      "code": "bright",
      "value": 30
    }
  ]
}
*/
$body=json_encode([ "commands"=> $control ],JSON_PRETTY_PRINT);
 echo $body; 
	$url = "https://openapi.tuyaeu.com/v1.0/devices/$device_id/commands";
	$p=parse_url($url);
	$t=round(microtime(true)*1000);
	$fp = fsockopen('ssl://'.$p['host'],443,$er,$es,30);
	$req="POST $p[path] HTTP/1.1\r\n"
	."Host: $p[host]\r\n"
	."sign_method: HMAC-SHA256\r\n"
	."client_id: $ACCESS_ID\r\n"
	."t: $t\r\n"
	."mode: cors\r\n"
	."Content-Type: application/json\r\n"
	."sign: ".sign($ACCESS_ID,$ACCESS_TOKEN, $t, "POST", $body , "$p[path]")."\r\n"
	."access_token: $ACCESS_TOKEN\r\n"
	."Content-Length: ".strlen($body)."\r\n"
	."Connection: Close\r\n\r\n$body";
	fwrite($fp,$req);
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
	if ($ar['code']==1010) {
		get_token();
		control($device_id, $control);
	}
}

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
	$ar=json_decode($res);
	if ($ar['code']==1010) {
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
