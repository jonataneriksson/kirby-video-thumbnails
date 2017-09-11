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
      'thumbs.video.driver'=> 'ffmpeg'
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
    $params['overwrite'] = true;
    //die(var_dump($params));

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
