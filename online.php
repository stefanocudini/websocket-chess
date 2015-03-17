<?
$printOnline = isset($printOnline) ? $printOnline : true;
//definisce la var $Online e se $printOnline stampa in output

$Expire = 10;
$AutoMonitor = 1;
$MaxFileSize = 10;  //in KB
$Online = 0;
$AutoCorrect = "";
$file = "online.txt";


if(!is_file($file))
{
 $fm = @fopen($file,"w");
 @fclose($fm);
 @chmod($file,0775);
}

$Interval = mktime() - $Expire;
$MaxFileSize = $MaxFileSize * 1024;

if($printOnline)  //non esiste $_SERVER in modalita' websocket
$NewUser = mktime()."|".$_SERVER['REMOTE_ADDR']."[x]";


#= OVERWRITE ALL OLD USER INFO
if (@filesize($file) > $MaxFileSize and $AutoMonitor == 1)
{
 $LoggedUsers = ReadLog($file);
 for ($x=0;$x<count($LoggedUsers);$x++)
 {
  if ($Interval <= trim(substr($LoggedUsers[$x],0,10)))
   $SavedUsers .= $LoggedUsers[$x]."\r\n";
 }
 if($printOnline)  //non esiste $_SERVER in modalita' websocket
 $SavedUsers .= $NewUser;

 $handle = @fopen($file,"w");
 @flock($handle,LOCK_EX);
 @fwrite($handle,$SavedUsers."\r\n");
 @flock($handle,LOCK_UN);
 @fclose($handle);
}


#= APPEND USER INFO TO LOG FILE
else
{
 $handle = @fopen($file,"a");
 @flock($handle,LOCK_EX);
 @fwrite($handle,$NewUser."\r\n");
 @flock($handle,LOCK_UN);
 @fclose($handle);
}

$LoggedUsers = ReadLog($file);


#= COUNTS CURRENT ONLINE USERS
for ($x=0;$x<count($LoggedUsers);$x++)
{
 $UserInfo = explode("|",$LoggedUsers[$x]);
 if (isset($CheckUsers)) // CHECKS FILE DATA FOR REPEAT USERS
 {
  if ($Interval <= trim($UserInfo[0]) and !stristr($CheckUsers,trim($UserInfo[1])))
  {
   $CheckUsers .= $UserInfo[1];
   $Online++;
  }
 }
 else
 {
  if($printOnline)  //non esiste $_SERVER in modalita' websocket
  	$CheckUsers .= $_SERVER['REMOTE_ADDR'];
  $Online++;
 }
}

////OUTPUT

if($printOnline) echo $Online;



#= READ LOG FILE FUNCTION
function ReadLog($file)
{
 $handle = @fopen($file,"r");
 @flock($handle,LOCK_SH);
 $LoggedUsers = @fread($handle,filesize($file));
 @flock($handle,LOCK_UN);
 @fclose($handle);
 $LoggedUsers = trim($LoggedUsers);
 $LoggedUsers = substr($LoggedUsers,0,-3);
 $LoggedUsers = explode("[x]",$LoggedUsers);
 return $LoggedUsers;
}
?>
