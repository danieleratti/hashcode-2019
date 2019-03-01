<?php

/*
 * Sorry, this is not a fancy code but is the fastest approach to resolve Google HashCode :-)
 * This solution is suitable for every input
*/

$INPUT = 3; // 3 and 4

require __DIR__ . '/common.php';

/*
 * take a photo: unset it and unset the photo ID from the TagList
*/
function getPhotosWithTags($tags) {
    global $PHOTOS, $TAG2PHOTOS;
    
    $idPossiblePhotos = [];
    $returnPhotos = [];
    $tagsInCommon2photoIds = [];
    
    foreach($tags as $tag) {
        foreach($TAG2PHOTOS[$tag] as $photoId) {
            if(!isset($idPossiblePhotos[$photoId]))
                $idPossiblePhotos[$photoId] = 1;
            else
                $idPossiblePhotos[$photoId]++;
        }
    }
    
    /* searching for the highest tags number and collecting a sample of photos for each record beaten */
    $highestTagsNumber = 0;
    foreach($idPossiblePhotos as $photoId => $num) {
        if($num > $highestTagsNumber-1) { // Don't ask why -1. It works. Just taking more pictures over the diagonal to increase the likelihood of a good score
            $photo = $PHOTOS[$photoId];
            $photo['tags_in_common'] = $num;
            $returnPhotos[] = $photo;
            $highestTagsNumber = $num;
        } else { // We are interested to add to the $tagsInCommon2photoIds only the photos not yet added!
            if(!isset($tagsInCommon2photoIds[$num]))
                $tagsInCommon2photoIds[$num] = [$photoId];
            else
                $tagsInCommon2photoIds[$num][] = $photoId;
        }
    }
    
    /* adding N photos found halfway between "1 tag in common" and "highestTagsNumber tags in common" to increase the likelihood of a good score */
    $k = 0.5; // Tunable from 0+ to 1-
    $addablePhotos = 100; // Tunable based on the speed of execution. Often higher is better
    
    if(count($tagsInCommon2photoIds) > 0) {
        foreach($tagsInCommon2photoIds as $num => $photoIds) {
            if($num >= $highestTagsNumber*$k) {
                foreach($photoIds as $photoId) {
                    $returnPhotos[] = $PHOTOS[$photoId];
                    $addablePhotos--;
                    if($addablePhotos <= 0)
                        break 2;
                }
            }
        }
    }
    
    return $returnPhotos;
}

/* choosing a seed to start with */

$totalScore = 0;
$result = [];

foreach($PHOTOS as $seed => $photo) if($photo['orientation'] == 'H') break; // Take the first horizontal photo (or the last vertical photo)

if($photo['orientation'] == 'H') { // There is at least one horizontal photo
    $currentSlide = [$PHOTOS[$seed]];
    takePhoto($seed);
    $result = [$seed];
}
else { // There are only vertical photos
    $currentSlide = [$PHOTOS[0], $PHOTOS[1]];
    takePhoto(0);
    takePhoto(1);
    $result = ['0 1'];
}

while (count($PHOTOS)) {
    $maxPoints = -1;
    $selectedSlide = null;
    $possibleSlides = [];
    
    $possiblePhotos = getPhotosWithTags(getSlideTags($currentSlide));
    
    /* calculate all the possible combinations of slides */
    for($start=0;$start<count($possiblePhotos)-1;$start++) {
        if($possiblePhotos[$start]['orientation'] == 'H') {
            $possibleSlides[] = [$possiblePhotos[$start]];
        }
        else {
            foreach ($possiblePhotos as $pos => $photo)
                if ($v['orientation'] == 'V' && $pos >= $start + 1)
                    $possibleSlides[] = [$possiblePhotos[$start], $photo];
        }
    }

    foreach ($possibleSlides as $slide) {
        $points = calculatePoints($slide, $currentSlide);
        if ($points > $maxPoints) {
            $maxPoints = $points;
            $selectedSlide = $slide;
        }
    }
    
    if(!$selectedSlide) { // No slide found -> Fallback: select the first photo(s) available
        foreach($PHOTOS as $seed => $photo) if($photo['orientation'] == 'H') break;
        if($photo['orientation'] == 'H') { // Horizontal photo found
            $selectedSlide = [$PHOTOS[$seed]];
            $points = calculatePoints($selectedSlide, $currentSlide);
            $maxPoints = $points;
        }
        else { // Horizontal photo not found -> only vertical photos available
            foreach($PHOTOS as $seed => $photo) if($photo['orientation'] == 'V') break;
            $selectedSlide = [$photo];
            foreach($PHOTOS as $seed => $photo) if($photo['orientation'] == 'V' && $photo['id'] != $selectedSlide[0]['id']) break;
            $selectedSlide[] = $photo;
            $points = calculatePoints($selectedSlide, $currentSlide);
            $maxPoints = $points;
            if(count($selectedSlide) != 2) // Exactly 2 vertical photos per slide
                $selectedSlide = null;
        }
    }
    
    if($selectedSlide) {
        $totalScore += $maxPoints;
        $currentSlide = $selectedSlide;

        if(count($selectedSlide) == 1) {
            $result[] = $selectedSlide[0]['id'];
            takePhoto($selectedSlide[0]['id']);
        } else {
            $result[] = $selectedSlide[0]['id'] . ' ' . $selectedSlide[1]['id'];
            takePhoto($selectedSlide[0]['id']);
            takePhoto($selectedSlide[1]['id']);
        }
        
        echo "TotalScore $totalScore ; Remaining photos: " . count($PHOTOS) . " ; Last Score " . $maxPoints . "\n";
    }
}

save();
