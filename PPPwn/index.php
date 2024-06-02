<?php 

if (isset($_POST['save'])){
	$config = "#!/bin/bash\n";
	$config .= "INTERFACE=\"".str_replace(" ", "", trim($_POST["interface"]))."\"\n";
	$config .= "FIRMWAREVERSION=\"".$_POST["firmware"]."\"\n";
	$config .= "SHUTDOWN=false\n";
	$config .= "USBETHERNET=".(isset($_POST["usbether"]) ? "true" : "false")."\n";
	$config .= "PPPOECONN=".(isset($_POST["pppoeconn"]) ? "true" : "false")."\n";
	$config .= "VMUSB=".(isset($_POST["vmusb"]) ? "true" : "false")."\n";
	$config .= "DTLINK=".(isset($_POST["dtlink"]) ? "true" : "false")."\n";
	$config .= "PPDBG=".(isset($_POST["ppdbg"]) ? "true" : "false")."\n";
	$config .= "TIMEOUT=\"".str_replace(" ", "", trim($_POST["timeout"]))."\"\n";
	exec('echo "'.$config.'" | sudo tee /boot/firmware/PPPwn/config.sh');
	exec('echo "'.trim($_POST["plist"]).'" | sudo tee /boot/firmware/PPPwn/ports.txt');
 	exec('sudo iptables -P INPUT ACCEPT');
 	exec('sudo iptables -P FORWARD ACCEPT');
 	exec('sudo iptables -P OUTPUT ACCEPT');
 	exec('sudo iptables -t nat -F');
 	exec('sudo iptables -t mangle -F');
 	exec('sudo iptables -F');
 	exec('sudo iptables -X');
	exec('sudo sysctl net.ipv4.ip_forward=1');
 	exec('sudo sysctl net.ipv4.conf.all.route_localnet=1');
 	exec('sudo iptables -t nat -I PREROUTING -s 192.168.2.0/24 -p udp -m udp --dport 53 -j DNAT --to-destination 127.0.0.1:5353');
	$plst = explode(",",trim($_POST["plist"]));
	for($i = 0; $i < count($plst); ++$i) {
	 	exec('sudo iptables -t nat -I PREROUTING -p tcp --dport '.str_replace("-", ":", $plst[$i]).' -j DNAT --to 192.168.2.2:'.str_replace(":", "-", $plst[$i]));
		exec('sudo iptables -t nat -I PREROUTING -p udp --dport '.str_replace("-", ":", $plst[$i]).' -j DNAT --to 192.168.2.2:'.str_replace(":", "-", $plst[$i]));
	}
 	exec('sudo iptables -t nat -A POSTROUTING -s 192.168.2.0/24 ! -d 192.168.2.0/24 -j MASQUERADE');

    if (isset($_POST["vmusb"]) == true)
	{
      exec('sudo bash /boot/firmware/PPPwn/remount.sh &');
	}
	else
	{
      exec('sudo rmmod g_mass_storage');
	}
	if (isset($_POST["pppoeconn"]) == true)
	{
      $cmd = 'sudo systemctl is-active pipwn';
      exec($cmd ." 2>&1", $sresp, $pret);
	  if (implode($sresp) != "active")
	  {
		$cmd = 'sudo systemctl is-active pppoe';
		exec($cmd ." 2>&1", $presp, $pret);
		if (implode($presp) != "active")
		{
			exec('sudo systemctl start pppoe'); 
		}
	  }
	}
	else
	{
      exec('sudo systemctl stop pppoe');
	}
	sleep(1);
}
 
if (isset($_POST['restart'])){
   exec('echo "\033[32mRestarting\033[0m"  | sudo tee /dev/tty1 && sudo systemctl restart pipwn');
}

if (isset($_POST['reboot'])){
   exec('sudo reboot');
}
 
if (isset($_POST['shutdown'])){
   exec('sudo poweroff');
}

if (isset($_POST['payloads'])){
   header("Location: payloads.php");
}

if (isset($_POST['remount'])){
   exec('sudo bash /boot/firmware/PPPwn/remount.sh &');
}

