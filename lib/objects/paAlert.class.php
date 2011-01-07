<?php

/**
 * Base class for all alerts raised by the analyser.
 *
 * @author lvernet
 * @since  V0.1 beta
 */
class paAlert
{
  /**
   * @see sfLogger
   */
  const ALERT   = 1;  // Immediate action required
  const CRIT    = 2;  // Critical conditions
  const ERR     = 3;  // Error conditions
  const WARNING = 4;  // Warning conditions
  const NOTICE  = 5;  // Normal but significant
  const INFO    = 6;  // Informational

  public static $statusList = array(
    self::ALERT   => 'ALERT',
    self::CRIT    => 'CRIT',
    self::ERR     => 'ERR',
    self::WARNING => 'WARNING',
    self::NOTICE  => 'NOTICE',
    self::INFO    => 'INFO'
  );

  /**
   * Tasks alerts
   */
  const ALERT_TASK                   = -1000;
  const ALERT_TASK_SETTING_NOT_FOUND = -1001;

  /**
   * Project alerts
   */
  const ALERT_PROJECT                = 1000;
  const ALERT_PROJECT_ANALYSIS_OK    = 1001;
  const ALERT_PROJECT_ANALYSIS_NOK   = 1002;
  
  /**
   * Application alerts
   */
  const ALERT_APPLICATION            = 2000;

  /**
   * Module alerts
   */
  const ALERT_MODULE                 = 3000;
  const ALERT_MODULE_PUBLIC_FUNCTION = 3001;
  const ALERT_MODULE_ACTIONS_COUNT   = 3002;

  /**
   * Actions alerts
   */
  const ALERT_ACTION                    = 4000;
  const ALERT_ACTION_MAX_CODE_LENGTH    = 4001;
  const ALERT_ACTION_CHECK_DOC_BLOCK    = 4002;
  const ALERT_ACTION_CHECK_GET_INSTANCE = 4003;
  const ALERT_ACTION_CHECK_EXECUTE_CALL = 4004;

  /**
   * Functions alerts
   */
  const ALERT_FUNCTION                  = 5000;

  /**
   * Template alerts
   */
  const ALERT_TEMPLATE                  = 6000;
  const ALERT_TEMPLATE_MAX_CODE_LENGTH  = 6001;
  const ALERT_TEMPLATE_EMPTY            = 6002;

  /**
   * Partials alerts
   */
  const ALERT_PARTIAL                   = 7000;
  const ALERT_PARTIAL_MAX_CODE_LENGTH   = 7001;
  const ALERT_PARTIAL_EMPTY             = 7002;

  /**
   * Layout alerts
   */
  const ALERT_LAYOUT                    = 8000;
  const ALERT_LAYOUT_MAX_CODE_LENGTH    = 8001;
  const ALERT_LAYOUT_EMPTY              = 8002;

  /**
   * Class alerts
   */
  const ALERT_CLASSES                   = 9000;

  /**
   * Interfaces alerts
   */
  const ALERT_INTERFACES                = 10000;
  
  /**
   * @var Integer   Internal error code
   */
  protected $code;
  
  /**
   * @var Integer
   */
  protected $status;

  /**
   * @var String
   */
  protected $message;

  /**
   * @var String
   */
  protected $help;

  /**
   * @var Boolean
   */
  protected $fatal;
  
  /* GETTERS / SETTERS ********************************************************/

  public function getCode()
  {
    return $this->code;
  }
  
  public function getStatus()
  {
    return $this->status;
  }

  public function getMessage()
  {
    return $this->message;
  }

  public function getHelp()
  {
    return $this->help;
  }

  public function hasHelp()
  {
    return !empty($this->help);
  }
  
  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param Integer $code    Internal code of the alert
   * @param Integer $status  Status of the alert
   * @param String  $message Message
   * @param String  $help    Optional help message
   * @param Boolean $fatal   If the error is fatal or not
   */
  public function __construct($code, $status, $message, $help = '', $fatal = false)
  {
    $this->code    = $code;
    $this->status  = $status;
    $this->message = $message;
    $this->help    = $help;
    $this->fatal   = $fatal;
  }

  /**
   * Return the label of a status.
   * 
   * @param  Integer $status
   * @return String
   */
  public static function getStatusLabel($status)
  {
    return self::$statusList[$status];
  }
}