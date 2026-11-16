<?php
require_once __DIR__.'/includes/core.php';
header('Content-Type: application/xml; charset=utf-8');
$scheme=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')?'https':'http';
$host=$_SERVER['HTTP_HOST']??'localhost';
if(!preg_match('/^[a-z0-9.:-]+$/i',$host))$host='localhost';
$base=$scheme.'://'.$host;
$urls=['index.php','explore.php','creators.php','page.php?name=about','page.php?name=copyright'];
foreach(public_videos() as $v)$urls[]='watch.php?v='.rawurlencode($v['id']);
foreach(creators() as $c)$urls[]='creator.php?name='.rawurlencode($c['slug']);
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach($urls as $url)echo '<url><loc>'.h($base.path_url($url)).'</loc></url>';
echo '</urlset>';
