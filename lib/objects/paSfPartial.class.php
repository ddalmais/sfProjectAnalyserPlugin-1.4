<?php

/**
 * Class that represents a symfony partial.
 *
 * @author lvernet
 * @since  V0.9.0 - 30/06/10
 */
class paSfPartial extends paAlertable
{
  /**
   * @var paSfModule | paSfApplication   Parent module or application
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
    return $this->getModule();
  }
  
  public function getModule()
  {
    return $this->parent;
  }
  
  public function getApplication()
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

  public function getTemplateCodeLength($with_comments = false)
  {
    return $this->getCodeLength();
  }
  
  /**
   * Get the config section related to the model object.
   * 
   * @return Array
   */
  public function getConfig()
  {
    $config = $this->getParent()->getApplication()->getProject()->getConfig();
    
    return $config[$this->getYamlSection()];
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
      'name='. $this->getName(). ', '.
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
    $config = $this->getConfig();
    $this->processAlert6001($config);
    $this->processAlert6002($config);
  }

  /**
   * Test if the object must be ignored by the analysis.
   *
   * @since V1.0.2 - 01/06/2010
   */
  public function isIgnored()
  {
    $ignoredPartials = $this->getParent()->getApplication()->getProject()->getIgnoredObjects('partial');
    $partial = $this->getParent()->getApplication()->getName(). '/'. $this->getParent()->getName(). '/'. $this->getName();

    return in_array($partial, $ignoredPartials);
  }

  /**
   * See message & help.
   *
   * @todo  Check if you can keep only the parent function
   * @see   paAlert
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert6001($config)
  {
    if ($this->isAlert6001($config))
    {
      $alert = new paAlert(
        paAlert::ALERT_PARTIAL_MAX_CODE_LENGTH,
        $this->codeLength < (2 * $config['max_code_length']) ? paAlert::WARNING : paAlert::ALERT,
        sprintf('The partial "%s" is too big it\'s %s lines whereas it should be below %s lines.', $this->getName(), $this->codeLength, $config['max_code_length']),
        'Consider refactoring your partial (use sub-partials, helpers, custom helpers, move complex logic at the action level...)'
      );

      $this->addAlert($alert);
    }
  }

  /**
   * Return if the code has the condition to raise the alert.
   *
   * @since V1.0.2 - 6/01/2010
   */
  protected function isAlert6001($config)
  {
    return 
       $config['max_code_length'] &&                         // Alert activated
      ($this->codeLength > $config['max_code_length']) &&    // Length beyond the threshold
      !$this->isIgnored()                                    // Is not in the ignore list
    ;
  }
  
  /**
   * See message & help.
   *
   * @see  paAlert
   * @since V0.9.0 - 01/07/10
   */
  protected function processAlert6002($config)
  {
    if ($config['check_empty'] &&      // Alert activated
        ($this->codeLength == 0) &&    // Length beyond the threshold
        !$this->isIgnored()            // Is not in the ignore list
    )
    {
      $alert = new paAlert(
        paAlert::ALERT_PARTIAL_EMPTY,
        paAlert::WARNING,
        sprintf('The partial "%s" is empty', $this->getName()),
        'Remove it if it is not used'
      );

      $this->addAlert($alert);
    }
  }
}