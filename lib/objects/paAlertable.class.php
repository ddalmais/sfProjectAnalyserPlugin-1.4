<?php

/**
 * Interface to munipulate the alerts related to an object.
 *
 * @author lvernet
 */
class paAlertable
{
  /**
   * @var Array   Array of paAlert
   */
  protected $alerts = array();

  /**
   * Return all the alerts or for a given status.
   * 
   * @param Boolean $status
   * @return Array
   */
  public function getAlerts($status = null)
  {
    if (is_null($status))
    {
      return $this->alerts;
    }

    $alerts = array();

    foreach ($this->alerts as $alert)
    {
      if ($alert->getStatus() == $status)
      {
        $alerts[] = $alert;
      }
    }

    return $alerts;
  }

  /**
   * Push an alert on the alert stack.
   * 
   * @param paAlert $alert
   */
  public function addAlert(paAlert $alert)
  {
    $this->alerts[] = $alert;
  }

  /**
   * Count the number of alerts for the object itself. (does not take care
   * of child) So this function has to be re-implemented in all classes
   * that extend this one.
   *
   * @param Integer $status 
   */
  public function countAlerts($status = null)
  {
    return count($this->getAlerts($status));
  }

  /**
   * Test existence of alerts with a given status.
   *
   * @param Integer $status
   */
  public function hasAlerts($status = null)
  {
    return $this->countAlerts($status) > 0;
  }

  /**
   * Return the section key name of the yaml config file used by the object.
   *
   * @param Boolean $strtolower
   */
  public function getYamlSection($strtolower = true)
  {
    $key = explode('paSf', get_class($this));
    
    return $strtolower ? strtolower($key[1]) : $key[1];
  }
}