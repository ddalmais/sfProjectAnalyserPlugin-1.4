<?php

/**
 * Class that represents a symfony project.
 *
 * @author lvernet
 * @since  09/06/10
 */
class paSfProject extends paProject
{
  // List of regexp used
  public static $sfRegexps = array(
    'ACTION_METHOD'   => '/execute(.*)$/',
    'VALIDATE_METHOD' => '/validate(.*)$/'
  );

  /**
   * @var String    Full version number of symfony version used for project.
   */
  protected $sfVersion;
  
  /**
   * @var Array    Array of applications
   */
  protected $applications = array();

  /**
   * @var Array    Array of applications
   */
  protected $plugins = array();

  /**
   * @var Array    Array of applications
   */
  protected $classes = array();

  /**
   * @var Array    Array of applications
   */
  protected $interfaces = array();
  
  /* GETTERS / SETTERS ********************************************************/

  public function getSymfonyVersion()
  {
    return $this->sfVersion;
  }
  
  public function getApplications()
  {
    return $this->applications;
  }

  public function getPlugins()
  {
    return $this->plugins;
  }

  public function getClasses()
  {
    return $this->classes;
  }

  public function getInterfaces()
  {
    return $this->interfaces;
  }

  /**
   * Return an array with the name of all applications.
   *
   * @todo: refactorize to have only a get"Object"List function
   *
   * @return Array
   */
  public function getApplicationsList()
  {
    $applications = array();
    
    foreach ($this->applications as $application)
    {
      $applications[] = $application->getName();
    }

    return $applications;
  }

  /**
   * Return an array with the name of all plugins.
   *
   * @todo: refactorize to have only a get"Object"List function
   *
   * @return Array
   */
  public function getPluginsList()
  {
    $plugins = array();

    foreach ($this->plugins as $plugin)
    {
      $plugins[] = $plugin->getName();
    }

    return $plugins;
  }

  /**
   * Return the module count of a project, application.
   *
   * @return Integer
   */
  public function getModulesCount($app = null)
  {
    $count = 0;

    foreach ($this->applications as $application)
    {
      if (!is_null($app) && $application !== $app)
      {
        continue;
      }

      $count += count($application->getModules());
    }

    foreach ($this->plugins as $plugin)
    {
      if (!is_null($app) && $plugin !== $app)
      {
        continue;
      }

      $count += count($plugin->getModules());
    }

    return $count;
  }

  /**
   * Return the module count of a project, application.
   *
   * @return Integer
   */
  public function getActionsCount($app = null, $mod = null)
  {
    $count = 0;

    foreach ($this->applications as $application)
    {
      if (!is_null($app) && $application !== $app)
      {
        continue;
      }

      foreach ($application->getModules() as $module)
      {
        if (!is_null($mod) && $module->getName() !== $mod)
        {
          continue;
        }

        $count += count($module->getActions());
      }
    }

    foreach ($this->plugins as $plugin)
    {
      if (!is_null($app) && $plugin !== $app)
      {
        continue;
      }

      foreach ($plugin->getModules() as $module)
      {
        if (!is_null($mod) && $module->getName() !== $mod)
        {
          continue;
        }

        $count += count($module->getActions());
      }
    }

    return $count;
  }

  /**
   * Compute the total code length of the application.
   */
  public function getTotalCodeLength($withComments = false)
  {
    $codeLength = 0;

    foreach ($this->applications as $application)
    {
      $codeLength += $application->getTotalCodeLength($withComments);
    }

    foreach ($this->plugins as $plugin)
    {
      $codeLength += $plugin->getTotalCodeLength($withComments);
    }

    foreach ($this->classes as $class)
    {
      $codeLength += $class->getTotalCodeLength($withComments);
    }

    foreach ($this->interfaces as $interface)
    {
      $codeLength += $interface->getTotalCodeLength($withComments);
    }

    // TODO: Parse helpers

    return $codeLength;
  }

  /**
   * Numbers of characters in the project
   *
   * @return Integer
   */
  public function getTotalCharacters()
  {
    $totalCharacters = 0;

    foreach ($this->applications as $application)
    {
      $totalCharacters += $application->getTotalCharacters();
    }

    return $totalCharacters;
  }

  /**
   * Compute the total code length of project templates and layouts.
   */
  public function getTemplateCodeLength($withComments = false)
  {
    $codeLength = 0;

    // Applications
    foreach ($this->applications as $application)
    {
      $codeLength += $application->getTemplateCodeLength($withComments);
    }

    return $codeLength;
  }

  /**
   * Count the total number of alerts of the project.
   */
  public function countAlerts($status = null)
  {
    $countAlerts = 0;

    foreach ($this->applications as $application)
    {
      $countAlerts += $application->countAlerts($status);
    }

    foreach ($this->plugins as $plugin)
    {
      $countAlerts += $plugin->countAlerts($status);
    }

    foreach ($this->interfaces as $interface)
    {
      $countAlerts += $interface->countAlerts($status);
      foreach($interface->getMethods() as $method)
      {
        $countAlerts += $method->countAlerts($status);
      }
    }

    foreach ($this->classes as $class)
    {
      $countAlerts += $class->countAlerts($status);
      foreach($class->getMethods() as $method)
      {
        $countAlerts += $method->countAlerts($status);
      }
    }

    // Project
    $countAlerts += parent::countAlerts($status);

    return $countAlerts;
  }

