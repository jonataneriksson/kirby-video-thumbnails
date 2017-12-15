<?php

/**
 * Thumb
 *
 * @package   Kirby Toolkit
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      http://getkirby.com
 * @copyright Bastian Allgeier
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

    if($this->options['still']) { //

      $destination = new Obj();
      $safeName    = f::safeName($this->source->name());

      if($this->options['width']) {
        $this->options['height'] = $this->options['height'] ? $this->options['height'] : intval($this->options['width'] / $this->source()->dimensions()->ratio()); // *
      }

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

  /**
   * Generates and returns the full html tag for the thumbnail
   *
   * @param array $attr An optional array of attributes, which should be added to the image tag
   * @return string
   */
  public function tag($attr = array()) {

    // don't return the tag if the url is not available
    if(!$this->result->url()) return false;

    return html::img($this->result->url(), array_merge(array(
      'alt'    => isset($this->options['alt'])   ? $this->options['alt']   : ' ',
      'class'  => isset($this->options['class']) ? $this->options['class'] : null,
    ), $attr));

  }

  /**
   * Makes it possible to echo the entire object
   */
  public function __toString() {
    return $this->tag();
  }

}
