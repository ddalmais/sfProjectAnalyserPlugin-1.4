<?php

/**
 * Class that represents a project.
 *
 * @author lvernet
 * @since  01/06/10
 */
abstract class paProject extends paAlertable
{
  const TYPE_PHP      = 1;
  const TYPE_SYMFONY  = 2;
  const TYPE_CAKE_PHP = 3;
  const TYPE_JAVA     = 100;

  // List of regexp used
  public static $regexps = array(
    'ENDING_FILENAME' => '/.*\/(.*)$/',
    'CLASS' => '/.*\/(.*)(.|class.)php$/',
  );
  
  /**
   * @var String Physical root of the project
   */
  protected $path;
  
  /**
   * @var Integer  Type of the project
   */
  protected $type = self::TYPE_PHP;

  /**
   * @var String   Name of the project
   */
  protected $name;

  /**
   * @var Array    Paths used to parse the project
   */
  protected $paths = array();

  /**
   * Name of the configuration used by the analyser
   *
   * @var Array
   */
  protected $configName;

  /**
   * File system object of the parent task
   *
   * @var Array
   */
  protected $fileSystem;
  
  /**
   * Current analysis parameters
   *
   * @var Array
   */
  protected $config = array();
  
  /* GETTERS / SETTERS ********************************************************/

  public function getName()
  {
    return $this->name;
  }

  public function getConfig()
  {
    return $this->config;
  }

  public function getConfigName()
  {
    return $this->configName;
  }

  public function getFileSystem()
  {
    return $this->fileSystem;
  }
  
  /* END GETTERS / SETTERS ****************************************************/

  /**
   * Main constructor.
   *
   * @param String  $path   Path of the root of the project.
   * @param Integer $type   Type of the project.
   */
  public function __construct($config, $configName, $fileSystem, $path = null, $type = self::TYPE_PHP, $name = null)
  {
    $this->config     = $config;
    $this->configName = $configName;
    $this->fileSystem = $fileSystem;
    $this->type       = $type;

    // Affect forced path or symfony path
    if (is_null($path) || !is_readable($path))
    {
      throw new InvalidArgumentException(sprintf('The path of your project is not valid ! "%s"', $path), -1);
    }
    $this->path = $path;
       
    // Project name, value or get root directory name
    if (!empty($name))
    {
      $this->name = $name;
    }
    else
    {
      $matches = null;
      preg_match(self::$regexps['ENDING_FILENAME'], str_replace('\\', '/', $this->path), $matches);
      $this->name = $matches[1];
    }
  }

  /**
   * Get a path from the global path array.
   *
   * @param String   $key
   * @return String
   */
  public function getPath($key)
  {
    return array_key_exists($key, $this->paths) ? $this->paths[$key] : '';
  }

  /**
   * Return the ignored objects of a given type.
   *
   * @since V1.0.2 - 01/06/2010
   */
  public function getIgnoredObjects($type)
  {
    $ignoredObjects = $this->config['global']['ignored_objects'];

    return isset($ignoredObjects[$type]) ? $ignoredObjects[$type] : array();
  }

  /**
   * Main analyse function.
   */
  abstract protected function computePaths();

  /**
   * Compute all paths necessary to parse the project.
   */
  abstract public function analyse();
}