<?php
//////////////////////
//
// README:
// external variables: (assuming if request is 'http://example.com/files/test.txt' then)
//	MainDir						-> __DIR__.'/'
//	$request					-> $_SERVER["REQUEST_URI"] except first slash (files/test.txt)
//	UNIX_SERVER					-> [false->Windows,true->Linux]
//  MAINDIR						-> USUALY SET IT TO _DIR_
//  CHUNKSIZE					-> file chunk size
//  DEBUG						-> [true->show sql error,false->hide ]
// external functions:
//  FError206(),
//  FError416()
//
//  Error500(string $description),
//  Error404(string $description),
//  Error410(string $description)
//
define("DEBUG", false);
define("UNIX_SERVER", false);
if (UNIX_SERVER==true) {
  define("MAINDIR", __DIR__.'/');
} else {
  define("MAINDIR", __DIR__.'\\');
}
define("DBADDR", "127.0.0.1");//database addres
define("DBUN", "root");//db username
define("DBPW", "");//password
define("DBNAME", "main");//database name
define("CHUNKSIZE", 10*1024);//speed in byte / 10*1024 -> 1MBps  (with 0.01 second sleep!)

$request=$_SERVER["REQUEST_URI"];
$request=substr($request,1);
//////////////////////


//DASABLE COMPRESSION
if(ini_get("zlib.output_compression")) ini_set("zlib.output_compression", "off");
/////////////////
// IMPORTANT: SET YOUR DATABASE CONNECTION PARAMETERS HERE
/////////////////
$conn=mysqli_connect(DBADDR,DBUN,DBPW,DBNAME);
	if($conn==''){Error500("<h1>SQL Error :(</h1>");exit();}
	if (mysqli_connect_errno()){ if(DEBUG==true) Error500( "Failed to connect to MySQL: " . mysqli_connect_error()); else Error500();	exit();}
/////////////////
// IMPORTANT: SET TABLE COLLUMNS NAME HERE
// 	fileaddr: ENCODDED URL ADDRESS (PRIMARY KEY) (EG 'files%2Ffavicon.ico')
// 	filename: SET 'filename' HEADER VALUE (EG 'test.txt')
// 	filetype: JUST SET FILE POST FIX (EG 'txt', 'bmp', 'pdf', ...)
// 	realaddr: REAL ADDRESS OF FILE TO BE OPENED BY 'fopen' FUNCTION (EG 'files/test.txt') (USUALY SHOULD BE EQUAL TO FILE URL ADDR)
/////////////////
$dbansw=mysqli_query($conn,"SELECT filename,filetype,realaddr from files where \"".urlencode($request)."\" = fileaddr");if (mysqli_connect_errno()){if(DEBUG==true) Error500( "Failed to connect to MySQL: " . mysqli_connect_error()); else Error500();	exit();}
	if($dbansw==''){Error404();exit();}
	if(mysqli_num_rows($dbansw)==0){Error404("file name does not found: "$request);exit();}
	if(mysqli_num_rows($dbansw)!=1){Error500();exit();}

$dbfile=mysqli_fetch_assoc($dbansw);if ($dbfile=='') {Error404();exit();}
mysqli_free_result($dbansw);mysqli_close($conn);
if (UNIX_SERVER==true) {$dbfile["realaddr"]=str_replace("\\", "/", $dbfile["realaddr"]);}
else {$dbfile["realaddr"]=str_replace("/", "\\", $dbfile["realaddr"]);}

//CHECK FILE EXISTENS
if(!file_exists(MAINDIR.$dbfile["realaddr"])){Error410("<h1>YOUR FILE  IS GONE FOREVER!</h1>");exit();}

//SET FILE LENGHT AND CALCULATE RANGE
$flength=array(0,0);$ffsize=filesize(MAINDIR.$dbfile["realaddr"]);
if(isset($_SERVER["HTTP_RANGE"])){
	$dblist=explode('=',$_SERVER["HTTP_RANGE"],2);
	if(strpos($dblist[1],',')!=0){Erro416();exit();}
	$frange=array(0,0);
	if(strpos($dblist[1],'-')!=0) $frange=explode('-', $dblist[1],2);
	else $frange=array('0',$dblist[1]);
	if($frange[0]==''){$frange[0]=intval($ffsize) - intval($frange[1]);$frange[1]=$ffsize-1;}
	else if($frange[1]==''){$frange[1]=$ffsize-1;}
	FError206();
	$flength[0]=intval($frange[0]);$flength[1]=intval($frange[1]);
	if($flength[0]<0 || $flength[1]<$flength[0] || $ffsize<=$flength[1] ){FError416();exit();}
}else{$flength[0]=0;$flength[1]=$ffsize-1;}
$fdata_lenght=$flength[1]-$flength[0]+1;

