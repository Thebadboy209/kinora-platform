<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/core.php';
no_cache();require_admin();
if($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['download'])){
 $payload=['format'=>'kinora-backup-v1','exported_at'=>gmdate('c'),'videos'=>videos(),'creators'=>creators(),'comments'=>comments(),'settings'=>settings()];
 header('Content-Type: application/json; charset=utf-8');
 header('Content-Disposition: attachment; filename="kinora-backup-'.gmdate('Y-m-d').'.json"');
 echo json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
validate_csrf();
try {
 $f=$_FILES['backup']??null;
 if(!$f || !isset($f['error']) || $f['error']!==UPLOAD_ERR_OK || $f['size']<1 || $f['size']>2097152 || !is_uploaded_file($f['tmp_name']))throw new RuntimeException('Invalid backup or file larger than 2 MB');
 $payload=json_decode((string)file_get_contents($f['tmp_name']),true,512,JSON_THROW_ON_ERROR);
 if(!is_array($payload) || ($payload['format']??'')!=='kinora-backup-v1')throw new RuntimeException('Not a compatible Kinora backup');
 foreach(['videos','creators','comments','settings'] as $k){if(!isset($payload[$k]) || !is_array($payload[$k]))throw new RuntimeException('Backup is missing '.$k);}
 if(count($payload['videos'])>5000 || count($payload['creators'])>5000 || count($payload['comments'])>20000)throw new RuntimeException('Backup exceeds supported record limits');
 foreach($payload['videos'] as $v){if(!is_array($v) || empty($v['id']) || empty($v['title']) || !valid_url((string)($v['video_url']??'')))throw new RuntimeException('Invalid video in backup');}
 foreach($payload['creators'] as $c){if(!is_array($c) || empty($c['id']) || empty($c['slug']) || empty($c['name']))throw new RuntimeException('Invalid creator in backup');}
 foreach($payload['comments'] as $c){if(!is_array($c) || empty($c['id']) || empty($c['video_id']) || !in_array(($c['type']??''),['demo','admin'],true))throw new RuntimeException('Invalid comment in backup');}
 $old=json_read('settings');
 foreach(['site_name','tagline','description','hero_title','accent','contact_email','logo_url'] as $k){if(!isset($payload['settings'][$k]) || !is_string($payload['settings'][$k]))throw new RuntimeException('Invalid settings in backup');}
 if(!preg_match('/^#[0-9a-fA-F]{6}$/',$payload['settings']['accent']))throw new RuntimeException('Invalid accent color');
 if(!valid_url($payload['settings']['logo_url']))throw new RuntimeException('Invalid logo URL');
 foreach(['videos','creators','comments','settings'] as $key){json_update($key,fn($a)=>$payload[$key]);}
 flash('Khôi phục JSON thành công.');go('admin/?page=settings');
} catch(Throwable $e){flash('Lỗi khôi phục: '.$e->getMessage(),'error');go('admin/?page=settings');}
