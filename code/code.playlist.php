<?php

// The class handling song info
include_once('classes/class.song.php');

if (ALLOW_REQUESTS) {
	// An array of song objects with the top requested songs
	$topRequestedSongs = Song::getTopRequestedSongs();
}

$start = Def('start', 0);	// Where the playlist must start
$limit = Def('limit', 25);	// How many items will be displayed
$search = Def('search');	// The search string
$character = Def('character'); // The letter to sort the playlist by
if ("All" == $character) {
	unset($character);
}


//########## BUILD SEARCH STRING ################
$search_words = '';
if ($search <> '') {
	$search_words = array();
	$temp = explode(' ', $search);
	reset($temp);
	while (list($key, $val) = each($temp)) {
		$val = trim($val);
		if (!empty($val)) {
			$search_words[] = $val;
		}
	}
}

// An array of song objects matching the search criteria
$playlistSongs = Song::getPlaylistSongs($search_words, $character, $start, $limit);
$cnt = Song::getPlaylistSongCount();

//########## =================== ################
$first = $start + 1;
$last = min($cnt, $start + $limit);

// Create the previous and next links based on the result
if ($cnt > 0) {
	$searchstr = urlencode($search);
	$prev = max(0, $start - $limit);
	if ($start > 0) {
		$prevlnk = "<a href='?start=$prev&limit={$limit}&character=$character&search=$searchstr'>&lt;&lt; Previous</a>";
	}

	$tmp = ($start + $limit);
	if ($tmp < $cnt) {
		$nextlnk = "<a href='?start=$tmp&limit={$limit}&character=$character&search=$searchstr'>Next &gt;&gt;</a>";
	}
}

$comingSongs = Song::getComingSongs(QUEUE_RULE);

function requestable($song) 
{
    global $comingSongs;

    $artistInQueue = false;
    foreach ($comingSongs as $coming)
    {
        $artistInQueue |= ($coming->artist === $song->artist);
    }

    $now = new DateTime();
    $track = DateTime::createFromFormat('Y-m-d H:i:s', $song->date_played);
    $track_available = $track->add(new DateInterval('PT'.TRACK_RULE.'M'));
    $artist = DateTime::createFromFormat('Y-m-d H:i:s', $song->date_artist_played);
    $artist_available = $artist->add(new DateInterval('PT'.ARTIST_RULE.'M'));
    $album = DateTime::createFromFormat('Y-m-d H:i:s', $song->date_album_played);
    $album_available = $album->add(new DateInterval('PT'.ALBUM_RULE.'M'));
    $title = DateTime::createFromFormat('Y-m-d H:i:s', $song->date_title_played);
    $title_available = $title->add(new DateInterval('PT'.TITLE_RULE.'M'));

    return (($now > $track_available) && ($now > $title_available) && ($now > $artist_available) && ($now > $album_available)) && !($artistInQueue);
}