  /**
   * Return only the classes that extends a native symfony class.
   */
  public function getSymfonyExtendedClasses()
  {
    $classes = array();

    foreach ($this->classes as $class)
    {
      if ($class instanceof paSfClass)
      {
        $classes[$class->getParentClass()][] = $class;
      }
    }

    return $classes;
  }
  
  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String  $path   Path of the root of the project.
   * @param Integer  $type  Type of the project.
   */
  public function __construct($config, $configName, $fileSystem, $name = '')
  {
    $this->computePaths();
    $path = $this->paths['sf_root_dir'];
    $type = paProject::TYPE_SYMFONY;
    $this->sfVersion = SYMFONY_VERSION;

    parent::__construct($config, $configName, $fileSystem, $path, $type, $name);
  }

  /**
   * Main analyse function.
   */
  public function analyse($arguments = array(), $options = array())
  {
    $this->process();

    foreach ($this->applications as $application)
    {
      $application->process();
    }

    foreach ($this->plugins as $plugin)
    {
      $plugin->process();
    }
    foreach ($this->classes as $class)
    {
      $class->process();
    }

    foreach ($this->interfaces as $interface)
    {
      $interface->process();
    }

    // Add the alert summary for the html output mode
    if ($options['output'] == 'html')
    {
      $this->processFinalAlert();
    }
  }

  /**
   * Compute all paths. Override this function ti use specific paths.
   */
  protected function computePaths()
  {
    $this->paths['sf_root_dir']    = sfConfig::get('sf_root_dir');
    $this->paths['sf_apps_dir']    = sfConfig::get('sf_apps_dir');
    $this->paths['sf_cache_dir']   = sfConfig::get('sf_cache_dir');
    $this->paths['sf_lib_dir']     = sfConfig::get('sf_lib_dir');
    $this->paths['sf_plugins_dir'] = sfConfig::get('sf_plugins_dir');
  }

  /**
   * Launch all analysis processes.
   */
  public function process()
  {
    $this->processApplications();
    
    if ($this->config['global']['check_plugins'])
    {
      $this->processPlugins();
    }

    // TOFIX...
    //$this->processLib();
  }

  /**
   * Extract all applications infos of the project.
   */
  protected function processApplications()
  {
    $applications = array();
    $ignoredApplications = $this->getIgnoredObjects('application');
    $matches      = null;
    $results      = sfFinder::type('dir')
      ->maxdepth(0)
      ->in($this->paths['sf_apps_dir'])
    ;

    foreach ($results as $appDirectory)
    {      
      preg_match(paProject::$regexps['ENDING_FILENAME'], $appDirectory, $matches);

      // Skip application if ignored
      if (!in_array($matches[1], $ignoredApplications))
      {
        $applications[] = new paSfApplication($this, $matches[1]);
      }
    }
    
    $this->applications = $applications;
  }

  /**
   * Extract plugins informations of the project.
   */
  protected function processPlugins()
  {
    $pluginsToParse = $this->config['plugin']['to_parse'];
    $pluginsToIgnore = $this->config['plugin']['to_ignore'];
    $parseAllPlugin = isset($pluginsToParse[0]) ? $pluginsToParse[0] == 'all' : false;

    $plugins = array();
    $matches = null;
    $results = sfFinder::type('dir')
      ->maxdepth(0)
      ->not_name('.*')
      ->in($this->paths['sf_plugins_dir'])
    ;
   
    foreach ($results as $pluginDirectory)
    {
      preg_match(paProject::$regexps['ENDING_FILENAME'], $pluginDirectory, $matches);
      if ($parseAllPlugin || in_array($matches[1], $pluginsToParse))
      {
        if(!in_array($matches[1], $pluginsToIgnore)){
            $plugins[] = new paSfPluginApplication($this, $matches[1]);
        }
      }
    }

   if($parseAllPlugin){
         $this->processLib($this->paths['sf_plugins_dir'],false,
             array_merge($pluginsToIgnore,
             	array('templates',
                'actions'
             	)),
              array('*Generator*'));
    }
    
    $this->plugins = $plugins;
  }

