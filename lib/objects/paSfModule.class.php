<?php

/**
 * Class that represent a symfony module.
 *
 * @author lvernet
 * @since  10/06/10
 */
class paSfModule extends paAlertable
{
  /**
   * @var paSfApplication  Parent application
   */
  protected $application;

  /**
   * @var String Name of the module
   */
  protected $name;

  /**
   * @var String  Raw code of all actions
   */
  protected $actionsCode;

  /**
   * @var Array  List of module main actions
   */
  protected $actions = array();

  /**
   * @var Array  List of action templates
   */
  protected $templates = array();

  /**
   * @var Array  List of partials of the module
   */
  protected $partials = array();

  /**
   * @var Integer  Count of code lines
   */
  protected $countLines;

  /**
   * @var Boolean  If the module is an autogenerated one
   */
  protected $isAutogenerated = false;

  /* GETTERS / SETTERS ********************************************************/

  public function getApplication()
  {
    return $this->application;
  }
  
  public function getName()
  {
    return $this->name;
  }

  /**
   * Retrieve the application modules list.
   *
   * @return Array  Array of paSfModule
   */
  public function getActions()
  {
    return $this->actions;
  }

  /**
   * Retrieve the actions list
   *
   * @return Array  Array of actions
   */
  public function getActionsList()
  {
    $actions = array();

    foreach ($this->actions as $action)
    {
      $actions[] = $action->getName();
    }

    return $actions;
  }

  /**
   * Get the module configuration.
   * 
   * @return Array
   */
  public function getConfig()
  {
    $config = $this->getApplication()->getProject()->getConfig();

    return $config['module'];
  }

  /**
   * Retrieve the templates list.
   *
   * @return Array
   */
  public function getTemplates()
  {
    return $this->templates;
  }

  /**
   * Retrieve the partials list.
   *
   * @return Array
   */
  public function getPartials()
  {
    return $this->partials;
  }
	
  /**
   * Count the total alerts number of the module.
   */
  public function countAlerts($status = null)
  {
    $countAlerts = 0;

    foreach ($this->actions as $action)
    {
      // Modules
      $countAlerts += $action->countAlerts($status);
    }

    foreach ($this->templates as $template)
    {
      // Modules
      $countAlerts += $template->countAlerts($status);
    }

    foreach ($this->partials as $partial)
    {
      // Modules
      $countAlerts += $partial->countAlerts($status);
    }

    $countAlerts += parent::countAlerts($status);

    return $countAlerts;
  }

  /**
   * Test if the module has at least one alert for itself or one of its child.
   *
   * @since V1.0.2 - 07/01/2010
   * @return Boolean
   */
  public function hasAlerts($status = null)
  {
    return $this->countAlerts($status) > 0;
  }

  /**
   * Count the total alerts number of the actions of the module
   */
  public function countActionsAlerts($status = null)
  {
    $countActionsAlerts = 0;

    foreach ($this->getActions() as $action)
    {
      $countActionsAlerts += $action->countAlerts();
    }

    return $countActionsAlerts;
  }

  /**
   * Test if the module object itself has alerts.
   */
  public function hasGenericAlerts($status = null)
  {
    return parent::countAlerts($status) > 0;
  }

  /**
   * Numbers of lines of files fo module. We don't count empty lines.
   *
   * @return Integer
   */
  public function getTotalCodeLength($withComments = false)
  {
    $totalCodeLength = 0;

    foreach ($this->actions as $action)
    {
      $totalCodeLength += $action->getTotalCodeLength($withComments);
    }

    return $totalCodeLength;
  }
  
  /**
   * Compute the total code length of application templates and layouts.
   */
  public function getTemplateCodeLength($withComments = false)
  {
    $codeLength = 0;

    // Templates
    foreach ($this->templates as $template)
    {
      $codeLength += $template->getTemplateCodeLength($withComments);
    }

    // Partials
    foreach ($this->partials as $partial)
    {
      $codeLength += $partial->getTemplateCodeLength($withComments);
    }

    return $codeLength;
  }

  public function isAutogenerated()
  {
    return $this->isAutogenerated;
  }

  /**
   * Numbers of characters in a module
   *
   * @return Integer
   */
  public function getTotalCharacters()
  {
    $totalCharacters = 0;

    foreach ($this->actions as $action)
    {
      $totalCharacters += $action->getTotalCharacters();
    }

    return $totalCharacters;
  }
 
  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String   $path  Path of the root of the project.
   * @param Integer  $type  Type of the project.
   */
  public function __construct(paSfApplication $application, $name)
  {
    $this->application = $application;
    $this->name        = $name;
  }

