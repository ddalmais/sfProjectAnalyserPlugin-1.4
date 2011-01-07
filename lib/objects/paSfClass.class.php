<?php

/**
 * Class that represents a symfony extended class.
 *
 * @author lvernet
 * @since  V1.0.0 - 16/07/10
 */
class paSfClass extends paClass
{
  /**
   * @var String  The symfony class that the class extends.
   */
  protected $parentClass;

  /* GETTERS / SETTERS ********************************************************/

  public function getParentClass()
  {
    return $this->parentClass;
  }

  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String $name  Name of the component
   */
  public function __construct($parent, $name, $parentClass)
  {
    parent::__construct($parent, $name);

    $this->parentClass = $parentClass;
  }
}