$cmd = 'sudo cat /boot/firmware/PPPwn/config.sh';
exec($cmd ." 2>&1", $data, $ret);
if ($ret == 0){
foreach ($data as $x) {
   if (str_starts_with($x, 'INTERFACE')) {
      $interface = (explode("=", str_replace("\"", "", $x))[1]);
   }
   elseif (str_starts_with($x, 'FIRMWAREVERSION')) {
      $firmware = (explode("=", str_replace("\"", "", $x))[1]);
   }
   elseif (str_starts_with($x, 'SHUTDOWN')) {
      $shutdown = (explode("=", $x)[1]);
   }
   elseif (str_starts_with($x, 'USBETHERNET')) {
      $usbether = (explode("=", $x)[1]);
   }
   elseif (str_starts_with($x, 'PPPOECONN')) {
      $pppoeconn = (explode("=", $x)[1]);
   }
   elseif (str_starts_with($x, 'VMUSB')) {
      $vmusb = (explode("=", $x)[1]);
   }
   elseif (str_starts_with($x, 'DTLINK')) {
      $dtlink = (explode("=", $x)[1]);
   }
   elseif (str_starts_with($x, 'PPDBG')) {
      $ppdbg = (explode("=", $x)[1]);
   }
   elseif (str_starts_with($x, 'TIMEOUT')) {
      $timeout = (explode("=", str_replace("\"", "", $x))[1]);
   }
}
}else{
   $interface = "eth0";
   $firmware = "11.00";
   $shutdown = "false";
   $usbether = "false";
   $pppoeconn = "false";
   $vmusb = "false";
   $dtlink = "false";
   $ppdbg = "false";
   $timeout = "5m";
}


if (empty($interface)){ $interface = "eth0";}
if (empty($firmware)){ $firmware = "11.00";}
if (empty($shutdown)){ $shutdown = "false";}
if (empty($usbether)){ $usbether = "false";}
if (empty($pppoeconn)){ $pppoeconn = "false";}
if (empty($vmusb)){ $vmusb = "false";}
if (empty($dtlink)){ $dtlink = "false";}
if (empty($ppdbg)){ $ppdbg = "false";}
if (empty($timeout)){ $timeout = "5m";}


$cmd = 'sudo cat /boot/firmware/PPPwn/ports.txt';
exec($cmd ." 2>&1", $pdata, $pret);
if ($pret == 0){
   $portlist = "";
   foreach ($pdata as $x) {
      $portlist .= trim($x);
   }
}else{
   $portlist = "2121,3232,9090,8080,12800,1337";	
}


$cmd = 'sudo ip link | cut -d " " -f-2   | cut -d ":" -f2-2 ';
exec($cmd ." 2>&1", $idata, $iret);


