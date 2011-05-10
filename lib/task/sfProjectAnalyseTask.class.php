<?php

/**
 * Outputs statistics and raises coding standarts alerts about your symfony project.
 *
 * @package    sfProjectAnalyserPlugin
 * @subpackage task::project
 * @author     lvernet <qrf_coil[at]yahoo[dot].fr>
 */
class sfProjectAnalyseTask extends sfBaseTask
{
  /**
   * Return code when the analysis raison no alert not warning.
   */
  const ANALYSIS_OK = 10;
  
  /**
   * Main project object.
   *
   * @var paSfProject
   */
  protected $project;

  /**
   * Start time of analyse.
   *
   * @var Float
   */
  protected $startedAt;

  /**
   * End time of analyse.
   *
   * @var Float
   */
  protected $endedAt;
  
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('config', null, sfCommandOption::PARAMETER_OPTIONAL, 'The config you want to use to analyse the project', 'default'),
      new sfCommandOption('type', null, sfCommandOption::PARAMETER_OPTIONAL, 'The type of the project', paProject::TYPE_SYMFONY),
      new sfCommandOption('name', null, sfCommandOption::PARAMETER_OPTIONAL, 'The name of the project to analyse', ''),
      new sfCommandOption('path', null, sfCommandOption::PARAMETER_OPTIONAL, 'The path of the project to parse (for non symfony project only)', ''),
      new sfCommandOption('output', null, sfCommandOption::PARAMETER_OPTIONAL, 'The type of the output (html or xml)', 'html'),
    ));

    $this->namespace        = 'project';
    $this->name             = 'analyse';
    $this->briefDescription = 'Analyzes and outputs several statistics about your symfony project';

    $this->detailedDescription = <<<EOF
The [project:analyse|INFO] task analyses your symfony project:

> Note that the application option does not mean that you will analyse this one but
> it will be used to create an application context in order to be able to parse the project.

  [./symfony project:analyse --application="frontend" --env="dev" --name="My Project" --config="default" > analysis.html|INFO]
