<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['httponly'=>true,'secure'=>$secure,'samesite'=>'Lax','path'=>BASE_PATH ?: '/']);
}
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
date_default_timezone_set('UTC');
function path_url(string $path = ''): string { return (BASE_PATH ?: '') . '/' . ltrim($path,'/'); }
function h(mixed $text): string { return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function asset(string $file): string { return path_url('assets/'.ltrim($file,'/')); }
function no_cache(): void { header('Cache-Control: no-store, private'); }
function json_read(string $name, array $fallback=[]): array {
    $file = STORAGE_PATH . '/' . $name . '.json';
    if (!is_file($file)) return $fallback;
    $data = file_get_contents($file);
    if ($data === false) throw new RuntimeException('Cannot read storage: '.$name);
    $decoded = json_decode($data, true);
    if (!is_array($decoded)) throw new RuntimeException('Invalid JSON storage: '.$name);
    return $decoded;
}
function json_update(string $name, callable $callback, array $fallback=[]): mixed {
    if (!preg_match('/^[a-z_]+$/',$name)) throw new InvalidArgumentException('Invalid storage name');
    if (!is_dir(STORAGE_PATH) && !mkdir(STORAGE_PATH, 0750, true) && !is_dir(STORAGE_PATH)) throw new RuntimeException('Storage directory not writable');
    $file = STORAGE_PATH . '/' . $name . '.json';
    $lock = fopen(STORAGE_PATH.'/'.$name.'.lock', 'c+');
    if (!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Unable to lock storage');
    try {
        $current = json_read($name,$fallback);
        $next = $callback($current);
        if (!is_array($next)) throw new RuntimeException('Invalid storage update');
        $data = json_encode($next, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        if (strlen($data) > 16000000) throw new RuntimeException('Storage limit reached');
        if (is_file($file)) {
            $backDir=STORAGE_PATH.'/backups';
            if (!is_dir($backDir)) mkdir($backDir,0750,true);
            $backup=$backDir.'/'.$name.'-'.gmdate('Y-m-d').'.json';
            if (!file_exists($backup)) copy($file,$backup);
        }
        $tmp=tempnam(STORAGE_PATH,'.write-');
        if ($tmp === false) throw new RuntimeException('Cannot create temporary file');
        try {
            if (file_put_contents($tmp,$data,LOCK_EX)===false) throw new RuntimeException('Cannot save JSON');
            chmod($tmp,0640);
            if (!rename($tmp,$file)) throw new RuntimeException('Cannot publish JSON');
        } finally { if (file_exists($tmp)) @unlink($tmp); }
        return $next;
    } finally { flock($lock,LOCK_UN); fclose($lock); }
}
function settings(): array { return array_merge([
    'site_name'=>'Kinora','tagline'=>'A home for moving stories.','description'=>'An independent home for original video and the people behind it.',
    'accent'=>'#93a7ff','contact_email'=>'','logo_url'=>'','hero_title'=>'Motion deserves a better home.',
],json_read('settings'));
}
function videos(): array { $a=json_read('videos'); return array_values(array_filter($a,'is_array')); }
function creators(): array { $a=json_read('creators'); return array_values(array_filter($a,'is_array')); }
function comments(): array { $a=json_read('comments'); return array_values(array_filter($a,'is_array')); }
function creator_for(array $video): ?array { foreach(creators() as $c){ if (($c['id']??'')===($video['creator_id']??'')) return $c; } return null; }
function find_video(string $id): ?array { foreach (videos() as $v){if(($v['id']??'')===$id)return $v;} return null; }
function find_creator(string $slug): ?array { foreach(creators() as $c){if(($c['slug']??'')===$slug || ($c['id']??'')===$slug) return $c;} return null; }
function is_admin(): bool { return isset($_SESSION['admin']) && $_SESSION['admin'] === true; }
function has_admin(): bool { return !empty(json_read('admin')['hash'] ?? ''); }
function require_admin(): void { if (!is_admin()){header('Location: '.path_url('admin/'));exit;} }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.h(csrf_token()).'">'; }
function valid_csrf(?string $token): bool { return is_string($token) && hash_equals(csrf_token(),$token); }
function validate_csrf(): void { if(!valid_csrf($_POST['csrf']??$_SERVER['HTTP_X_CSRF_TOKEN']??null)){http_response_code(419);die('Security token expired. Refresh the page and try again.');} }
function valid_url(?string $url, bool $allowEmpty=true): bool {
    if ($allowEmpty && ($url===null || trim($url)==='')) return true;
    if (!is_string($url) || strlen($url)>2000 || !filter_var($url,FILTER_VALIDATE_URL)) return false;
    $p=parse_url($url); return is_array($p) && in_array(strtolower($p['scheme']??''),['http','https'],true) && !empty($p['host']) && !isset($p['user']) && !isset($p['pass']);
}
function nfmt(mixed $n): string { $n=(int)$n; return $n>=1000000 ? rtrim(rtrim(number_format($n/1000000,1), '0'),'.').'M' : ($n>=1000 ? rtrim(rtrim(number_format($n/1000,1),'0'),'.').'K' : number_format($n)); }
function format_date(?string $date): string { if(!$date) return 'Chưa rõ'; $t=strtotime($date); return $t ? date('d/m/Y',$t) : 'Chưa rõ'; }
function excerpt(string $text,int $size=125): string { return function_exists('mb_strimwidth')?mb_strimwidth($text,0,$size,'…','UTF-8'):(strlen($text)>$size?substr($text,0,$size).'…':$text); }
function slugify(string $s): string { $s=strtolower(trim($s)); if(function_exists('iconv'))$s=(string)iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); $s=preg_replace('/[^a-z0-9]+/','-',$s);return trim($s??'','-')?:'creator'; }
function uid(string $prefix='v'): string {return $prefix.'_'.bin2hex(random_bytes(5));}
function public_videos(): array {return array_values(array_filter(videos(),fn($v)=>($v['visibility']??'public')==='public' && ($v['status']??'published')==='published'));}
function video_accessible(array $v): bool {return is_admin() || (($v['visibility']??'public')!=='private' && ($v['status']??'published')==='published');}
function video_href(array $video): string {return path_url('watch.php?v='.rawurlencode((string)$video['id']));}
function creator_href(array $creator): string {return path_url('creator.php?name='.rawurlencode((string)$creator['slug']));}
function go(string $path): never {header('Location: '.path_url($path));exit;}
function flash(string $text,string $kind='success'): void {$_SESSION['flash']=['text'=>$text,'kind'=>$kind];}
function flash_render(): void {if(!empty($_SESSION['flash'])){$f=$_SESSION['flash'];unset($_SESSION['flash']);echo '<div class="flash '.h($f['kind']).'">'.h($f['text']).'</div>';}}
function response_json(array $arr,int $code=200): never {http_response_code($code);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($arr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function try_str(string $key,int $max=500): string {return trim(substr((string)($_POST[$key]??''),0,$max));}
function int_post(string $key): int { $n=filter_var($_POST[$key]??0,FILTER_VALIDATE_INT);return max(0,min(999999999999,$n===false?0:(int)$n)); }
function date_post(string $key): string { $date=try_str($key,50);return $date && strtotime($date)!==false ? $date : ''; }
function head_meta(string $title,string $description,?string $image=null,bool $index=true): void {
    $s=settings(); $name=$s['site_name']; echo '<!doctype html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#090b10">';
    echo '<title>'.h($title.' · '.$name).'</title><meta name="description" content="'.h(excerpt($description,155)).'">';
    if(!$index)echo '<meta name="robots" content="noindex,nofollow">';
    echo '<meta property="og:title" content="'.h($title).'"><meta property="og:description" content="'.h(excerpt($description,155)).'">';
    if($image && valid_url($image))echo '<meta property="og:image" content="'.h($image).'">';
    echo '<link rel="icon" type="image/svg+xml" href="'.h(asset('favicon.svg')).'"><link rel="stylesheet" href="'.h(asset('css/style.css')).'?v=1.0"><script defer src="'.h(asset('js/app.js')).'?v=1.0"></script><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    $accent=$s['accent'];if(preg_match('/^#[0-9a-fA-F]{6}$/',$accent))echo '<style>:root{--accent:'.h($accent).'}</style>';
    echo '</head><body>';}