print("<html> 
<head>
<title>PI-Pwn</title>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
<style>

body {
	user-select: none;
    -webkit-user-select: none;
    background-color: #0E0E14;
    color: white;
    font-family: Arial;
}

select {
    background: #454545;
	color: #FFFFFF;
	padding: 3px 5px;
    border-radius: 3px;
	border: 1px solid #6495ED;
}


input[type=text] {
    background: #454545;
	color: #FFFFFF;
	padding: 5px 5px;
    border-radius: 3px;
	border: 1px solid #6495ED;
}

a:active,
a:focus {
    outline: 0;
    border: none;
}

button {
    border: 1px solid #6495ED;
    color: #FFFFFF;
    background: #454545;
    padding: 10px 20px;
    border-radius: 3px;
}

button:hover {
    background: #999999;
}

input:focus {
    outline:none;
}

label {
    padding: 5px 5px;
}

input[type=checkbox] {
    position: relative;
    cursor: pointer;
}

input[type=checkbox]:before {
    content: \"\";
    display: block;
    position: absolute;
    width: 17px;
    height: 17px;
    top: 0;
    left: 0;
    background-color:#e9e9e9;
}

input[type=checkbox]:checked:before {
    content: \"\";
    display: block;
    position: absolute;
    width: 17px;
    height: 17px;
    top: 0;
    left: 0;
    background-color:#1E80EF;
}

input[type=checkbox]:checked:after {
    content: \"\";
    display: block;
    width: 3px;
    height: 8px;
    border: solid white;
    border-width: 0 2px 2px 0;
    -webkit-transform: rotate(45deg);
    -ms-transform: rotate(45deg);
    transform: rotate(45deg);
    position: absolute;
    top: 2px;
    left: 6px;
}
</style>
<script>
function setEnd() {
	if (navigator.userAgent.includes('PlayStation 4')) {
		let name = document.getElementById(\"plist\");
		name.focus();
		name.selectionStart = name.value.length;
		name.selectionEnd = name.value.length;	
	}
}
</script>
</head>
<body>


<center>
<br><br>
<form method=\"post\"><button name=\"payloads\">Load Payloads</button> &nbsp; ");


$cmd = 'sudo tr -d \'\0\' </proc/device-tree/model';
exec($cmd ." 2>&1", $pidata, $ret);
if ($vmusb == "true" && str_starts_with(trim(implode($pidata)),  "Raspberry Pi 4") || str_starts_with(trim(implode($pidata)), "Raspberry Pi 5"))
{
print("<button name=\"remount\">Remount USB</button> &nbsp; ");
}

print("<button name=\"restart\">Restart PPPwn</button> &nbsp; <button name=\"reboot\">Reboot PI</button> &nbsp; <button name=\"shutdown\">Shutdown PI</button>
</form>
</center>
<br>");

print("<br><table align=center><td><form method=\"post\">");

print("<select name=\"interface\">");
foreach ($idata as $x) {
$x = trim($x);
if ($x !== "" && $x !== "lo" && $x !== "ppp0" && !str_starts_with($x, "wlan"))
{
if ( $interface ==  $x)
{
print("<option value=\"".$x."\" selected>".$x."</option>");
}else{
print("<option value=\"".$x."\">".$x."</option>");
}
}
}
print("</select><label for=\"interface\">&nbsp; Interface</label><br><br>");


print("<select name=\"firmware\">");

if ($firmware == "11.00")
{
print("<option value=\"11.00\" selected>11.00</option>
<option value=\"9.00\">9.00</option>");
}else{
print("<option value=\"11.00\">11.00</option>
<option value=\"9.00\" selected>9.00</option>");
}

print("</select><label for=\"firmware\">&nbsp; Firmware version</label><br><br>");



print("<select name=\"timeout\">");
for($x =1; $x<=5;$x++)
{
   if ($timeout == $x."m")
   {
	   print("<option value=\"".$x."m\" selected>".$x."m</option>");
   }else{
	   print("<option value=\"".$x."m\">".$x."m</option>");
   }
} 
print("</select><label for=\"timeout\">&nbsp; Time to restart PPPwn if it hangs</label><br><br>");



$cval = "";
if ($usbether == "true")
{
$cval = "checked";
}
print("<br><input type=\"checkbox\" name=\"usbether\" value=\"".$usbether."\" ".$cval.">
<label for=\"usbether\">&nbsp;Use usb ethernet adapter</label>
<br>");



$cval = "";
if ($dtlink == "true")
{
$cval = "checked";
}
print("<br><input type=\"checkbox\" name=\"dtlink\" value=\"".$dtlink."\" ".$cval.">
<label for=\"dtlink\">&nbsp;Detect console shutdown and restart PPPwn</label>
<br>");


$cval = "";
if ($ppdbg == "true")
{
$cval = "checked";
}
print("<br><input type=\"checkbox\" name=\"ppdbg\" value=\"".$ppdbg."\" ".$cval.">
<label for=\"ppdbg\">&nbsp;Enable verbose PPPwn</label>
<br>");


$cval = "";
if ($pppoeconn == "true")
{
$cval = "checked";
}
print("<br><input type=\"checkbox\" name=\"pppoeconn\" value=\"".$pppoeconn."\" ".$cval.">
<label for=\"pppoeconn\">&nbsp;Enable console internet access</label>
<br>");



if (str_starts_with(trim(implode($pidata)),  "Raspberry Pi 4") || str_starts_with(trim(implode($pidata)), "Raspberry Pi 5"))
{
$cval = "";
if ($vmusb == "true")
{
$cval = "checked";
}
print("<br><input type=\"checkbox\" name=\"vmusb\" value=\"".$vmusb."\" ".$cval.">
<label for=\"vmusb\">&nbsp;Enable usb drive to console</label>");
}


print("<br>
<br>
<label for=\"plist\">Ports: </label>
<input type=\"text\" name=\"plist\" id=\"plist\" value=\"".$portlist."\" onclick=\"setEnd()\"><br>
<div  style =\"text-align:left; font-size:12px; padding:4px;\">
<label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Accepts ranges: 1000-1100</label>
</div>");

print("</td></tr><td align=center><br><button name=\"save\">Save</button></td></tr>
</form>
</td>
</table>
</body>
</html>");

?>