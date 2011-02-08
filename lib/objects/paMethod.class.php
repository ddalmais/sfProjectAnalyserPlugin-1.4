<?php

/**
 * Class that represent a php method.
 *
 * @author lvernet
 * @since  10/06/10
 */
class paMethod extends paAlertable
{
  /**
   * @var paInterface $paInterface
   */
  protected $paInterface;

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
  
  /**
   * @var ReflectionMethod the method
   */
  protected $method;
  
  /* GETTERS / SETTERS ********************************************************/

  public function getPaInterface()
  {
    return $this->paInterface;
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
    $config = $this->getPaInterface()->getParent()->getConfig();

    return $config['global'];
  }

  public function getConfig()
  {
    $config = $this->getPaInterface()->getParent()->getConfig();

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
  public function __construct(paInterface $paInterface, $name)
  {
    $this->paInterface = $paInterface;
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
    $this->processAlert4002($globalConfig, $config);
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

}