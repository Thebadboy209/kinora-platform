<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/core.php';
no_cache();
if($_SERVER['REQUEST_METHOD']!=='POST')response_json(['ok'=>false,'error'=>'Method not allowed'],405);
if(!is_admin())response_json(['ok'=>false,'error'=>'Session expired. Sign in again.'],401);
if(!valid_csrf($_POST['csrf']??null))response_json(['ok'=>false,'error'=>'Invalid security token'],419);
if(!isset($_FILES['video']))response_json(['ok'=>false,'error'=>'No uploaded video. PHP limits may be too low (upload_max_filesize / post_max_size).'],400);
$f=$_FILES['video'];if(is_array($f['error']))response_json(['ok'=>false,'error'=>'Only one video per request'],400);
if($f['error']!==UPLOAD_ERR_OK)response_json(['ok'=>false,'error'=>'PHP upload error code '.(int)$f['error'].'. Check hosting upload limits.'],400);
if($f['size']<1 || $f['size']>PONE_LIMIT_BYTES)response_json(['ok'=>false,'error'=>'File exceeds 1 GiB limit or is empty'],400);
$extension=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));if(!in_array($extension,['mp4','webm','mov','m4v'],true))response_json(['ok'=>false,'error'=>'Allowed formats: mp4, webm, mov, m4v'],400);
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
if(!in_array($mime,['video/mp4','video/webm','video/quicktime','application/octet-stream','video/x-m4v'],true))response_json(['ok'=>false,'error'=>'Unsupported file MIME type: '.$mime],400);
if(!is_uploaded_file($f['tmp_name']))response_json(['ok'=>false,'error'=>'Invalid upload'],400);
if(!function_exists('curl_init'))response_json(['ok'=>false,'error'=>'PHP cURL extension is required'],500);
$ch=curl_init(PONE_UPLOAD_URL);
$post=['files[]'=>new CURLFile($f['tmp_name'],$mime,basename($f['name']))];
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>300,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
if(defined('CURLOPT_PROTOCOLS_STR'))curl_setopt($ch,CURLOPT_PROTOCOLS_STR,'https');
$body=curl_exec($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
if($body===false)response_json(['ok'=>false,'error'=>'pone.rs connection error: '.$error],502);
if($status<200 || $status>=300)response_json(['ok'=>false,'error'=>'pone.rs returned HTTP '.$status.'. '.substr(strip_tags($body),0,220)],502);
$data=json_decode($body,true);
if(!is_array($data))response_json(['ok'=>false,'error'=>'pone.rs response is not valid JSON: '.substr(strip_tags($body),0,180)],502);
// API docs do not specify the complete JSON schema. Inspect candidate URLs conservatively.
$found=[];
$walk=function($node,$key='')use(&$walk,&$found){
 if(is_string($node) && valid_url($node,false)){$found[]=['url'=>$node,'key'=>$key];return;}
 if(is_array($node))foreach($node as $k=>$v)$walk($v,is_string($k)?$k:$key);
};$walk($data);
// Prioritize a direct-media URL, otherwise a field explicitly named URL/link.
usort($found,function($a,$b){$score=fn($v)=>(preg_match('/\.(mp4|webm|mov|m4v)([?#].*)?$/i',$v['url'])?10:0)+(preg_match('/^(url|direct_url|file_url|link|download|raw)$/i',$v['key'])?5:0);return $score($b)<=>$score($a);});
$url=$found[0]['url']??'';
if(!$url)response_json(['ok'=>false,'error'=>'pone.rs accepted the upload but returned no recognizable URL. Check the API response manually.'],502);
response_json(['ok'=>true,'url'=>$url,'playback_likely'=>(bool)preg_match('/\.(mp4|webm|m4v)([?#].*)?$/i',$url),'message'=>'Uploaded. Please verify playback before publishing.']);
