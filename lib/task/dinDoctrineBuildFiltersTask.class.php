<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license informationation, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once sfConfig::get( 'sf_symfony_lib_dir' ) . '/plugins/sfDoctrinePlugin/lib/task/sfDoctrineBaseTask.class.php';

/**
 * Create filter form classes for the current model.
 *
 * @package    symfony
 * @subpackage doctrine
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: dinDoctrineBuildFiltersTask.class.php 49 2010-07-01 18:29:15Z relo_san $
 */
class dinDoctrineBuildFiltersTask extends sfDoctrineBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('model-dir-name', null, sfCommandOption::PARAMETER_REQUIRED, 'The model dir name', 'model'),
      new sfCommandOption('filter-dir-name', null, sfCommandOption::PARAMETER_REQUIRED, 'The filter form dir name', 'filter'),
      new sfCommandOption('generator-class', null, sfCommandOption::PARAMETER_REQUIRED, 'The generator class', 'dinDoctrineFormFilterGenerator'),
    ));

    $this->namespace = 'doctrine-ext';
    $this->name = 'build-filters';
    $this->briefDescription = 'Creates filter form classes for the current model';

    $this->detailedDescription = <<<EOF
The [doctrine:build-filters|INFO] task creates form filter classes from the schema:

  [./symfony doctrine:build-filters|INFO]

This task creates form filter classes based on the model. The classes are
created in [lib/doctrine/filter|COMMENT].

This task never overrides custom classes in [lib/doctrine/filter|COMMENT].
It only replaces base classes generated in [lib/doctrine/filter/base|COMMENT].
EOF;
  }

    /**
     * Execute task
     * 
     * @param   array   $arguments  Task arguments [optional]
     * @param   array   $options    Task options [optional]
     */
    protected function execute( $arguments = array(), $options = array() )
    {

        $this->logSection( 'doctrine', 'generating filter form classes' );
        $databaseManager = new sfDatabaseManager( $this->configuration );
        $generatorManager = new sfGeneratorManager( $this->configuration );
        $generatorManager->generate( $options['generator-class'], array(
            'model_dir_name'  => $options['model-dir-name'],
            'filter_dir_name' => $options['filter-dir-name'],
        ) );

        $this->reloadAutoload();

    } // dinDoctrineBuildFiltersTask::execute()

} // dinDoctrineBuildFiltersTask

//EOF