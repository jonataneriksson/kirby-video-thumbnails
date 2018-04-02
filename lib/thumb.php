<?php

/**
 * VideoThumb
 *
 * @author    Jonatan Eriksson <jonatan@tsto.org>
 * @link      http://tsto.org
 * @copyright Jonatan Eriksson
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
class VideoThumb extends Thumb {

  /**
   * Constructor
   *
   * @param mixed $source
   * @param array $params
   */

  public function __construct($source, $params = array()) {

    $this->source      = $this->result = is_a($source, 'Media') ? $source : new Media($source);
    $this->options     = array_merge(static::$defaults, $this->params($params));
    $this->destination = $this->destination();

    // don't create the thumbnail if it's not necessary
    if($this->isObsolete()) return;

    // don't create the thumbnail if it exists
    if(!$this->isThere()) {

      // try to create the thumb folder if it is not there yet
      dir::make(dirname($this->destination->root));

      // check for a valid image
      if(!$this->source->exists() || $this->source->type() != 'video') {
        throw new Error('The given image is invalid', static::ERROR_INVALID_IMAGE);
      }

      // check for a valid driver
      if(!array_key_exists($this->options['driver'], static::$drivers)) {
        throw new Error('Invalid thumbnail driver', static::ERROR_INVALID_DRIVER);
      }

      // create the thumbnail
      $this->create();

      // check if creating the thumbnail failed
      if(!file_exists($this->destination->root)) return;
    }

    // create the result object
    $this->result = new Media($this->destination->root, $this->destination->url);

  }

  /**
   * Calls the driver function and
   * creates the thumbnail
   */

  protected function create() {
    return call_user_func_array(parent::$drivers[$this->options['driver']], array($this));
  }

  /**
   * Filename and destination
   *
   */

  public function destination() {

    //Return an image if the 'still' option is on.
    if($this->options['still']) {

      $destination = new Obj();
      $safeName    = f::safeName($this->source->name());

      //Video width
      if($this->options['width']) {
        $this->options['height'] = $this->options['height'] ? $this->options['height'] : intval($this->options['width'] / $this->source()->dimensions()->ratio()); // *
      }

      //Video height
      if($this->options['height']) {
        $this->options['width'] = $this->options['width'] ? $this->options['width'] : intval($this->options['height'] * $this->source()->dimensions()->ratio());
      }

      //Create filename
      $destination->filename = str::template('{safeName}-{width}x{height}.{extension}', array(
        'extension'    => 'jpg', //TODO this could be in the defaults
        'safeName'     => $safeName,
        'width'        => $this->options['width'],
        'height'       => $this->options['height'],
      ));

      //Setup the stuff
      $destination->url  = $this->options['url'] . '/' . $this->source()->page . '/' . $destination->filename;
      $destination->root = $this->options['root'] . DS . $this->source()->page . DS . $destination->filename;

      return $destination;
    } else {
      return parent::destination();
    }

  }

  //Direct to the original isObsolete if thumbnail is an image.
  public function isObsolete() {
    if($this->options['overwrite'] === true) return false;

    //Use the image obsolete if this is an image
    switch ($this->type()):
      case 'video':
        return false;
      break;
      case 'image':
        return parent::isObsolete();
      break;
    endswitch;

    return false;
  }

}