  /**
   * Extract modules informations.
   *
   * @var $action paSfComponent
   */
  public function process()
  {
    // Actions
    $this->processActions();
    foreach ($this->getActions() as $action)
    {
      $action->process();
    }

    // Templates
    $this->processTemplates();
    foreach ($this->getTemplates() as $template)
    {
      $template->process();
    }

    // Partials
    foreach ($this->getPartials() as $partial)
    {
      $partial->process();
    }

    $this->processAlerts();
  }

  /**
   * Extract actions informations.
   */
  public function processActions()
  {
    $config = $this->getConfig();
    $modulesActions = array();
    $modulesOtherFunctions = array();
    $countMethods = 0;
    $mainActionClass = $this->getName(). 'Actions';
    $ignoredActions = $this->getApplication()->getProject()->getIgnoredObjects('action');

    // auto-generated modules are not parsed by the plugin
    $generatorFile = $this->getApplication()->getPath(). DIRECTORY_SEPARATOR.
      $this->getApplication()->getName(). DIRECTORY_SEPARATOR. 'modules'. DIRECTORY_SEPARATOR.
      $this->getName(). DIRECTORY_SEPARATOR. 'config'. DIRECTORY_SEPARATOR. 'generator.yml'
    ;

    if (is_readable($generatorFile))
    {
      $this->isAutogenerated = true;
    }

    // The plugin does not handle dimension or custom actions class for now
    $actionFile = $this->getApplication()->getPath(). DIRECTORY_SEPARATOR.
      $this->getApplication()->getName(). DIRECTORY_SEPARATOR. 'modules'. DIRECTORY_SEPARATOR.
      $this->getName(). DIRECTORY_SEPARATOR. 'actions'. DIRECTORY_SEPARATOR. 'actions.class.php'
    ;

    // For now we don't handle same modules in multiple applications
    if (!is_readable($actionFile))
    {
      return;
    }

    if (!$this->isAutogenerated())
    {
      // Same module but for another application, so create a temporary action class.
      if (class_exists($mainActionClass))
      {
        $fs = $this->getApplication()->getProject()->getFileSystem();
        $mainActionClassCache = $this->getApplication()->getName(). '_'. $this->getName(). 'Actions';
        $actionFileCache = $this->getApplication()->getProject()->getPath('sf_cache_dir'). DIRECTORY_SEPARATOR. $mainActionClassCache. '.class.php';
        $fs->copy($actionFile, $actionFileCache);
        $fs->replaceTokens(array($actionFileCache), '', '', array($mainActionClass => $mainActionClassCache));
        require_once($actionFileCache);
        $mainActionClass = $mainActionClassCache;
      }
      else
      {
        require_once($actionFile);
      }

      // Create the reflection object
      $actionReflection = new ReflectionClass($mainActionClass);

      // Store the actions code
      $this->actionsCode = file_get_contents($actionReflection->getFileName());

      // Delete the temporary actions class if applicable
      if (isset($fs))
      {
        $fs->remove($actionFileCache);
      }

      // Now loop on each functions
      foreach ($actionReflection->getMethods() as $method)
      {
        // Do not handle functions that are declared in another class for now
        if ($method->getDeclaringClass()->getName() != $mainActionClass)
        {
          continue;
        }

        $isActionMethod = $this->isActionMethod($method);

        // OK - Valid action method
        if ($isActionMethod)
        {          
          $actionName = strtolower(preg_replace(paSfProject::$sfRegexps['ACTION_METHOD'], '$1', $method->getName()));
          $actionFullName = $this->getApplication()->getName(). '/'. $this->getName(). '/'. $actionName;

          // Check if action is not ignored
          if (!in_array($actionFullName, $ignoredActions))
          {
            $sfAction = new paSfAction($this, $actionName);
            $sfAction->setDocComment($method->getDocComment());
            $sfAction->extractCode($this->actionsCode, $method);
            $modulesActions[] = $sfAction;
          }
        }

        // NOK - ALERT 3001 - Public and not an execute action then add an 'alert'
        if ($this->isAlert3001($method, $mainActionClass))
        {
          $alert = new paAlert(
            paAlert::ALERT_MODULE_PUBLIC_FUNCTION,
            paAlert::WARNING,
            sprintf('The method "%s" is public but is not named as an action method, therefore it should be declared as protected.', $method->getName()),
            'Change the method visibility to protected'
          );

          $this->addAlert($alert);
        }
      }
    }
        
    $this->actions = $modulesActions;
  }

