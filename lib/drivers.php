<?php

/**
* Modified ImageMagick Driver
*/
thumb::$drivers['ffmpeg'] = function($thumb) {

  $command = array();

  $ffmpeg = isset($thumb->options['thumbs.video.bin']) ? $thumb->options['thumbs.video.bin'] : 'ffmpeg';

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Run ffmpeg without interruptions */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if($thumb->source->type() == 'video'):
    $command[] = $ffmpeg.' -y';
    //$crfratio = 21;
    //$command[] = '-c:v libx264';
    //$command[] = '-tune grain';
    //$command[] = '-preset fast';
    //$command[] = '-crf '.$crfratio;
  endif;

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Images and clips from the middle */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if(isset($thumb->options['clip']) && isset($thumb->options['clipstart'])) {
    //$duration = $thumb->options['duration'];
    //$clipstart = $thumb->options['clipstart'];
    //$playroom = $duration - $clipstart;
    //$cliptime = ($playroom > 8) ? 8 : $playroom;
    //$command[] = '-t '.$cliptime;
    //$command[] = '-ss '.$thumb->options['clipstart'];
  } else if(isset($thumb->options['clip'])) {
    //$duration = $thumb->options['duration'];
    //$start = ($thumb->options['duration'] > 10) ? round($duration/2)-4 : 2;
    $command[] = '-ss 2'; //$start;
    $command[] = '-t 6'; //duration
  } else {
    $command[] = '-ss 0';
  }

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Source after seeking */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if($thumb->source->type() == 'video'):
    $command[] = '-i "' . $thumb->source->root() . '"';
  endif;

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* ! */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if($thumb->source->type() == 'video' && !isset($thumb->options['still'])):
    $command[] = '-movflags +faststart';
  endif;

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Resizing */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if(isset($thumb->options['width']) && isset($thumb->options['height'])) {
    $command[] = '-vf scale="'.$thumb->options['width'].':'.$thumb->options['height'].'"';
  } else if (isset($thumb->options['height'])) {
    $command[] = '-vf scale=-2:'.$thumb->options['height'];
  } else if (isset($thumb->options['width'])) {
    $command[] = '-vf scale='.$thumb->options['width'].':-2';
  }

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Quality */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if(isset($thumb->options['silent'])) {
    $command[] = '-an';
  }

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Quality */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if(!isset($thumb->options['still'])) {
    //$invertedratio = (100-$thumb->options['quality'])/100;
    //$crfratio = intval(51 * $invertedratio);
    $crfratio = 21;
    $command[] = '-c:v libx264';
    $command[] = '-tune grain';
    $command[] = '-preset fast';
    $command[] = '-crf '.$crfratio;
  } else {
    $crfratio = 2;
    $command[] = '-qscale '.$crfratio;
    //isset($thumb->options['quality']) &&
    //$command[] = '-c:v libx264';
    //$command[] = '-tune grain';
    //$command[] = '-preset fast';
    //$command[] = '-crf '.$crfratio;
  }

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Output (2>&1 for independent)*/
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  if(isset($thumb->options['still'])) {
    $command[] = '-frames:v 1';
    $command[] = '-f mjpeg';
    $root = pathinfo($thumb->destination->root);
    $command[] = '"' .$root['dirname'].DS.$root['filename'].'.jpg"';
  } else {
    $command[] = '"' . $thumb->destination->root . '"';
  }

  $command[] = '2>&1';

  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
  /* !Exec */
  /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

  $execstring = implode(' ', $command);

  //die($execstring);
  exec($execstring);

};
