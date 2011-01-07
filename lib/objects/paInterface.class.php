<?php

/**
 * Class that represent a class or interface object.
 *
 * @author lvernet
 * @since  V1.0.0 - 16/07/10
 */
class paInterface extends paAlertable
{
  /**
   * @var Parent object
   */
  protected $parent;

  /**
   * @var String Name of the partial/template
   */
  protected $name;

  /**
   * @var Integer  Count of code lines
   */
  protected $codeLength = 0;

  /* GETTERS / SETTERS ********************************************************/

  public function getParent()
  {
    return $this->parent;
  }

  public function getName()
  {
    return $this->name;
  }

  public function setCodeLength($codeLength)
  {
    $this->codeLength = $codeLength;
  }

  public function getCodeLength()
  {
    return $this->codeLength;
  }

  public function getTotalCodeLength()
  {
    return $this->getCodeLength();
  }

  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String $name  Name of the component
   */
  public function __construct($parent, $name)
  {
    $this->parent = $parent;
    $this->name   = $name;
  }

  /**
   * Debug object.
   *
   * @return String
   */
  public function __toString()
  {
    return
      'parent ('. get_class($this). ')='. $this->parent->getName(). ', '.
      'name='.        $this->getName(). ', '.
      'codeLength='. $this->getCodeLength()
    ;
  }

  /**
   * Process partial informations.
   */
  public function process()
  {
    $this->processAlerts();
  }

  /**
   * Check specific alerts.
   *
   * @param String $actions_code
   * @param ReflectionMethod $method
   */
  public function processAlerts()
  {
  }
}