  /**
   * Retrieve the template and partials list associated to the module.
   *
   * @internal ysfDimensionsPlugin support, modifications have to be done here.
   */
  protected function processTemplates()
  {
    $templates = array();
    $partials  = array();
    $templatesDir =
      $this->getApplication()->getPath(). DIRECTORY_SEPARATOR.
      $this->getApplication()->getName(). DIRECTORY_SEPARATOR. 'modules'. DIRECTORY_SEPARATOR.
      $this->getName(). DIRECTORY_SEPARATOR. 'templates'
    ;

    // Get 1st level partials templates ////////////////////////////////////////
    $results  = sfFinder::type('file')
      ->maxdepth(0)
      ->in($templatesDir)
    ;

    $templates = array();
    foreach ($results as $filePath)
    {
      $matches = null;
      preg_match(paProject::$regexps['ENDING_FILENAME'], $filePath, $matches);
      $infos = explode('_', sfInflector::underscore($matches[1]));

      // Does not stat by _ => template
      if (!empty($infos[0]))
      {
        // Extract action status
        $actionStatus = explode('.', $infos[1]);
        $object = new paSfTemplate($this, $matches[1], $actionStatus[0]);
        $templates[] = $object;
      }
      // Start by _ => partial
      else
      {
        $object = new paSfPartial($this, $matches[1]);;
        $partials[] = $object;
      }

      // Compute file length
      $fileContent = file_get_contents($filePath);

      // Keep the file name in the object ??
      $object->setCodeLength(empty($fileContent) ? 0 : count(explode("\n", $fileContent)));
    }

    // Get 2nd level partials //////////////////////////////////////////////////
    $resultsLevel2 = sfFinder::type('file')
      ->maxdepth(1)
      ->in($templatesDir)
    ;
    
    // Remove 1st level
    $resultsLevel2 = array_diff($resultsLevel2, $results);

    // Modifiy here for dimmensions support
    foreach ($resultsLevel2 as $filePath)
    {
      $matches = null;
      preg_match(paProject::$regexps['ENDING_PARTIAL_FILENAME'], $filePath, $matches);
      $object = new paSfPartial($this, $matches[1]);
      $fileContent = file_get_contents($filePath);
      $object->setCodeLength(empty($fileContent) ? 0 : count(explode("\n", $fileContent)));
      $partials[] = $object;
    }

    $this->templates = $templates;
    $this->partials  = $partials;
  }

  /**
   * Check and process the alerts specific to the module.
   */
  protected function processAlerts()
  {
    $config = $this->getConfig();
    $this->processAlert3002($config);
  }

  /**
   * @internal See message & help.
   *
   * @since V0.9.0 - 30/06/10
   */
  protected function processAlert3002($config)
  {    
    // Modules alerts
    if ($config['max_actions_count'] && (count($this->actions) > $config['max_actions_count']))
    {
      $alert = new paAlert(
        paAlert::ALERT_MODULE_ACTIONS_COUNT,
        paAlert::WARNING,
        sprintf('The module "%s" has "%d" actions, it is too much. (threshold=%s)', $this->getName(), count($this->actions), $config['max_actions_count']),
        'Consider splitting your module into several ones but more specific'
      );

      $this->addAlert($alert);
    }
  }

  /**
   * Test if function is a valid action method that can be called. (executeXXX)
   *
   * @param ReflectionMethod $method
   * @return Boolean
   */
  public function isActionMethod(ReflectionMethod $method)
  {
    return
        $method->isPublic()                                                         // public
        && preg_match(paSfProject::$sfRegexps['ACTION_METHOD'], $method->getName()) // start by execute
        && $method->getName() != 'execute'                                          // is not the global execute function
    ;
  }

  /**
   * Test if it is a public anonymous function.
   *
   * @param ReflectionMethod $method
   * @return Boolean
   */
  public function isAlert3001(ReflectionMethod $method, $mainActionClass)
  {
    $config = $this->getApplication()->getProject()->getConfig();
    
    // Test if is a validateXXX function
    if ($config['global']['check_compat10'])
    {
      $isValidateFunction = preg_match(paSfProject::$sfRegexps['VALIDATE_METHOD'], $method->getName());
    }
    else
    {
      $isValidateFunction = true;
    }

    // Test execute
    $isExecuteFunction = preg_match(paSfProject::$sfRegexps['ACTION_METHOD'], $method->getName());

    return (
      $method->isPublic()                                                       // public
      && (
        (!$config['global']['check_compat10'] && !$isExecuteFunction)            // does NOT start by execute
          || 
        ($config['global']['check_compat10'] && !$isValidateFunction && !$isExecuteFunction) // compat and NOT start by validate
      )
      && ($method->getDeclaringClass()->getName() == $mainActionClass)              // is declared in the current module action class
      && ($method->getName() != 'preExecute' && $method->getName() != 'postExecute')  // is not preExecute or postExecute
    );
  }

  /**
   * Debug object.
   *
   * @return String
   */
  public function __toString()
  {
    return
      'parent ('. get_class($this). ')='. $this->getApplication()->getName(). ', '.
      'name='.        $this->getName()
    ;
  }
}