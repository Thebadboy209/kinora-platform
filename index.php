<?php
require_once __DIR__.'/includes/ui.php';
$list=public_videos();usort($list,fn($a,$b)=>strcmp($b['display_date']??'',$a['display_date']??''));
$featured=null;foreach($list as $v){if(!empty($v['featured'])){$featured=$v;break;}}$featured??=$list[0]??null;
$popular=$list;usort($popular,fn($a,$b)=>($b['views']??0)<=>($a['views']??0));
head_meta('Khám phá câu chuyện', 'Explore original films, visual stories and independent creators.',$featured['thumbnail_url']??null);
header_ui('home'); ?>
<div class="section-kicker"><span class="kicker-square"></span> WELCOME TO THE PLATFORM <span class="kicker-line"></span> CURATED VIDEO EXPERIENCE</div>
<?php if($featured): ?>
<section class="hero hero-filled"><?php if(valid_url($featured['thumbnail_url']??'') && !empty($featured['thumbnail_url'])):?><img class="hero-bg-photo" src="<?=h($featured['thumbnail_url'])?>" alt=""><?php endif;?><div class="hero-bg"></div><div class="hero-content"><div class="eyebrow"> <span class="pulse"></span> EDITOR'S PICK <span class="eyebrow-line"></span> FEATURED STORY</div><h1><?=h($featured['title'])?></h1><p><?=h(excerpt($featured['description']??'A fresh story worth exploring.',175))?></p><div class="hero-meta"><?=icon('user-round',15)?><?=h(creator_for($featured)['name']??($featured['owner_name']??'Creator'))?><span class="dot-sep">·</span><?=h(nfmt($featured['views']??0))?> views</div><a class="btn btn-light" href="<?=h(video_href($featured))?>"><?=icon('play',18)?> Watch film <?=icon('arrow-up-right',17)?></a></div><div class="hero-number">01 / FEATURED</div></section>
<?php else: ?>
<section class="hero hero-empty"><div class="hero-bg"></div><div class="hero-content"><div class="eyebrow"><span class="pulse"></span> A NEW SPACE FOR CREATIVE MOTION</div><h1><?=h(settings()['hero_title'])?></h1><p><?=h(settings()['tagline'])?> Một thư viện video tinh tuyển, nơi mỗi tác phẩm đều có câu chuyện và chủ sở hữu rõ ràng.</p><a class="btn btn-light" href="<?=h(path_url('explore.php'))?>">Explore the platform <?=icon('arrow-up-right',17)?></a></div><div class="hero-decoration"><span></span><span></span><span></span></div></section>
<?php endif; ?>
<section class="section-block"><div class="section-heading"><div><div class="section-index">01 / NEW ARRIVALS</div><h2>Vừa xuất bản <span class="header-dot">.</span></h2><p>Những tác phẩm mới nhất trên nền tảng</p></div><a class="text-arrow" href="<?=h(path_url('explore.php?sort=newest'))?>">Xem tất cả <?=icon('arrow-up-right',18)?></a></div>
<?php if($list): ?><div class="video-grid"><?php foreach(array_slice($list,0,8) as $v)video_card($v); ?></div><?php else: ?><?php empty_content(); ?><?php endif; ?></section>
<div class="banner-editorial"><div><span class="section-index">BUILT FOR ORIGINALS</span><h2>Every frame tells<br>someone's story<span class="header-dot">.</span></h2><p>Tác phẩm có chủ sở hữu, nguồn xuất bản và thông tin minh bạch.</p></div><div class="banner-mark"><?=icon('aperture',76)?></div><a class="btn btn-outline" href="<?=h(path_url('page.php?name=about'))?>">Our philosophy <?=icon('arrow-up-right',17)?></a></div>
<section class="section-block"><div class="section-heading"><div><div class="section-index">02 / COMMUNITY PICKS</div><h2>Được quan tâm <span class="header-dot">.</span></h2><p>Các video theo số lượt xem được khai báo</p></div><a class="text-arrow" href="<?=h(path_url('explore.php?sort=popular'))?>">Khám phá <?=icon('arrow-up-right',18)?></a></div>
<?php if($list): ?><div class="video-grid"><?php foreach(array_slice($popular,0,4) as $v)video_card($v); ?></div><?php else: ?><?php empty_content('Bộ sưu tập đang hình thành','Video nổi bật sẽ được hiển thị tại đây sau khi đăng.'); ?><?php endif; ?></section>
<?php footer_ui(); ?>
