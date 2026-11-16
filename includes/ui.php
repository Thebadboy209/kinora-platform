<?php
require_once __DIR__.'/core.php';
require_once __DIR__.'/icons.php';
function header_ui(string $active='home', bool $admin=false): void { $s=settings(); ?>
<div class="app-shell <?= $admin?'admin-shell':'' ?>">
<aside class="sidebar" id="sidebar">
 <a class="brand" href="<?=h(path_url())?>"><span class="brand-symbol"><?php if(!empty($s['logo_url']) && valid_url($s['logo_url'])):?><img src="<?=h($s['logo_url'])?>" alt=""><?php else:?><?=icon('play',19)?><?php endif;?></span><span class="brand-text"><?=h($s['site_name'])?><small>VIDEO PLATFORM</small></span></a>
 <div class="nav-head">DISCOVER</div>
 <nav class="side-nav" aria-label="Điều hướng chính">
  <?php foreach([['home','index.php','house','Trang chủ'],['explore','explore.php','compass','Khám phá'],['latest','explore.php?sort=newest','clock-3','Mới nhất'],['popular','explore.php?sort=popular','trending-up','Phổ biến'],['creators','creators.php','users','Nhà sáng tạo']] as $n): ?>
  <a class="side-link <?= $active===$n[0]?'active':'' ?>" href="<?=h(path_url($n[1]))?>"><?=icon($n[2])?><span><?=h($n[3])?></span></a>
  <?php endforeach ?>
 </nav>
 <div class="nav-head nav-divider">PLATFORM</div>
 <nav class="side-nav">
  <a class="side-link <?= $active==='about'?'active':'' ?>" href="<?=h(path_url('page.php?name=about'))?>"><?=icon('info')?><span>Giới thiệu</span></a>
  <a class="side-link <?= $active==='copyright'?'active':'' ?>" href="<?=h(path_url('page.php?name=copyright'))?>"><?=icon('shield-check')?><span>Bản quyền</span></a>
  <a class="side-link <?= $active==='contact'?'active':'' ?>" href="<?=h(path_url('page.php?name=contact'))?>"><?=icon('mail')?><span>Liên hệ</span></a>
 </nav>
 <div class="sidebar-bottom"><div class="sidebar-note"><span class="status-dot"></span> Independent creator space<small>Built for original stories.</small></div><small>© <?=date('Y')?> <?=h($s['site_name'])?></small></div>
</aside>
<div class="app-content">
<header class="topbar"><button type="button" class="icon-btn mobile-menu" data-toggle-nav aria-label="Mở menu"><?=icon('menu')?></button>
<a class="mobile-logo" href="<?=h(path_url())?>"><?=h($s['site_name'])?></a>
<form class="search-form" action="<?=h(path_url('explore.php'))?>" method="get"><?=icon('search',19)?><input name="q" aria-label="Tìm video" value="<?=h($_GET['q']??'')?>" placeholder="Tìm video, người sáng tạo..." autocomplete="off"><button type="submit" aria-label="Tìm kiếm"><?=icon('arrow-right',17)?></button></form>
<div class="top-actions"><a class="top-small-link" href="<?=h(path_url('explore.php'))?>">Khám phá</a>
<?php if(is_admin()): ?><a class="btn btn-primary btn-small" href="<?=h(path_url('admin/'))?>"><?=icon('layout-dashboard',16)?> Quản trị</a>
<?php else: ?><a class="top-login" href="<?=h(path_url('admin/'))?>" title="Đăng nhập quản trị"><?=icon('lock-keyhole',18)?><span>Admin</span></a><?php endif; ?></div></header>
<main class="main-area"><?php flash_render(); ?>
<?php }
function footer_ui(): void { $s=settings(); ?>
</main><footer class="footer"><span>© <?=date('Y')?> <?=h($s['site_name'])?>. Crafted for motion.</span><span><a href="<?=h(path_url('page.php?name=privacy'))?>">Privacy</a><a href="<?=h(path_url('page.php?name=terms'))?>">Terms</a><a href="<?=h(path_url('page.php?name=copyright'))?>">Copyright</a></span></footer></div></div>
<div class="nav-overlay" id="navOverlay"></div><div id="toast" role="status" aria-live="polite"></div>
</body></html>
<?php }
function video_card(array $v): void {$c=creator_for($v);$thumb=$v['thumbnail_url']??''; ?>
<a class="video-card" href="<?=h(video_href($v))?>">
 <div class="video-art"><?php if($thumb && valid_url($thumb)): ?><img loading="lazy" src="<?=h($thumb)?>" alt="Thumbnail: <?=h($v['title']??'')?>"><?php else: ?><div class="abstract-thumbnail"><span><?=icon('play',32)?></span><b>ORIGINAL MOTION</b></div><?php endif; ?><span class="video-hover-play"><?=icon('play',24)?></span><?php if(!empty($v['duration'])): ?><span class="time-chip"><?=h($v['duration'])?></span><?php endif; ?></div>
 <div class="video-card-info"><div class="avatar avatar-sm"><?php if(!empty($c['avatar']) && valid_url($c['avatar'])):?><img loading="lazy" src="<?=h($c['avatar'])?>" alt=""><?php else: ?><?=h(strtoupper(substr($c['name']??$v['owner_name']??'K',0,1)))?><?php endif ?></div><div class="video-copy"><h3><?=h($v['title']??'Untitled')?></h3><p><?=h($c['name']??($v['owner_name']??'Unknown creator'))?></p><div class="meta"><?=h(nfmt($v['views']??0))?> lượt xem <span class="dot-sep">·</span> <?=h(format_date($v['display_date']??''))?></div></div></div>
</a>
<?php }
function empty_content(string $title='Chưa có video',string $description='Nội dung sẽ xuất hiện khi quản trị viên đăng video đầu tiên.'): void {echo '<div class="empty-state">'.icon('clapperboard',32).'<h3>'.h($title).'</h3><p>'.h($description).'</p>'.(is_admin()?'<a class="btn btn-primary" href="'.h(path_url('admin/?page=editor')).'">'.icon('plus',17).' Đăng video đầu tiên</a>':'').'</div>';}