.
EOF;
  }

  /**
   * Main task function.
   *
   * @var $configuration sfProjectConfiguration
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    // Initialize the context
    sfContext::createInstance($this->configuration);

    // Record start time
    $this->startedAt = $this->getMicroTime();

    // Get current config
    $this->config = $this->checkAndGetConfig($options['config']);

    switch ($options['type'])
    {
      case paProject::TYPE_SYMFONY:
        $this->project = new paSfProject($this->config, $options['config'], $this->getFilesystem());
        break;

      case paProject::TYPE_CAKE_PHP:
        throw new RuntimeException('Are you OK ? :)');
        break;

      case paProject::TYPE_PHP:
      case paProject::TYPE_JAVA:
      default:
        throw new RuntimeException(sprintf('The analysis of this type of project (%s) is not implemented yet; available: type=1 for a symfony project (This is the default option)', $options['type']));
        break;
    }

    $this->project->analyse($arguments, $options);
    $this->endedAt = $this->getMicroTime();
    $this->outputStats($arguments, $options);
    $returnCode = $this->getReturnCode($arguments, $options);
    
    return $returnCode;
  }

  /**
   * Return code depending on the alerts.
   *
   * @see paAlert
   */
  protected function getReturnCode($arguments, $options)
  {
    $errors = 0;
    $errorCode = self::ANALYSIS_OK;

    // When using html don't take care the final alert summary
    if ($options['output'] == 'html')
    {
      $errors = -1;
    }

    foreach ($this->project->getAlertsSummary() as $status => $count)
    {
      $errors += $count;
    }

    return $errors == 0 ? $errorCode : -$errors;
  }

  /**
   * Generate the config file if not present and returns a configuration for
   * a given key or return the default configuration if no specific key
   * was provided.
   *
   * @param $config String Config key
   * @return Array
   */
  public function checkAndGetConfig($config = null)
  {
    $configs = include(sfContext::getInstance()->getConfigCache()->checkConfig('config/plugin_sfpa.yml'));

    if (!is_null($config) && !array_key_exists($config, $configs))
    {
      throw new RuntimeException(sprintf('There is no "%s" configuration ! Copy the plugin_sfpa.yml into your /config project or application directory, copy/paste the "default" configuration and then remane it to "%s"',
        $config, $config
      ));
    }

    $this->checkConfig($configs[$config]);
    
    return $configs[$config];
  }

  /**
   * Control that we have all the required parameters to do the analysis.
   *
   * @since V1.0.2 - 06/01/2011
   * @param Array $config
   */
  protected function checkConfig($config)
  {
    $this->settings = array(
      'global' => array(
        'check_functions_docblock',
        'check_context_get_instance',
        'check_plugins',
        'check_compat10',
        'ignored_objects',
        'check_class_docblock'
      ),
      'project' => array(),
      'application' => array(),
      'classes' => array(
        'grouping_limit'
      ),
      'module' => array(
        'max_actions_count'
      ),
      'action' => array(
        'max_code_length',
        'check_other_actions_call'
      ),
      'template' => array(
        'max_code_length',
        'check_empty'
      ),
      'partial' => array(
        'max_code_length',
        'check_empty'
      ),
      'layout' => array(
        'max_code_length',
        'check_empty'
      ),
      'plugin' => array(
        'to_parse',
        'to_ignore',
        'assets_path'
      )
    );

    foreach ($this->settings as $section => $settings)
    {
      foreach ($settings as $setting)
      {
        if (!isset($config[$section][$setting]))
        {
          throw new RuntimeException("Can't find the '$setting' setting in the '$section' section, please update your plugin config file (plugin_sfpa.yml).", paAlert::ALERT_TASK_SETTING_NOT_FOUND);
        }
      }
    }
  }
  
  /**
   * Output the analysis results depending on the wanted type.
   *
   * @param Array $arguments
   * @param Array $options
   * @return void
   */
  protected function outputStats($arguments, $options)
  {
    switch ($options['output'])
    {
      case 'html':
        echo $this->getBuffer($arguments, $options);
        break;

      case 'xml':
        echo $this->getBufferAsXml($arguments, $options);
        break;

      default:
        throw new InvalidArgumentException('This output option: "'. $options['output']. '" is not implemented yet, allowed values are "html" or "xml")', 1);
        break;
    }
  }

  /**
   * Return the html buffer of output.
   *
   * @param Array $arguments
   * @param Array $options
   * @return String
   */
  public function getBuffer($arguments, $options)
  {    
    $html  = '';
    $html .= $this->getHeader(). '<hr/>';
    $html .= $this->getConfigSummary(). '<hr/>';
    $html .= $this->getProjectOutput(). '<hr/>';
    $html .= '
	<div id="treecontrol">
    <a title="Collapse the entire tree below" href="#"><img src="%plugin_assets_path%/images/minus.gif" />Collapse All</a>
    <a title="Expand the entire tree below" href="#"><img src="%plugin_assets_path%/images/plus.gif" />Expand All</a>
    <a title="Toggle the tree below, opening closed branches, closing open branches" href="#">Toggle All</a>
	</div>';

    // Fix assets path
    $html = str_replace(
      '%plugin_assets_path%',
      $this->config['plugin']['assets_path'],
      $html
    );
    
    $html .= $this->getApplicationsOutput(). '<hr/>';
    if ($this->config['global']['check_plugins'])
    {
      $html .= $this->getPluginsOutput(). '<hr/>';
    }

    // Display final project alert
    foreach ($this->project->getAlerts() as $alert)
    {
      $html .= $this->getAlertOutput($alert);
      $html .= $this->addline();
    }
    
    $html .= $this->getLibsOutput($alert);
    $html .= $this->addline();
      
    $html .= $this->getFooter();

    return $html;
  }

  /**
   * Panel that displays the configuration used.
   */
  protected function getConfigSummary()
  {
    $html =  '<ul id="summary" class="treeview-black">';
    $html .= '<li class="closed"><span class="folder"><strong>Analysis configuration</strong></span>';
    $html .= '<ul>';
    $html .= '  <li><pre>'. sfYaml::dump($this->config). '</pre></li>';
    $html .= '</ul>';
    $html .= '</ul>';
    
    return $html;
  }
  
  /**
   * Return output as xml.
   *
   * @param Array $arguments
   * @param Array $options
   * @return String
   */
  public function getBufferAsXml($arguments, $options)
  {
    $dom = new DomDocument('1.0');
    $response = $dom->appendChild($dom->createElement('response'));
    $returnCode = $this->getReturnCode($arguments, $options);
    $successStatus = ($returnCode == self::ANALYSIS_OK) ? 1 : 0;
    $success = $response->appendChild($dom->createElement('success', $successStatus));
    $error = $response->appendChild($dom->createElement('error', $successStatus == 1 ? 0 : 1));
    $dom->formatOutput = true;

    if (!$successStatus && ($returnCode < 0))
     {
      $summary = $response->appendChild($dom->createElement('summary'));
       
      foreach ($this->project->getAlertsSummary() as $status => $count)
      {
        switch ($status)
        {
          case paAlert::ALERT:
            $xmlKey = 'ALERT';
            break;
          case paAlert::CRIT:
            $xmlKey = 'CRIT';
            break;
          case paAlert::ERR:
            $xmlKey = 'ERR';
            break;
          case paAlert::WARNING:
            $xmlKey = 'WARNING';
            break;
          case paAlert::NOTICE:
            $xmlKey = 'NOTICE';
            break;
          case paAlert::INFO:
            $xmlKey = 'INFO';
            break;
        }

        $summary->appendChild($dom->createElement(strtolower($xmlKey), $count));
      }
    }
    
    return $dom->saveXML();
  }
  
  /**
   * Project statistics.
   */
  protected function getProjectOutput()
  {
    $html  = '';
    $html .= '<h2>Project '. $this->project->getName(). ' (symfony '. $this->project->getSymfonyVersion(). ') </h2>';

    // Applications summary
    $this->applications = $this->project->getApplications();
    $html .= '- Number of applications: <b>'. count($this->applications). '</b>'. $this->addline();
    
    $html .= '- Applications: <b>'. implode($this->project->getApplicationsList(), ', '). '</b>'. $this->addline();
    
    // Modules summary
    $html .= '- Modules count: <b>'. $this->project->getModulesCount(). '</b>'. $this->addline();

    // Actions summary
    $html .= '- Actions count: <b>'. $this->project->getActionsCount(). '</b>'. $this->addline();

    // Plugins summary
    if ($this->config['global']['check_plugins'])
    {
      $this->plugins = $this->project->getPlugins();
      $html .= '- Number of analysed plugins: <b>'. count($this->plugins). '</b>'. $this->addline();
      $pluginsList = $this->project->getPluginsList() ? implode($this->project->getPluginsList(), ', ') : "No plugin parsed: update the 'to_parse' setting in the 'plugin' section";
      $html .= '- Plugins: <b>'. $pluginsList. '</b>'. $this->addline();
    }

    // Lib summary
    $html .= '- Number of interface(s): <b>'. count($this->project->getInterfaces()). '</b>'. $this->addline();
    $html .= '- Number of classe(s): <b>'. count($this->project->getClasses()). '</b>'. $this->addline();

    // Extended symfony classes
    $extendedSymfonyClasses = $this->project->getSymfonyExtendedClasses();
    if ($extendedSymfonyClasses)
    {
      $extendedSymfonyClassesCount = 0;
      foreach ($extendedSymfonyClasses as $extendedSymfonyClass => $childClasses)
      {
        $extendedSymfonyClassesCount += count($childClasses);
      }

      $html .= '- Including <b>'. $extendedSymfonyClassesCount .'</b> that extend a symfony native class';
      $list = array();

      foreach ($extendedSymfonyClasses as $extendedSymfonyClass => $childClasses)
      {
        if (count($childClasses) > $this->config['classes']['grouping_limit'])
        {
          $list[] = '<b>'. count($childClasses). '</b> classes extending the <b>'. $extendedSymfonyClass. '</b> class';
        }
        else
        {
          foreach ($childClasses as $childClass)
          {
            $list[] = '<b>'. $childClass->getName(). '</b> ('.  $childClass->getParentClass() .')';
          }
        }
      }
      $html .= ' : '. implode(', ', $list). $this->addline();
    }
    
    // Code length summary
    $html .= '- Total code length: <b>'. $this->project->getTotalCodeLength(true). '</b> line(s)'. $this->addline();
    $html .= '- Total code length without comments: <b>'. $this->project->getTotalCodeLength(). '</b> line(s)'. $this->addline();
    $html .= '- Templates total code length: <b>'. $this->project->getTemplateCodeLength(true). '</b> line(s)'. $this->addline();

    // Display the list of ignored objects
    $ignoredObjects = $this->config['global']['ignored_objects'];
    if ($ignoredObjects)
    {
      foreach ($ignoredObjects as $section => $objects)
      {
        if (count($objects))
        {
          $html .= ' - '. ucfirst($section). '(s) ignored: ';
          $html .= '<span class="red">'. implode($objects, ', '). '</span><br/>';
        }
      }
    }

    // Display project alerts (when implemented)

    return $html;
  }

  /**
   * Output lib details.
   *
   * @return String
   */
  protected function getLibsOutput()
  {
    $html = '';
    $html .= '<ul id="libs" class="treeview-black">';

    foreach ($this->project->getClasses() as $class)
    {
      if(count($class->getAlerts())>0)
      {
        $html .= '<li><span class="folder"><strong>Class '. $class->getName(). '</strong></span>';
        $html .= '<ul>';
        foreach ($class->getAlerts() as $alert)
        {
          $html .= $this->getAlertOutput($alert);
        }
        $html .= '</ul>';
      }
      
      foreach($class->getMethods() as $method)
      {
        if(count($method->getAlerts()) > 0)
        {
          $html .= '<li><span class="folder"><strong>Methode '. $class->getName(). '::'. $method->getName(). '</strong></span>';
          $html .= '<ul>';

          foreach ($method->getAlerts() as $mAlert)
          {
            $html .= $this->getAlertOutput($mAlert);
          }
          
          $html .= '</ul>';
        }
      }
    }

    return $html;
  }
  
  /**
   * Output plugins details.
   *
   * @return String
   */
  protected function getPluginsOutput()
  {
    $html = '';
    $html .= '<ul id="plugins" class="treeview-black">';

    foreach ($this->project->getPlugins() as $plugin)
    {
      $modules = $plugin->getModulesList();

      $html .= '<li><span class="folder"><strong>Plugin '. $plugin->getName() . '</strong></span>';
      $html .= '<ul>';

      // Modules summary
      $html .= sprintf('<li>It has <b>%d</b> module(s) (%s)</li>', count($modules), implode($modules, ', '));
      $html .= sprintf('<li>It has <b>%d</b> action(s) in its module(s)</li>', $this->project->getActionsCount($plugin));
      $html .= '<li>Total modules code length: <b>'. $plugin->getTotalCodeLength(true). '</b> line(s)</li>';
      $html .= '<li>Total modules code length without comments: <b>'. $plugin->getTotalCodeLength(). '</b> line(s)</li>';
      $html .= '<li>Modules Templates total code length: <b>'. $plugin->getTemplateCodeLength(). '</b> line(s)</li>';

      // Modules
      $html .= $this->getModulesOutput($plugin);

      // Layouts
      $html .= $this->getLayoutsOutput($plugin);

      // Partials
      $html .= $this->getPartialsOutput($plugin);

      // TODO: libs

      $html .= '</ul>';
    }

    $html .= '</ul>';

    return $html;
  }

  /**
   * Output applications details.
   * 
   * @return String
   */
  protected function getApplicationsOutput()
  {
    $html = '';

    $html .= '<ul id="project" class="treeview-black">';

    // Applications details
    foreach ($this->project->getApplications() as $application)
    {
      $modules = $application->getModulesList();

      $html .= '<li><span class="folder"><strong>Application '. $application->getName() . '</strong></span>';
      $html .= '<ul>';

      // Layouts and partials
      $html .= sprintf('<li>It has <b>%d</b> layout(s) and <b>%d</b> partials</li>', count($application->getLayouts()), count($application->getPartials()) );

      // Modules summary
      $html .= sprintf('<li>It has <b>%d</b> module(s) (%s)</li>', count($modules), implode($modules, ', '));
      $html .= sprintf('<li>It has <b>%d</b> action(s) in its module(s)</li>', $this->project->getActionsCount($application));
      $html .= '<li>Total modules code length: <b>'. $application->getTotalCodeLength(true). '</b> line(s)</li>';
      $html .= '<li>Total modules code length without comments: <b>'. $application->getTotalCodeLength(). '</b> line(s)</li>';
      $html .= '<li>Modules Templates total code length: <b>'. $application->getTemplateCodeLength(). '</b> line(s)</li>';

      // Modules
      $html .= $this->getModulesOutput($application);

      // Layouts
      $html .= $this->getLayoutsOutput($application);

      // Partials
      $html .= $this->getPartialsOutput($application);

      $html .= '</ul>';
      
      // TODO: Display application alerts (when application will have alerts)
    }

    $html .= '</ul>';

    return $html;
  }

  /**
   * Get the modules stats of an application.
   */
  protected function getModulesOutput($application)
  {
    $html = '';

    foreach ($application->getModules() as $module)
    {        
      $html .= '<li class="closed">';
      
      if ($module->isAutogenerated())
      {
        $html .= sprintf('<span class="folder'.($module->countAlerts() > 0 ? ' red' : '') .'">
            Module <b>%s</b> (admin generator) has <b/>%d</b> templates code line(s)</span>',
          $module->getName(),
          $module->getTemplateCodeLength()
        ). $this->addline();

        $html .= '<ul>';
      }

      if (!$module->isAutogenerated())
      {
        $actionsList = $module->getActionsList();

        $html .= sprintf('<span class="folder'.($module->countAlerts() > 0 ? ' red' : '') .'">
            Module <b>%s</b> has <b>%d action(s)</b> (%s) for a total of <b>%d</b> code line(s) and <b/>%d</b> templates code line(s)</span>',
          $module->getName(),
          count($actionsList),
          implode($actionsList, ', '),
          $module->getTotalCodeLength(),
          $module->getTemplateCodeLength()
        ). $this->addline();

        $html .= '<ul>';
      }

      // Templates and partials summary
      $html .= sprintf('<li>It has <b>%d template(s)</b> and <b>%d partial(s)</b>',
        count($module->getTemplates()), count($module->getPartials())
      ). $this->addline();

      // Actions infos
      $html .= $this->getActionsOutput($module);

      // Templates infos
      $html .= $this->getTemplatesOutput($module);

      // Partials infos
      $html .= $this->getPartialsOutput($module);

      // Display module generic alerts (to move at the top ?)
      if ($module->hasGenericAlerts())
      {
        $html .= $this->addLine(). '&raquo; Module generic alerts:';
      }
      foreach ($module->getAlerts() as $alert)
      {
        $html .= $this->getAlertOutput($alert);
        $html .= $this->addline();
      }

      $html .= $this->addline();
      $html .= '</li></ul>';

      
      $html .= '</li>';
    }

    return $html;
  }

  /**
   * Get the actions stats of an module.
   */
  protected function getActionsOutput($module)
  {
    $html = '';
    
    foreach ($module->getActions() as $action)
    {
      // Avoid Div by 0 bug if unexcepted error occured when getting the action code
      if ($action->getTotalCodeLength() != 0)
      {
        $codePercent = round(($action->getCodeLength() / $action->getTotalCodeLength()) * 100);
        $html .= sprintf('Action <b>%s</b> has %d code line(s) (code: %s%% comments: %s%%)',
          $action->getName(),
          $action->getTotalCodeLength(),
          $codePercent,
          100 - $codePercent
        ). $this->addline();
      }
      else
      {
          $html .= sprintf('Action <b>%s</b> has %d code line(s), bug ? (code: ?, comments: ?)',
            $action->getName(),
            $action->getTotalCodeLength()
          ). $this->addline();
      }

      // Display actions alerts
      foreach ($action->getAlerts() as $alert)
      {
        $html .= $this->getAlertOutput($alert);
        $html .= $this->addline();
      }
    }

    return $html;
  }

  /**
   * Get the layout alert output.
   */
  protected function getLayoutsOutput($application)
  {
    $html     = '';
    $hasAlert = false;

    foreach ($application->getLayouts() as $layout)
    {
      if ($layout->hasAlerts() && !$hasAlert)
      {
        $html .= $this->addLine(). '&raquo; Application layout alerts:';
        $hasAlert = true;
      }

      // Display actions alerts
      foreach ($layout->getAlerts() as $alert)
      {
        $html .= $this->getAlertOutput($alert);
        $html .= $this->addline();
      }
    }

    return $html;
  }
  
  /**
   * Get the templates alert output.
   */
  protected function getTemplatesOutput($module)
  {
    $html      = '';
    $hasAlert = false;

    foreach ($module->getTemplates() as $template)
    {
      if ($template->hasAlerts() && !$hasAlert)
      {
        $html .= $this->addLine(). '&raquo; Module templates alerts:';
        $hasAlert = true;
      }

      // Display actions alerts
      foreach ($template->getAlerts() as $alert)
      {
        $html .= $this->getAlertOutput($alert);
        $html .= $this->addline();
      }
    }

    return $html;
  }

  /**
   * Get the partials alert output of a parent object.
   */
  protected function getPartialsOutput($parent)
  {
    $html = '';
    $hasAlert = false;

    foreach ($parent->getPartials() as $partial)
    {
      if ($partial->hasAlerts() && !$hasAlert)
      {
        $html .= $this->addLine(). '&raquo; '.  $partial->getParent()->getYamlSection(false). ' partials alerts:';
        $hasAlert = true;
      }

      // Display actions alerts
      foreach ($partial->getAlerts() as $alert)
      {
        $html .= $this->getAlertOutput($alert);
        $html .= $this->addline();
      }
    }

    return $html;
  }
  
  /**
   * Header output.
   *
   * @return String
   */
  protected function getHeader()
  {
    $html = sprintf('
<html>
<head>
<style>
body
{
  padding: 30px;
  text-align: left;
  font-family: Arial;
}

h1
{
  text-align: center;
}
</style>
<link rel="stylesheet" href="plugin_assets_path/css/jquery.treeview.css" />
<script src="plugin_assets_path/js/jquery.js"></script>
<script src="plugin_assets_path/js/jquery.treeview.js" type="text/javascript"></script>
<script>
    $(document).ready(function(){
        $("#project, #plugins, #summary").treeview({
            control: "#treecontrol"
        });
    });
</script>
</head>
<body>
<h1><img alt="symfony" style="vertical-align: middle" src="plugin_assets_path/images/symfony.png" />sfProjectAnalyserPlugin
<br/>Analysis of the "%s" project</h1>
', $this->project->getName());

    // Assets path
    $html = str_replace(
      'plugin_assets_path',
      $this->config['plugin']['assets_path'],
      $html
    );
    
    return $html;
  }

  /**
   * Footer output.
   *
   * @return String
   */
  protected function getFooter()
  {
    $html  = '';
    $html .= ' &raquo; Plugin sponsored by <a href="http://www.sqltechnologies.com">SQL-Technologies</a><br />';
    $html .= ' &raquo; Generated the '. date('Y-m-d H:i:s'). ' in '. $this->getElapsedTime($this->startedAt, $this->endedAt). ' second(s)';
    $html .= '</body></html>';

    return $html;
  }

  /**
   * Get elapsed time between 2 timestamp.
   *
   * @param Integer $timeStart
   * @param Integer $timeEnd
   * @return Float
   */
  public static function getElapsedTime($timeStart, $timeEnd)
  {
    return round($timeEnd - $timeStart, 4);
  }

  /**
   * Return microtime from a timestamp.
   *
   * @param $time     Timestamp to retrieve micro time
   * @return numeric  Microtime of timestamp param
   */
  public static function getMicroTime($time = null)
  {
    if (is_null($time))
    {
      $time = microtime();
    }

    list($usec, $sec) = explode(' ', $time);
    
    return (float)$usec + (float)$sec;
  }

  /**
   * Separation lines between <br/> by default.
   *
   * @author lvernet
   * @param  Integer $cpt
   * @param  String  $output
   * @return String
   */
  protected function addline($cpt = 1, $output = "<br/>")
  {
    return str_repeat($output, $cpt);
  }

  /**
   * Checks a configuration.
   *
   * @see paAlert
   */
  public function getAlertOutput(paAlert $alert)
  {
    $pluginAssetsPath = $this->config['plugin']['assets_path'];

    switch ($alert->getStatus())
    {
      case paAlert::ALERT:
      case paAlert::CRIT:
      case paAlert::ERR:
        $color = '#fd3900';
        $image = $pluginAssetsPath. '/images/alert.png';
        $alt = 'fatal';
        break;

      case paAlert::WARNING:
        $color = '#6a9ee6';
        $image = $pluginAssetsPath. '/images/warning.png';
        $alt = 'warning';
        break;

      case paAlert::NOTICE:
      case paAlert::INFO:
        $color = '#60b111';
        $image = $pluginAssetsPath. '/images/check.png';
        $alt   = 'ok';
        break;

      default:
        throw new RuntimeException(sprintf('The "%d" alert status is not handled ! It must be implemented in the sfProjectAnalyseTask::getAlertOutput() function.', $alert->getStatus()), -1);
        break;
    }

    return sprintf('
      <div style="background-color: %s; padding: 4px; margin: 3px; border: 1px #ddd solid; font-size: 18px">
        <div style="float: left"><img alt="%s" style="width: 60%%; vertical-align: middle; margin-right: 10px" src="%s" /></div>
        <div style="float: left; margin-top: 7px; text-align: left;">%s%s</div>
        <div style="clear: both"></div>
      </div>', $color, $alt, $image, $alert->getMessage(), $alert->hasHelp() ? '<div style="background-color: #fff; padding:5px">What to do: '. $alert->getHelp().'</div>' : ''
    );
  }
}