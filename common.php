<?php

/*
 * Sorry, this is not a fancy code but is the fastest approach to resolve Google HashCode :-)
*/

ini_set('memory_limit', -1);

/* Functions */

/*
 * get the slide tags as array
*/
function getSlideTags($slide) {
    $tags = $slide[0]['tags'];
    if($slide[1])
        $tags = array_unique(array_merge($tags, $slide[1]['tags']));
    return $tags;
}

/*
 * calculate the points of a transition between two slides
 * slides are array of 1 horizontal photo element or 2 vertical photo elements
*/
function calculatePoints($slide1, $slide2) {
    $tags1 = getSlideTags($slide1);
    $tags2 = getSlideTags($slide2);
    $int = array_intersect($tags1, $tags2);
    $diff1 = array_diff($tags1, $tags2);
    $diff2 = array_diff($tags2, $tags1);
    return min(count($int), count($diff1), count($diff2));
}

/*
 * take a photo: unset it and unset the photo ID from the TagList
*/
function takePhoto($photoId) {
    global $PHOTOS, $TAG2PHOTOS;
    $photo = $PHOTOS[$photoId];
    foreach($photo['tags'] as $tag) {
        $idx = array_search($photoId, $TAG2PHOTOS[$tag]);
        array_splice($TAG2PHOTOS[$tag], $idx, 1);
    }
    unset($PHOTOS[$photoId]);
}

/* Input reading */
$INPUTS = ['a_example.txt', 'b_lovely_landscapes.txt', 'c_memorable_moments.txt', 'd_pet_pictures.txt', 'e_shiny_selfies.txt'];
$nPhotos = null; //n° righe
$PHOTOS = []; //n° colonne
$TAG2PHOTOS = [];

/* Parsing */
$f = file_get_contents(__DIR__ . '/input/' . $INPUTS[$INPUT]);
$f = explode("\n", $f);

$nPhotos = (int)$f[0];

for($i=1;$i<=$nPhotos;$i++) {
    $photo = explode(' ', $f[$i]);
    
    $id = ($i-1);
    $orientation = $photo[0];
    $tags = array_splice($photo, 2);
    $tags_assoc = [];
    
    foreach($tags as $tag) {
        $tags_assoc[$tag] = true;
        $TAG2PHOTOS[$tag][] = $id;
    }
    
    $photo = [
        'id' => $id,
        'orientation' => $orientation,
        'n_tags' => count($tags),
        'tags' => $tags,
        'tags_assoc' => $tags_assoc,
    ];
    
    $PHOTOS[$id] = $photo;
}

/*
 * Save the solution to file
*/
function save()
{
    global $result, $INPUT;
    $out = fopen(__DIR__ . "/output/$INPUT.txt", 'w');
    fwrite($out, count($result) . "\n");
    fwrite($out, implode("\n", $result) . "\n");
    fclose($out);
}

/*
 * Here we have the $PHOTOS array containing all the available photos and the $TAG2PHOTOS array containing all the possible tags with an array of photo IDs for each tag
*/
