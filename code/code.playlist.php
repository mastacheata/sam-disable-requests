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