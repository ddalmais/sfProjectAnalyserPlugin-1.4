<?php

/**
 * Class that represent a symfony layout.
 *
 * @author lvernet
 * @since  V1.0.0 - 5/06/10
 */
class paSfLayout extends paSfPartial
{
  /* GETTERS / SETTERS ********************************************************/
  
  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String $name  Name of the component
   */
  public function __construct(paSfApplication $application, $name)
  {
    $this->parent = $application;
    $this->name   = $name;
  }

  /**
   * Check alerts.
   *
   * @todo  Check if you can keep only the parent function
   *
   * @param String $actions_code
   * @param ReflectionMethod $method
   */
  public function processAlerts()
  {
    $config = $this->getConfig();

    // To call automatically, so the class can be overrided
    $this->processAlert8001($config);
    $this->processAlert8002($config);
  }

  /**
   * Test if the object must be ignored by the analysis.
   *
   * @since V1.0.2 - 01/06/2010
   */
  public function isIgnored()
  {
    $ignoredLayouts = $this->getParent()->getApplication()->getProject()->getIgnoredObjects('layout');
    $layout = $this->getParent()->getApplication()->getName(). '/'. $this->getName();

    return in_array($layout, $ignoredLayouts);
  }

  /**
   * See message & help.
   *
   * @todo  Check if you can keep only the parent function
   * @see   paAlert
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert8001($config)
  {    
    if ($config['max_code_length'] && 
       ($this->codeLength > $config['max_code_length']) &&
        !$this->isIgnored()
    )
    {
      $alert = new paAlert(
        paAlert::ALERT_TEMPLATE_MAX_CODE_LENGTH,
        $this->codeLength < (2 * $config['max_code_length']) ? paAlert::WARNING : paAlert::ALERT,
        sprintf('The layout "%s" is too big it\'s %s lines whereas it should be below %s lines.', $this->getName(), $this->codeLength, $config['max_code_length']),
        'Consider refactoring your layout (use partials, helpers, custom helpers, slots...)'
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
  protected function processAlert8002($config)
  {
    if ($config['check_empty'] && 
        $this->codeLength == 0 &&
        !$this->isIgnored()
    )
    {
      $alert = new paAlert(
        paAlert::ALERT_TEMPLATE_EMPTY,
        paAlert::WARNING,
        sprintf('The layout "%s" is empty', $this->getName()),
        'Remove it if not used, use $this->setLayout(false) for actions that don\'t need a layout'
      );

      $this->addAlert($alert);
    }
  }
}