//SET FILE MIME TYPE HEADER
$mime_type="application/octet-stream";
if($dbfile["filetype"]!=''){
	$known_mime_types=array("doc" => "application/msword","obj" => "application/x-tgif","hlp" => "application/winhlp","pdb" => "application/vnd.palm","wps" => "application/vnd.ms-works","eot" => "application/vnd.ms-fontobject","cab" => "application/vnd.ms-cab-compressed","mpkg" => "application/vnd.apple.installer+xml","apk" => "application/vnd.android.package-archive","pdf" => "application/pdf","bin" => "application/octet-stream","dmg" => "application/octet-stream","so" => "application/octet-stream","pkg" => "application/octet-stream","iso" => "application/octet-stream","dump" => "application/octet-stream","dot" => "application/msword","doc" => "application/msword","gz" => "application/gzip","pdf" => "application/pdf","rar" => "application/vnd.rar","rtf" => "application/rtf","7z" => "application/x-7z-compressed","ppt" => "application/vnd.ms-powerpoint","pps" => "application/vnd.ms-powerpoint","pot" => "application/vnd.ms-powerpoint","ogg" => "application/ogg","json" => "application/json","exe" => "application/octet-stream","zip" => "application/zip","xls" => "application/vnd.ms-excel","ico" => "image/vnd.microsoft.icon","bmp" => "image/bmp","jpeg" => "image/jpeg","png" => "image/png","jpg" => "image/jpg","gif" => "image/gif","svg" => "image/svg+xml","ttf" => "font/ttf","woff" => "font/woff","woff2" => "font/woff2","mpeg" => "video/mpeg","wmv" => "video/x-ms-wmv","mkv" => "video/x-matroska","mk3d" => "video/x-matroska","mks" => "video/x-matroska","m4v" => "video/x-m4v","webm" => "video/webm","m4u" => "video/vnd.mpegurl","ogv" => "video/ogg","mpeg" => "video/mpeg","mpg" => "video/mpeg","mpe" => "video/mpeg","m1v" => "video/mpeg","m2v" => "video/mpeg","jpgv" => "video/jpeg","html" => "text/html","htm" => "text/html","css" => "text/css","xml" => "text/xml","xsl" => "application/xml","xhtml" => "application/xhtml+xml","js" => "text/javascript","java" => "text/x-java-source","curl" => "text/vnd.curl","txt" => "text/plain","text" => "text/plain","conf" => "text/plain","def" => "text/plain","list" => "text/plain","log" => "text/plain","in" => "text/plain","php" => "text/plain","csv" => "text/csv","x3d" => "model/x3d+xml","wav" => "audio/wav","wav" => "audio/x-wav","wma" => "audio/x-ms-wma","flac" => "audio/x-flac","acc" => "audio/x-aac","weba" => "audio/webm","oga" => "audio/ogg","spx" => "audio/ogg","ogg" => "audio/ogg","mpga" => "audio/mpeg","mp2" => "audio/mpeg","mp3" => "audio/mpeg","mp2a" => "audio/mpeg","m2a" => "audio/mpeg","m3a" => "audio/mpeg","mp4a" => "audio/mp4","mp4" => "video/mp4");
	if(array_key_exists($dbfile["filetype"], $known_mime_types)) $mime_type=$known_mime_types[$dbfile["filetype"]];
} header("Content-Type: ".$mime_type);

//SET HEADERS
//header("Connection: closed");
header("Cache-Control: must-revalidate");
header("Content-disposition: attachment; filename=\"".$dbfile["filename"]."\"");
header("Content-Transfer-Encoding: binary");
header("Content-Description: File Transfer");
header("Accept-Range: bytes");
if(isset($_SERVER["HTTP_RANGE"])){
	header("Content-Length: ".$fdata_lenght);
	header("Content-Range: bytes ".$flength[0].'-'.$flength[1].'/'.$ffsize);
}else
	header("Content-Length: ".$ffsize);
flush();if($_SERVER["REQUEST_METHOD"]=="HEAD"){exit();}

$ffile=fopen(MAINDIR.$dbfile["realaddr"],'r');
  if ($ffile=='') {Error500("<h1>file error occured!</h1>");exit();}
fseek($ffile, intval($flength[0]));

$fchunk_size = CHUNKSIZE;$fsent_times=0;
while((($fsent_times+1)*$fchunk_size) < $fdata_lenght  && !connection_aborted() && !feof($ffile)){
	$fbuffer=fread($ffile, $fchunk_size);
	echo $fbuffer;flush();
	$fsent_times++;
	usleep(10000);
}
//SEND LEFT DATA
if($fdata_lenght-($fchunk_size*$fsent_times) > 0){
	$fbuffer=fread($ffile, $fdata_lenght-($fchunk_size*$fsent_times));echo $fbuffer;flush();$fbuffer='';
}
fclose($ffile);
set_time_limit(0);
exit();
?>
