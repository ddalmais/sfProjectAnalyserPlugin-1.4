<?php

/**
 * Class that represent a symfony partial.
 *
 * @author lvernet
 * @since  V0.9.0 - 30/06/10
 */
class paSfTemplate extends paSfPartial
{
  /**
   * @var String  Name of the status string of the template (success, fail...)
   */
  protected $actionStatus;

  /* GETTERS / SETTERS ********************************************************/

  public function getActionStatus()
  {
    return $this->actionStatus;
  }

  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String $name  Name of the component
   */
  public function __construct(paSfModule $module, $name, $actionStatus)
  {
    $this->actionStatus = $actionStatus;

    parent::__construct($module, $name);
  }

  /**
   * For debug purpose.
   */
  public function __toString()
  {
    return parent::__toString(). ', actionStatus='. $this->actionStatus;
  }

  /**
   * Check alerts.
   *
   * @param String $actions_code
   * @param ReflectionMethod $method
   */
  public function processAlerts()
  {
    $config = $this->getConfig();

    // To call automatically, so the class can be overrided
    $this->processAlert7001($config);
    $this->processAlert7002($config);
  }

  /**
   * Test if the object must be ignored by the analysis.
   *
   * @since V1.0.2 - 01/06/2010
   */
  public function isIgnored()
  {
    $ignoredTemplates = $this->getParent()->getApplication()->getProject()->getIgnoredObjects('template');
    $template = $this->getParent()->getApplication()->getName(). '/'. $this->getParent()->getName(). '/'. $this->getName();

    return in_array($template, $ignoredTemplates);
  }

  /**
   * See message & help.
   * 
   * @todo  Check if you can keep only the parent function
   * @see paAlert
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert7001($config)
  {    
    if ($config['max_code_length'] &&
       ($this->codeLength > $config['max_code_length']) &&
       !$this->isIgnored()
    )
    {
      $alert = new paAlert(
        paAlert::ALERT_TEMPLATE_MAX_CODE_LENGTH,
        $this->codeLength < (2 * $config['max_code_length']) ? paAlert::WARNING : paAlert::ALERT,
        sprintf('The template "%s" is too big it\'s %s lines whereas it should be below %s lines.', $this->getName(), $this->codeLength, $config['max_code_length']),
        'Consider refactoring your template (use partials, helpers, custom helpers, move complex logic at the action level...)'
      );

      $this->addAlert($alert);
    }
  }

  /**
   * See message & help.
   *
   * @todo Check if you can keep only the parent function
   * @see  paAlert
   * @since V0.9.0 - 01/07/10
   */
  protected function processAlert7002($config)
  {
    if ($config['check_empty'] && 
        $this->codeLength == 0 &&
        !$this->isIgnored()
    )
    {
      $alert = new paAlert(
        paAlert::ALERT_TEMPLATE_EMPTY,
        paAlert::WARNING,
        sprintf('The template "%s" is empty', $this->getName()),
        'Remove it if not used'
      );

      $this->addAlert($alert);
    }
  }
}