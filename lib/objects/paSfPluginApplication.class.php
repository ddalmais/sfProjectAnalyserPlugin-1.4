<?php

/**
 * Class that represent a symfony plugin application.
 *
 * @author nloeuillet
 * @since  08/07/10
 */
class paSfPluginApplication extends paSfApplication
{  
  /**
   * Main constructor.
   *
   * @param String  $path   Path of the root of the project.
   * @param Integer  $type  Type of the project.
   */
  public function __construct(paSfProject $project, $name, $type = self::TYPE_FRONTEND)
  {
    parent::__construct($project, $name, $type);
    $this->path = $this->getProject()->getPath('sf_plugins_dir');
  }
}