<?php

/**
 * Class that represent a symfony application.
 *
 * @author lvernet
 * @since  09/06/10
 */
class paSfApplication extends paAlertable
{
  const TYPE_FRONTEND = 1;
  const TYPE_BACKEND  = 2;
  const TYPE_OTHER    = 3;

  /**
   * @var paSfProject   Parent project
   */
  protected $project;

  /**
   * @var String        Name of the application.
   */
  protected $name;

  /**
   * @var Integer       Type of the application
   *
   * @internal: Really necessary ?
   */
  protected $type;

  /**
   * @var Array         List of application modules
   */
  protected $modules = array();

  /**
   * @var Array  List of available applicaction layouts
   */
  protected $layouts = array();

  /**
   * @var Array  List of partials of the application
   */
  protected $partials = array();

  /**
   * @var String        Path of the application
   */
  protected $path;
  
  /* GETTERS / SETTERS ********************************************************/

  public function getProject()
  {
    return $this->project;
  }
  
  public function getApplication()
  {
    return $this;
  }
  
  public function getName()
  {
    return $this->name;
  }
  
  public function getModules()
  {
    return $this->modules;
  }

  public function getLayouts()
  {
    return $this->layouts;
  }
  
  public function getPartials()
  {
    return $this->partials;
  }

  public function getPath()
  {
    return $this->path;
  }

  /**
   * Return an array with the name of all modules of the application.
   *
   * @return Array
   */
  public function getModulesList()
  {
    $modules = array();

    foreach ($this->modules as $module)
    {
      $modules[] = $module->getName();
    }

    return $modules;
  }

  /**
   * Count the total alerts number of the application.
   */
  public function countAlerts($status = null)
  {
    $countAlerts = 0;

    foreach ($this->modules as $module)
    {
      $countAlerts += $module->countAlerts($status);
    }

    foreach ($this->layouts as $layout)
    {
      $countAlerts += $layout->countAlerts($status);
    }

    foreach ($this->partials as $partial)
    {
      $countAlerts += $partial->countAlerts($status);
    }

    $countAlerts += parent::countAlerts($status);

    return $countAlerts;
  }

  /**
   * Compute the total code length of the application.
   */
  public function getTotalCodeLength($with_comments = false)
  {
    $codeLength = 0;

    foreach ($this->modules as $module)
    {
      $codeLength += $module->getTotalCodeLength($with_comments);
    }

    // todo: lib / classes / helpers
    
    return $codeLength;
  }

  /**
   * Compute the total code length of application templates and layouts.
   */
  public function getTemplateCodeLength($with_comments = false)
  {
    $codeLength = 0;

    foreach ($this->modules as $module)
    {
      $codeLength += $module->getTemplateCodeLength($with_comments);
    }

    foreach ($this->layouts as $layout)
    {
      $codeLength += $layout->getTemplateCodeLength($with_comments);
    }
    
    foreach ($this->partials as $partial)
    {
      $codeLength += $partial->getTemplateCodeLength($with_comments);
    }
    
    return $codeLength;
  }

  /**
   * Numbers of characters in the application
   *
   * @return Integer
   */
  public function getTotalCharacters()
  {
    $total_characters = 0;

    foreach ($this->modules as $module)
    {
      $total_characters += $module->getTotalCharacters();
    }

    return $total_characters;
  }
  
  // getConfigTotalCodeLength

  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String  $path   Path of the root of the project.
   * @param Integer  $type  Type of the project.
   */
  public function __construct(paSfProject $project, $name, $type = self::TYPE_FRONTEND)
  {
    $this->project = $project;
    $this->name    = $name;
    $this->type    = $type;
    $this->path    = $this->getProject()->getPath('sf_apps_dir');
  }

  /**
   * Launch all analysis processes.
   */
  public function process()
  {
    $this->processModules();
    $this->processTemplates();

    foreach ($this->modules as $module)
    {
      $module->process();
    }

    foreach ($this->layouts as $layout)
    {
      $layout->process();
    }

    foreach ($this->partials as $partial)
    {
      $partial->process();
    }        
  }

  /**
   * Extract modules informations.
   */
  public function processModules()
  {
    $modulesDir =
      $this->getPath(). DIRECTORY_SEPARATOR.
      $this->getName(). DIRECTORY_SEPARATOR. 'modules'
    ;
    
    $results  = sfFinder::type('dir')
      ->maxdepth(0)
      ->in($modulesDir)
    ;

    $modules = array();
    $ignoredModules = $this->getProject()->getIgnoredObjects('module');

    foreach ($results as $moduleDirectory)
    {
      $matches = null;
      preg_match(paProject::$regexps['ENDING_FILENAME'], $moduleDirectory, $matches);
      $fullModuleName = $this->getName(). '/'. $matches[1];

      if (!in_array($fullModuleName, $ignoredModules))
      {
        $modules[] = new paSfModule($this, $matches[1]);
      }
    }

    $this->modules = $modules;
  }

  /**
   * Retrieve the template and partials list associated to the application.
   *
   * @internal ysfDimensionsPlugin support, modifications have to be done here.
   */
  protected function processTemplates()
  {
    $layouts  = array();
    $partials = array();
    
    $templates_dir =
      $this->getPath(). DIRECTORY_SEPARATOR.
      $this->getName(). DIRECTORY_SEPARATOR. 'templates'
    ;

    // Get 1st level -> layout /////////////////////////////////////////////////
    $results  = sfFinder::type('file')
      ->maxdepth(0)
      ->in($templates_dir)
    ;

    $layouts = array();
    foreach ($results as $filePath)
    {
      $matches = null;
      preg_match(paProject::$regexps['ENDING_FILENAME'], $filePath, $matches);
      $infos = explode('_', sfInflector::underscore($matches[1]));

      // Does not start by _ => layout
      if (!empty($infos[0]))
      {
        $object = new paSfLayout($this, $matches[1]);
        $layouts[] = $object;
      }
      // Start by _ => partial
      else
      {
        $object = new paSfPartial($this, $matches[1]);
        $partials[] = $object;
      }

      // Compute file length
      $file_content = file_get_contents($filePath);

      // Keep the file name in the object ??
      $object->setCodeLength(empty($file_content) ? 0 : count(explode("\n", $file_content)));
    }

    // Get 2nd level partials //////////////////////////////////////////////////
    $resultsLevel2 = sfFinder::type('file')
      ->maxdepth(1)
      ->in($templates_dir)
    ;

    // Remove 1st level
    $resultsLevel2 = array_diff($resultsLevel2, $results);

    // Modifiy here for support
    foreach ($resultsLevel2 as $filePath)
    {
      $matches = null;
      preg_match(paProject::$regexps['ENDING_FILENAME'], $filePath, $matches);
      $object = new paSfPartial($this, $matches[1]);
      $file_content = file_get_contents($filePath);
      $object->setCodeLength(empty($file_content) ? 0 : count(explode("\n", $file_content)));
      $partials[] = $object;
    }

    $this->layouts  = $layouts;
    $this->partials = $partials;
  }
}