<?php

/**
* Kirby Thumb Render and API Component
*
* @author    Jonatan Eriksson
*/

namespace Kirby\Component;

use A;
use F;
use Obj;
use R;
use File;
use Redirect;
use Str;
use Media;
use Asset;
use VideoThumb as VideoGenerator;
use Thumb as Generator;
use Kirby\Component;

class VideoThumb extends Thumb {

  public function defaults() {
    return array_merge(parent::defaults(), [
      'thumbs.video.bin'   => 'ffmpeg',
      'thumbs.video.probe'=> 'ffprobe',
      'thumbs.video.driver'=> 'ffmpeg',
    ]);
  }

  public function __debugInfo() { return array(); }

  public function configure() {
    parent::configure();
    generator::$defaults['video.driver']    = $this->kirby->option('thumbs.video.driver');
    generator::$defaults['video.bin']      = $this->kirby->option('thumbs.video.bin');
    generator::$defaults['video.probe']     = $this->kirby->option('thumbs.video.probe');
  }

  /*public function __construct($source, $params = array()) {
    parent::__construct($source, $params = array());
  }*/

  public function create($file, $params) {
    switch ($file->type()):
      case 'video':
        return $this->createvideo($file, $params);
      break;
      case 'image':
        return parent::create($file, $params);
      break;
    endswitch;
  }

  /**
   * @param Generator $thumb
   * @return string
   */

  protected function dimensions(Generator $thumb) {
    switch ($thumb->type()):
      case 'video':
        return $this->videodimensions($thumb);
      break;
      case 'image':
        return parent::dimensions($thumb);
      break;
    endswitch;
  }

  /**
   * @param Generator $thumb
   * @return string
   */

  protected function videodimensions(VideoGenerator $thumb) {
    $dimensions = clone $thumb->source->dimensions();
    $videometa = $this->videometa($thumb->source);
    $dimensions->width = $videometa['width'];
    $dimensions->height = $videometa['height'];
    if($dimensions->height > 0){
      $dimensions->ratio = intval($videometa['width'] / $videometa['height']*100)/100;
      $dimensions->orientation = $dimensions->ratio > 1 ? 'landscape' : 'portrait';
    }
    if(isset($thumb->options['crop']) && $thumb->options['crop']) {
      $dimensions->crop(a::get($thumb->options, 'width'), a::get($thumb->options, 'height'));
    } else {
      $dimensions->resize(a::get($thumb->options, 'width'), a::get($thumb->options, 'height'), a::get($thumb->options, 'upscale'));
    }
    return $dimensions;
  }

  /**
   * @param Generator $thumb
   * @return string
   */

  public function videometa($file) {

    if(!isset($this->metadata)){
      //Fet info with ffprobe
      $ffprobe = $this->kirby->option('thumbs.video.ffprobe') ? $this->kirby->option('thumbs.video.ffprobe') : 'ffprobe';
      $probe = $ffprobe.' -v quiet -print_format json -show_format -show_entries stream=width,height "'.$file->root().'"  2>&1';
      $ffprobeinfo = json_decode(shell_exec($probe), true);

      //Sort object
      $metadata = [];
      $metadata['width'] = $ffprobeinfo['streams'][0]['width'];
      $metadata['height'] = $ffprobeinfo['streams'][0]['height'];
      $metadata['duration'] = $ffprobeinfo['format']['duration'];
      $this->metadata = $metadata;
      return $this->metadata;
    } else {
      return $this->metadata;
    }

  }

  /**
   * @param Generator $thumb
   * @return string
   */


  public function createvideo($file, $params) {

    /*if(!$file->isWebsafe()) {
      return $file;
    }*/

    // load a thumb preset
    $presets = $this->kirby->option('thumbs.presets');

    if(is_string($params)) {
      if(isset($presets[$params])) {
        $params = $presets[$params];
      } else {
        throw new Error('Invalid thumb preset ' . $params);
      }
    } else if($params === []) {
      // try to load the default preset otherwise use the thumb defaults from the Toolkit
      if(isset($presets['default'])) {
        $params = $presets['default'];
      }
    }

    //Patch duration to parameters
    $params['driver'] = $this->kirby->option('thumbs.video.driver');
    $params['thumbs.video.bin'] = $this->kirby->option('thumbs.video.bin');
    //$params['overwrite'] = true;

    //Patch some dimensions
    $file->dimensions()->width = $this->videometa($file)['width'];
    $file->dimensions()->height = $this->videometa($file)['height'];
    $file->video = $this->videometa($file);

    // generate the thumb
    $thumb = new VideoGenerator($file, $params);
    $asset = new Asset($thumb->result);

    // store a reference to the original file
    $asset->original($file);

    $return = $thumb->exists() ? $asset : $file;

    return $return;
  }


}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
/* ! */
/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
