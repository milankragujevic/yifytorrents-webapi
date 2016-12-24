<?php
set_time_limit(0);
error_reporting(0);
$page = (int)$_GET['page'];
$genre = rawurldecode($_GET['genre']);
$query = rawurldecode($_GET['query']);
if(empty($genre)) { $genre = 'All'; }
$genre = ucwords(str_replace('-', ' ', $genre));
if($page < 1) { $page = 1; }
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
if(!is_dir(__DIR__ . '/cache/')) { mkdir(__DIR__ . '/cache/'); }
$url = 'https://yts.ag/api/v2/list_movies.json?limit=25&quality=720p&sort_by=seeds&order_by=desc&query_term=' . urlencode($query) . '&genre=' . urlencode($genre) . '&page=' . $page;
$cache = __DIR__ . '/cache/' . sha1($url) . '.json';
if(!is_file($cache) || time() - filemtime($cache) > 60 * 60) {
  file_put_contents($cache, file_get_contents($url));
}
$yts = json_decode(file_get_contents($cache), true);
$movies = [];
foreach($yts['data']['movies'] as $item) {
	$torrent = []; foreach($item['torrents'] as $_t) { if($_t['quality'] == '720p') { $torrent = $_t; } }
	$movies[] = [
		'title' => $item['title'],
		'year' => $item['year'],
		'background' => $item['background_image'],
		'poster' => str_replace('medium', 'large', $item['medium_cover_image']),
		'genres' => implode(', ', $item['genres']),
		'rating' => $item['rating'],
		'runtime' => $item['runtime'] . ' mins',
		'quality' => '720p',
		'infoHash' => $torrent['hash']
	];
}
echo json_encode($movies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
