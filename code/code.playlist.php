<?php

// The class handling song info
include_once('classes/class.song.php');

if (ALLOW_REQUESTS) {
    // An array of song objects with the top requested songs
    $topRequestedSongs = Song::getTopRequestedSongs();

    if (REQUESTLIST_RULE)
    {
        // An array of ids that are currently in the Requestlist of SAM
        // May also contain crc32-hashed artist/album names prefixed by artist_/album_
        $requestlist = Song::getRequestedSongs(REQUESTLIST_ARTIST_RULE | REQUESTLIST_ALBUM_RULE);
    }
    else
    {
        $requestlist = array();
    }

    if (QUEUE_RULE > 0)
    {
        $comingSongs = Song::getComingSongs(QUEUE_RULE);
    }
    else
    {
        $comingSongs = array();
    }
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

function requestable($song)
{
    global $comingSongs, $requestlist;

    $songInQueue = false;
    $artistInQueue = false;
    $albumInQueue = false;
    foreach ($comingSongs as $coming)
    {
        $songInQueue |= ($coming->title === $song->title);
        $artistInQueue |= ($coming->artist === $song->artist);
        $albumInQueue |= ($coming->album === $song->album);
    }

    $songInRequestlist = false;
    $artistInRequestlist = false;
    $albumInRequestlist = false;
    if (!empty($requestlist))
    {
        $songInRequestlist = array_key_exists($song->ID, $requestlist);
        $artistInRequestlist = array_key_exists('artist_'.hash('crc32b', $song->artist), $requestlist);
        $albumInRequestlist = array_key_exists('album_'.hash('crc32b', $song->album), $requestlist);
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

    $timeRule = (($now > $track_available) && ($now > $title_available) && ($now > $artist_available) && ($now > $album_available));
    if (!$timeRule)
    {
        $available_at = max($track_available, $artist_available, $album_available, $title_available);
        $song->available = ' Try again in '.$available_at->diff(new DateTime('now'))->format('%H:%I:%S');
    }

    $songInRequestlist = $songInRequestlist && REQUESTLIST_RULE;
    if ($songInRequestlist)
    {
        $song->available = ' Requested track already in list';
    }

    $artistInRequestlist = $artistInRequestlist && REQUESTLIST_ARTIST_RULE;
    if ($artistInRequestlist)
    {
        $song->available = ' Requested artist already in list';
    }

    $albumInRequestlist = $albumInRequestlist && REQUESTLIST_ALBUM_RULE;
    if ($albumInRequestlist)
    {
        $song->available = ' Requested album already in list';
    }

    $songInQueue = $songInQueue && QUEUE_RULE;
    if ($songInQueue)
    {
        $song->available = 'Requested track already in queue';
    }

    $artistInQueue = $artistInQueue && QUEUE_ARTIST_RULE;
    if ($songInQueue)
    {
        $song->available = ' Requested artist already in queue';
    }

    $albumInQueue = $albumInQueue && QUEUE_ALBUM_RULE;
    if ($albumInQueue)
    {
        $song->available = ' Requested album already in queue';
    }

    return $timeRule && !($songInQueue) && !($artistInQueue) && !($albumInQueue) && !($songInRequestlist) && !($artistInRequestlist) && !($albumInRequestlist);
}
