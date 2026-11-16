<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/core.php';
no_cache();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
validate_csrf();
$action=try_str('action',60);
try {
 if($action==='install') {
    if(has_admin()) throw new RuntimeException('Admin account already exists');
    if(INSTALL_SECRET==='CHANGE_THIS_TO_A_LONG_PRIVATE_INSTALL_SECRET' || strlen(INSTALL_SECRET)<20) throw new RuntimeException('Set a unique INSTALL_SECRET in config.php before installation.');
    if(!hash_equals(INSTALL_SECRET,(string)($_POST['install_secret']??'')))throw new RuntimeException('Incorrect installation secret');
    $pw=(string)($_POST['password']??'');if(strlen($pw)<12)throw new RuntimeException('Password must have at least 12 characters');
    json_update('admin',function($a)use($pw){if(!empty($a['hash']))throw new RuntimeException('Account exists');return ['hash'=>password_hash($pw,PASSWORD_DEFAULT),'created_at'=>gmdate('c')];});
    $_SESSION['admin']=true;session_regenerate_id(true);flash('Thiết lập quản trị thành công.');go('admin/');
 }
 if($action==='login') {
    if(!has_admin())throw new RuntimeException('Set up the administrator account first');
    $attempt=(int)($_SESSION['login_attempts']??0);$start=(int)($_SESSION['login_last']??0);
    if($attempt>=5 && time()-$start<600)throw new RuntimeException('Too many attempts. Try again in 10 minutes.');
    if($start>0 && time()-$start>=600)$_SESSION['login_attempts']=0;
    $admin=json_read('admin');
    if(!password_verify((string)($_POST['password']??''),$admin['hash']??'')){
       $_SESSION['login_attempts']=(int)($_SESSION['login_attempts']??0)+1;$_SESSION['login_last']=time();
       throw new RuntimeException('Incorrect password');
    }
    session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['login_attempts']=0;flash('Đăng nhập thành công.');go('admin/');
 }
 require_admin();
 if($action==='logout') {$_SESSION=[];session_destroy();go('admin/');}
 if($action==='save_video') {
    $id=try_str('id',64);$existing=$id?find_video($id):null;if($id && !$existing)throw new RuntimeException('Video not found');
    $published=try_str('publish',12)==='published';
    $video_url=try_str('video_url',2000);if(!valid_url($video_url))throw new RuntimeException('Invalid video URL');
    if($published && !$video_url)throw new RuntimeException('You must upload a video or enter a video URL first');
    $title=try_str('title',180);if(!$title)throw new RuntimeException('Please enter a video title');
    $urls=['thumbnail_url'=>try_str('thumbnail_url',2000),'original_url'=>try_str('original_url',2000)];
    foreach($urls as $key=>$u){if(!valid_url($u))throw new RuntimeException('Invalid URL in '.$key);}
    $creator_id=try_str('creator_id',64);if($creator_id && !in_array($creator_id,array_column(creators(),'id'),true))throw new RuntimeException('Creator not found');
    $visibility=try_str('visibility',15);if(!in_array($visibility,['public','unlisted','private'],true))$visibility='public';
    $record=array_merge($existing??[],[
      'id'=>$id?:uid('vid'),'title'=>$title,'description'=>try_str('description',12000),
      'video_url'=>$video_url,'thumbnail_url'=>$urls['thumbnail_url'],'category'=>try_str('category',75),
      'duration'=>try_str('duration',20),'resolution'=>try_str('resolution',50),'tags'=>try_str('tags',350),
      'creator_id'=>$creator_id,'owner_name'=>try_str('owner_name',140),'original_url'=>$urls['original_url'],
      'copyright_owner'=>try_str('copyright_owner',160),'copyright'=>try_str('copyright',700),
      'original_date'=>date_post('original_date'),'display_date'=>date_post('display_date')?:gmdate('Y-m-d\TH:i'),
      'views'=>int_post('views'),'likes'=>int_post('likes'),'dislikes'=>int_post('dislikes'),
      'shares'=>int_post('shares'),'comments_count'=>int_post('comments_count'),
      'visibility'=>$visibility,'featured'=>isset($_POST['featured']),
      'status'=>$published?'published':'draft','metrics_source'=>'admin_entered',
      'created_at'=>$existing['created_at']??gmdate('c'),'updated_at'=>gmdate('c')]);
    json_update('videos',function($rows)use($record,$id){$found=false;foreach($rows as &$r){if(($r['id']??'')===$id && $id){$r=$record;$found=true;break;}}unset($r);if(!$found)$rows[]=$record;return $rows;});
    flash('Đã '.($published?'xuất bản':'lưu nháp').' video.');go('admin/?page=videos');
 }
 if($action==='delete_video') {
    $id=try_str('id',64);json_update('videos',fn($a)=>array_values(array_filter($a,fn($v)=>($v['id']??'')!==$id)));
    json_update('comments',fn($a)=>array_values(array_filter($a,fn($v)=>($v['video_id']??'')!==$id)));
    flash('Đã xóa video khỏi danh mục (tệp trên pone.rs không bị xóa).');go('admin/?page=videos');
 }
 if($action==='duplicate_video') {
    $v=find_video(try_str('id',64));if(!$v)throw new RuntimeException('Video not found');$v['id']=uid('vid');$v['title'].=' (Copy)';$v['status']='draft';$v['featured']=false;$v['created_at']=gmdate('c');$v['updated_at']=gmdate('c');
    json_update('videos',function($a)use($v){$a[]=$v;return $a;});flash('Đã tạo bản sao ở trạng thái nháp.');go('admin/?page=editor&id='.rawurlencode($v['id']));
 }
 if($action==='save_creator') {
    $id=try_str('id',64);$name=try_str('name',140);if(!$name)throw new RuntimeException('Please enter creator name');
    $slug=slugify(try_str('slug',80)?:$name);
    $existing=null;foreach(creators() as $x){if(($x['id']??'')===$id)$existing=$x;else if($x['slug']===$slug)throw new RuntimeException('Creator username already exists');}
    if($id && !$existing)throw new RuntimeException('Creator not found');
    $avatar=try_str('avatar',2000);$cover=try_str('cover',2000);$web=try_str('website',2000);
    foreach([$avatar,$cover,$web] as $u){if(!valid_url($u))throw new RuntimeException('Invalid creator image or website URL');}
    $row=['id'=>$id?:uid('cr'),'name'=>$name,'slug'=>$slug,'bio'=>try_str('bio',2000),'avatar'=>$avatar,'cover'=>$cover,'website'=>$web,'created_at'=>$existing['created_at']??gmdate('c')];
    json_update('creators',function($a)use($id,$row){$found=false;foreach($a as &$x){if(($x['id']??'')===$id && $id){$x=$row;$found=true;break;}}unset($x);if(!$found)$a[]=$row;return $a;});
    flash('Đã lưu hồ sơ creator.');go('admin/?page=creators');
 }
 if($action==='delete_creator') {
    $id=try_str('id',64);foreach(videos() as $v)if(($v['creator_id']??'')===$id)throw new RuntimeException('Cannot delete creator with linked videos');
    json_update('creators',fn($a)=>array_values(array_filter($a,fn($c)=>($c['id']??'')!==$id)));flash('Đã xóa creator.');go('admin/?page=creators');
 }
 if($action==='save_settings') {
    $settings=['site_name'=>try_str('site_name',90)?:'Kinora','tagline'=>try_str('tagline',180),
      'description'=>try_str('description',500),'hero_title'=>try_str('hero_title',160),
      'contact_email'=>try_str('contact_email',150),'logo_url'=>try_str('logo_url',2000),'accent'=>try_str('accent',16)];
    if(!valid_url($settings['logo_url']))throw new RuntimeException('Invalid logo URL');
    if($settings['contact_email'] && !filter_var($settings['contact_email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid email');
    if(!preg_match('/^#[0-9a-f]{6}$/i',$settings['accent']))$settings['accent']='#93a7ff';
    $new=(string)($_POST['new_password']??'');
    if($new!==''){
      if(strlen($new)<12)throw new RuntimeException('New password must have at least 12 characters');
      $a=json_read('admin');if(!password_verify((string)($_POST['current_password']??''),$a['hash']??''))throw new RuntimeException('Current password incorrect');
    }
    json_update('settings',fn($a)=>array_merge($a,$settings));
    if($new!=='')json_update('admin',function($a)use($new){$a['hash']=password_hash($new,PASSWORD_DEFAULT);return $a;});
    flash('Đã lưu cài đặt.');go('admin/?page=settings');
 }
 if($action==='add_comment') {
    $v=find_video(try_str('video_id',64));if(!$v)throw new RuntimeException('Select a video');
    $content=try_str('content',1200);if(!$content)throw new RuntimeException('Enter comment text');
    $row=['id'=>uid('cm'),'video_id'=>$v['id'],'author'=>try_str('author',80)?:'Platform Editor','content'=>$content,'type'=>'admin','created_at'=>gmdate('c')];
    json_update('comments',function($a)use($row){$a[]=$row;return $a;});flash('Đã thêm bình luận ghi rõ do admin nhập.');go('admin/?page=comments&video='.$v['id']);
 }
 if($action==='generate_comments') {
    $v=find_video(try_str('video_id',64));if(!$v)throw new RuntimeException('Select a video');
    $num=max(1,min(MAX_DEMO_COMMENTS,int_post('quantity')));
    $samples=['A compelling visual story with a distinctive atmosphere.','The composition and pacing work really well together.','Interesting perspective on this subject.','Such a thoughtful editing style.','The lighting makes every scene stand out.','I enjoyed the visual approach to this piece.','A beautiful use of colors and movement.','The sound design adds so much to the mood.','Strong attention to detail throughout.','The storytelling feels very intentional.',
    'Góc máy đẹp và cách dựng khá sáng tạo.','Màu sắc và nhịp dựng rất hài hòa.','Tác phẩm có phong cách hình ảnh rất riêng.','Bố cục cảnh quay ấn tượng.','Phần hình ảnh được đầu tư kỹ lưỡng.','Câu chuyện được kể rất tự nhiên.','Mình thích cách sử dụng ánh sáng trong video.','Chuyển cảnh mượt mà và dễ theo dõi.','Một ý tưởng trình bày thú vị.','Đoạn kết được xử lý khá tinh tế.'];
    $names=['Demo Viewer A','Demo Viewer B','Demo Viewer C','Demo Viewer D','Demo Viewer E'];shuffle($samples);$rows=[];
    for($i=0;$i<$num;$i++)$rows[]=['id'=>uid('cm'),'video_id'=>$v['id'],'author'=>$names[$i%count($names)],'content'=>$samples[$i%count($samples)],'type'=>'demo','created_at'=>gmdate('c',time()-random_int(60,86400*10))];
    json_update('comments',function($a)use($rows){return array_merge($a,$rows);});flash('Đã tạo '.$num.' bình luận mô phỏng được đánh dấu rõ ràng.');go('admin/?page=comments&video='.$v['id']);
 }
 if($action==='save_comment') {
    $id=try_str('id',64);$author=try_str('author',80);$content=try_str('content',1200);
    if(!$author || !$content)throw new RuntimeException('Author and content are required');
    $found=false;
    json_update('comments',function($a)use($id,$author,$content,&$found){foreach($a as &$c){if(($c['id']??'')===$id){$c['author']=$author;$c['content']=$content;$c['updated_at']=gmdate('c');$found=true;break;}}unset($c);return $a;});
    if(!$found)throw new RuntimeException('Comment not found');flash('Đã cập nhật bình luận.');go('admin/?page=comments');
 }
 if($action==='delete_comment') {
    $id=try_str('id',64);json_update('comments',fn($a)=>array_values(array_filter($a,fn($r)=>($r['id']??'')!==$id)));flash('Đã xóa bình luận.');go('admin/?page=comments');
 }
 if($action==='clear_demo') {
    $id=try_str('video_id',64);json_update('comments',fn($a)=>array_values(array_filter($a,fn($r)=>!(($r['video_id']??'')===$id && ($r['type']??'')==='demo'))));flash('Đã xóa các bình luận mô phỏng của video.');go('admin/?page=comments&video='.rawurlencode($id));
 }
 throw new RuntimeException('Unknown operation');
} catch(Throwable $e){flash($e->getMessage(),'error'); $page=in_array($action,['login','install'],true)?'admin/':('admin/?page='.match(true){str_contains($action,'video')=>'videos',str_contains($action,'comment')=>'comments',str_contains($action,'creator')=>'creators',str_contains($action,'settings')=>'settings',default=>'dashboard'});go($page);}
