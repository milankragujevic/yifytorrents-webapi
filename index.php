<?php
function _cg($url) {
	if(!is_dir(__DIR__ . '/cache/')) { mkdir(__DIR__ . '/cache/'); }
	$cache = __DIR__ . '/cache/' . sha1($url) . '.json';
	if(!is_file($cache) || time() - filemtime($cache) > 60 * 60) {
	  file_put_contents($cache, file_get_contents($url));
	}
	$api = json_decode(file_get_contents($cache), true);	
	return $api;
}

$tmdb_key = 'd763549ca4b13a17f5ef6ff914033fad';

set_time_limit(0);
error_reporting(0);
$page = (int)$_GET['page'];
$genre = rawurldecode($_GET['genre']);
$query = rawurldecode($_GET['query']);
$genre = ucwords(str_replace('-', ' ', $genre));
if($page < 1) { $page = 1; }
$limit = 25;
if(isset($_GET['limit'])) {
	$limit = (int) $_GET['limit'];
	if($limit > 50) { $limit = 50; }
	if($limit < 25) { $limit = 25; }
}
$sort_by = 'views';
if(isset($_GET['sort_by'])) {
	$sort_by = $_GET['sort_by'];	
}
$sort_order = 'desc';
if(isset($_GET['sort_order'])) {
	$sort_order = $_GET['sort_order'];	
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$yts = _cg('https://yts.ag/api/v2/list_movies.json?limit=' . $limit . '&quality=720p&sort_by=' . $sort_by . '&order_by=' . $sort_order . '&query_term=' . urlencode($query) . '&genre=' . urlencode($genre) . '&page=' . $page);
$movies = [];
foreach($yts['data']['movies'] as $item) {
	$torrent = []; foreach($item['torrents'] as $_t) { if($_t['quality'] == '720p') { $torrent = $_t; } }
	$imdbID = $item['imdb_code'];
	$tmdb_find = _cg('https://api.themoviedb.org/3/find/' . $imdbID . '?api_key=' . $tmdb_key . '&language=en-US&external_source=imdb_id');
	$tmdb = $tmdb_find['movie_results'][0];
	$movies[] = array(
		'title' => $tmdb['title'],
		'year' => $item['year'],
		'released_date' => date('F jS, Y', strtotime($tmdb['release_date'])),
		'background' => 'https://image.tmdb.org/t/p/original' . $tmdb['backdrop_path'],
		'poster' => 'https://image.tmdb.org/t/p/w300' . $item['poster_path'],
		'genres' => implode(', ', $item['genres']),
		'rating' => $item['rating'],
		'runtime' => $item['runtime'] . ' mins',
		'quality' => '720p',
		'infoHash' => $torrent['hash'],
		'seeds' => $torrent['seeds']
	);
}
echo json_encode($movies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
