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
  
  /**
   * @var Integer  Comment lines if the code
   */
  protected $commentsLength = 0;
  
  
  /**
   * @var String  The doc comment block of the function is it exists
   */
  public $docComment;
  
  
  /**
   * @var Array The list of PaMethods
   */
  protected $paMethods;
  

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

  public function getCommentsLength()
  {
    return $this->commentsLength;
  }
  
  public function getMethods()
  {
    return $this->paMethods;
  }
  
  public function setDocComment($docComment)
  {
   $this->docComment     = $docComment;
   $this->commentsLength = !empty($docComment) ? count(explode("\n", $docComment)) : 0;
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

 public function getGlobalConfig()
  {
    $config = $this->getParent()->getConfig();
    return $config['global'];
  }

  
  
  /**
   * Check specific alerts.
   *
   * @param String $actions_code
   * @param ReflectionMethod $method
   */
  public function processAlerts()
  {
      $globalConfig = $this->getGlobalConfig();
      $config       = array();
      $paMethods = array();
      
      $this->reflection=new ReflectionClass($this->getName());   
      $this->processAlert4002($globalConfig,$config);
      
      foreach ($this->reflection->getMethods() as $method){
          //Ignore herited method that was not redeclared
          if($this->getName()==$method->class){
              $paMethod=new paMethod($this,$method->getName());
              $paMethod->setDocComment($method->getDocComment());
              $paMethod->process();
              $paMethods[] = $paMethod;
          }
      }
      
      $this->paMethods = $paMethods;
  }
  
 /**
   * @internal See message & help.
   *
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert4002($globalConfig, $config)
  {    
    if ($globalConfig['check_class_docblock'] && $this->commentsLength == 0)
    {
        
      $alert = new paAlert(
        paAlert::ALERT_ACTION_CHECK_DOC_BLOCK,
        paAlert::WARNING,
        sprintf('The class or interface "%s" does not have a docblock !', $this->getName()),
        'Take some time to document the class or the interface'
      );

      $this->addAlert($alert);
    }
  }
}