  /**
   * Extract all libs of the project. 'Base' classes are excluded no keep quiet
   * a clean list of class used. Class starting as sf and Doctrine are considered
   * as symfony native classes.
   */
  protected function processLib($path=null,$required=true,$prune=array(),$not_name=array())
  {
    $classes        = array();
    $interfaces     = array();
    if(is_null($path)){
        $path=$this->paths['sf_lib_dir'];
    }
    $libObjects     = $this->extractClasses($path,$prune,$not_name);
    $ignoredClasses = $this->getIgnoredObjects('classes');

    foreach ($libObjects as $class => $filePath)
    {
      // Exclude Base classes
      if (0 === strpos($class, 'Base'))
      {
        continue;
      }

      $object = null;
      
      if (class_exists($class))
      {
        // Create the reflection object
        $reflectionClass = new ReflectionClass($class);
        try
        {
          $parent = $reflectionClass->getParentClass();
        }
        catch (Exception $e)
        {
        }
        
        while ($parent instanceof ReflectionClass)
        {
          // Break if no parent class
          if (!$parent->getParentClass())
          {
            break;
          }
          
          // Break on 1st symfony parent
          if ((strpos($parent->getName(), 'sf') === 0) || (strpos($parent->getName(), 'Doctrine_Table') === 0))
          {
            break;
          }

          $parent = $parent->getParentClass();
        }

        // Test if the class is an extension of a symfony class
        if ($parent instanceof ReflectionClass && ((strpos($parent->getName(), 'sf') === 0) || (strpos($parent->getName(), 'Doctrine_Table') === 0)))
        {
          $object = new paSfClass($this, $class, $parent->getName());
        }
        else
        {
          $object = new paClass($this, $class);
        }

        // Look if we choose th ignore the class
        if (!in_array($object->getName(), $ignoredClasses))
        {
          $classes[] = $object;
        }
      }
      // It is an interface ?
      elseif (interface_exists($class))
      {
        // Look if we choose th ignore the class
        if (!in_array($class, $ignoredClasses))
        {
          $object = new paInterface($this, $class);
          $interfaces[] = $object;
        }
      }
      // Unkown error for this class
      else
      {  
        // Error
        if ($required)
        {
          throw new RuntimeException('Class : '. $class. ' not found, verify both its class name and file name case, if it seems like a bug of the plugin send me an email. :)');
        }
      }

      // Compute file length
      $fileContent = file_get_contents($filePath);

      // Keep the file name in the object ??
      if($object)
      {
        $object->setCodeLength(empty($fileContent) ? 0 : count(explode("\n", $fileContent)));
      }
    }

    $this->interfaces = array_merge($this->interfaces ,$interfaces);
    $this->classes    = array_merge($this->classes ,$classes);
  }

  /**
   * This function is a copy/paste of the make function of the sfCoreAutoload class.
   *
   * @see sfAutoloadCore::make
   */
  public function extractClasses($sfLibDir,$prune=array(),$not_name=array())
  {
    $files = sfFinder::type('file')
      ->prune('plugins')
      ->prune('fixtures')
      ->prune('vendor')
      ->prune('skeleton')
      ->prune('default')
      ->prune('helper')
      ->prune('test');
      
    foreach ($prune as $pr)
    {
      $files->prune($pr);
    }
     
    foreach ($not_name as $nn)
    {
      $files->not_name($nn);
    }
    
    if(strtolower(sfConfig::get('sf_orm'))=='doctrine')
    {
      $files->not_name('*Propel*');
    }
    else
    {
      $files->not_name('*Doctrine*');
    }
    
    $files = $files->name('*.php')->in($sfLibDir);
    sort($files, SORT_STRING);

    $classes = array();
    foreach ($files as $file)
    {
      sfSimpleAutoload::getInstance()->addFile($file);
      $file  = str_replace(DIRECTORY_SEPARATOR, '/', $file);
      $class = basename($file, false === strpos($file, '.class.php') ? '.php' : '.class.php');

      $contents = file_get_contents($file);
      if (false !== stripos($contents, 'class '. $class) || false !== stripos($contents, 'interface '. $class))
      {
        $classes[$class] = $file;
      }
    }
    
    return $classes;
  }
  
  /**
   * Check and process the alerts specific to the project.
   */
  protected function processFinalAlert()
  {   
    // Add the alert summary
    if ($this->hasAlerts())
    {
      $html = '';
      $countAlerts = $this->countAlerts();
      $maxAlertStatus = paAlert::INFO;

      foreach ($this->getAlertsSummary() as $status => $count)
      {
        if ($count > 0)
        {
          if ($status < $maxAlertStatus)
          {
            $maxAlertStatus = $status;
          }
          
          $html .= sprintf(' &raquo; %d %s(S)<br/>', $count, paAlert::getStatusLabel($status));
        }        
      }
     
      $alert = new paAlert(
        paAlert::ALERT_PROJECT_ANALYSIS_NOK,
        $maxAlertStatus,
        sprintf('The project has "%d" alert(s) according to the coding standards of the "%s" configuration:<br/>'. $html,
          $countAlerts,
          $this->getConfigName()
        )
      );
    }
    else
    {
      $alert = new paAlert(
        paAlert::ALERT_PROJECT_ANALYSIS_OK,
        paAlert::INFO,
        sprintf('Congratulations ! The project "%s" respects the symfony coding standards of the "%s" configuration.',
          $this->getName(),
          $this->getConfigName()
        )
      );
    }

    $this->addAlert($alert);
  }

  /**
   * Return an array with the number of alert by status.
   *
   * @return Array
   */
  public function getAlertsSummary()
  {
    $summary      = paAlert::$statusList;
    $finalSummary = array();

    foreach ($summary as $status => $label)
    {      
      $finalSummary[$status] = $this->countAlerts($status);
    }

    return $finalSummary;
  }
}