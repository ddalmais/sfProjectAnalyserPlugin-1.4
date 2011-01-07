<?php

/**
 * Class that represent a symfony component.
 *
 * @author lvernet
 * @since  10/06/10
 */
class paSfComponent extends paAlertable
{
  /**
   * @var paSfModule   Parent module
   */
  protected $module;

  /**
   * @var String Name of the module
   */
  protected $name;

  /**
   * @var String  The doc comment block of the function is it exists
   */
  public $docComment;

  /**
   * @var String  The raw code of function
   */
  public $code;
  
  /**
   * @var Integer  Count of code lines
   */
  protected $codeLength = 0;

  /**
   * @var Integer  Comment lines if the code
   */
  protected $commentsLength = 0;

  /**
   * @var Integer  Count of characters
   */
  protected $characters = 0;
  
  /* GETTERS / SETTERS ********************************************************/

  public function getModule()
  {
    return $this->module;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getCodeLength()
  {
    return $this->codeLength;
  }

  public function getCommentsLength()
  {
    return $this->commentsLength;
  }
  
  public function setDocComment($docComment)
  {
   $this->docComment     = $docComment;
   $this->commentsLength = !empty($docComment) ? count(explode("\n", $docComment)) : 0;
  }

  public function setCode($code)
  {
   $this->code = $code;
  }

  public function getGlobalConfig()
  {
    $config = $this->getModule()->getApplication()->getProject()->getConfig();

    return $config['global'];
  }

  public function getConfig()
  {
    $config = $this->getModule()->getApplication()->getProject()->getConfig();

    return $config['action'];
  }

  public function getTotalCodeLength($with_comments = false)
  {
    return $this->getCodeLength() + ($with_comments ? $this->getCommentsLength() : 0);
  }

  public function getTotalCharacters()
  {
    return $this->characters;
  }
  
  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String $name  Name of the component
   */
  public function __construct(paSfModule $module, $name)
  {
    $this->module = $module;
    $this->name   = $name;
  }

  /**
   * Extract the function code from its class.
   *
   * @param String $actionsCode
   * @param ReflectionMethod $method
   */
  public function extractCode($actionsCode, ReflectionMethod $method)
  {
    $code = explode("\n", $actionsCode);
    $slice = array_slice($code, $method->getStartLine() - 1, $method->getEndline() - $method->getStartLine() + 1);
    $this->code       = implode("\n", $slice);
    $this->characters = strlen(str_replace(array("\n", "\r"), '', $this->code));
    $this->codeLength = count($slice);
  }

  /**
   * Extract modules informations.
   */
  public function process()
  {
    $this->processAlerts();
  }
  
  /**
   * Extract the function code from its class.
   *
   * @param String $actionsCode
   * @param ReflectionMethod $method
   */
  public function processAlerts()
  {
    $globalConfig = $this->getGlobalConfig();
    $config       = $this->getConfig();

    // To call automatically, so the class can be overrided
    $this->processAlert4001($globalConfig, $config);
    $this->processAlert4002($globalConfig, $config);
    $this->processAlert4003($globalConfig, $config);
    $this->processAlert4004($globalConfig, $config);
  }

  /**
   * @internal See message & help.
   *
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert4001($globalConfig, $config)
  {
    if ($config['max_code_length'] && ($this->codeLength > $config['max_code_length']))
    {
      $alert = new paAlert(
        paAlert::ALERT_ACTION_MAX_CODE_LENGTH,
        $this->codeLength < (2 * $config['max_code_length']) ? paAlert::WARNING : paAlert::ALERT,
        sprintf('The action "%s" is too big it\'s %s lines whereas it should be below %s lines.', $this->getName(), $this->codeLength, $config['max_code_length']),
        'Consider refactoring your action (move model code at the model level, use sub-functions, classes...)'
      );

      $this->addAlert($alert);
    }
  }

  /**
   * @internal See message & help.
   *
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert4002($globalConfig, $config)
  {    
    if ($globalConfig['check_functions_docblock'] && $this->commentsLength == 0)
    {
      $alert = new paAlert(
        paAlert::ALERT_ACTION_CHECK_DOC_BLOCK,
        paAlert::WARNING,
        sprintf('The action "%s" does not have a docblock !', $this->getName(), $this->codeLength, $config['max_code_length']),
        'Take some time to document the function'
      );

      $this->addAlert($alert);
    }
  }
  
  /**
   * @internal See message & help.
   *
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert4003($globalConfig, $config)
  {
    if ($globalConfig['check_context_get_instance'] && strstr($this->code, 'sfContext::getInstance()'))
    {
      $help = 'Replace with $this->getContext()';
      $alert = new paAlert(
        paAlert::ALERT_ACTION_CHECK_GET_INSTANCE,
        paAlert::WARNING,
        sprintf('The action "%s" contains a call to "sfContext::getInstance()"', $this->getName()),
        $help
      );

      $this->addAlert($alert);
    }
  }

  /**
   * @internal See message & help.
   *
   * @since V1.0.2 - 22/12/10
   */
  protected function processAlert4004($globalConfig, $config)
  {    
    if ($config['check_other_actions_call'] && strstr($this->code, '$this->execute'))
    {
      $alert = new paAlert(
        paAlert::ALERT_ACTION_CHECK_EXECUTE_CALL,
        paAlert::WARNING,
        sprintf('The action "%s" calls another action through a public executeXXX() function"', $this->getName()),
        'Consider using a forward, a redirect or refactor your code.<br/> (Typically you should put the datas you want to use in both actions in a sub-function)'
      );

      $this->addAlert($alert);
    }
  }
}