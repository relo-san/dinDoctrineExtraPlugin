<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once sfConfig::get( 'sf_symfony_lib_dir' ) . '/plugins/sfDoctrinePlugin/lib/task/sfDoctrineBaseTask.class.php';

/**
 * Create form classes for the current model.
 *
 * @package    symfony
 * @subpackage doctrine
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: dinDoctrineBuildFormsTask.class.php 49 2010-07-01 18:29:15Z relo_san $
 */
class dinDoctrineBuildFormsTask extends sfDoctrineBaseTask
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
      new sfCommandOption('form-dir-name', null, sfCommandOption::PARAMETER_REQUIRED, 'The form dir name', 'form'),
      new sfCommandOption('generator-class', null, sfCommandOption::PARAMETER_REQUIRED, 'The generator class', 'dinDoctrineFormGenerator'),
    ));

    $this->namespace = 'doctrine-ext';
    $this->name = 'build-forms';
    $this->briefDescription = 'Creates form classes for the current model';

    $this->detailedDescription = <<<EOF
The [doctrine:build-forms|INFO] task creates form classes from the schema:

  [./symfony doctrine:build-forms|INFO]

This task creates form classes based on the model. The classes are created
in [lib/doctrine/form|COMMENT].

This task never overrides custom classes in [lib/doctrine/form|COMMENT].
It only replaces base classes generated in [lib/doctrine/form/base|COMMENT].
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

        $this->logSection( 'doctrine', 'generating form classes' );
        $databaseManager = new sfDatabaseManager( $this->configuration );
        $generatorManager = new sfGeneratorManager( $this->configuration );
        $generatorManager->generate( $options['generator-class'], array(
            'model_dir_name' => $options['model-dir-name'],
            'form_dir_name'  => $options['form-dir-name'],
        ) );

        if ( !class_exists( 'BaseForm' ) )
        {
            $file = sfConfig::get( 'sf_lib_dir' ) . '/' . $options['form-dir-name'] . '/BaseForm.php';
            ob_start();
            require sfConfig::get( 'sf_plugins_dir' )
                . '/dinDoctrineExtraPlugin/data/generator/skeleton/form/default/template/dinBaseFormTemplate.php';
            file_put_contents( $file, str_replace(
                array( '[?php', '[?=', '?]' ), array( '<?php', '<?php echo', '?>' ), ob_get_clean()
            ) );
        }

        $this->reloadAutoload();

    } // dinDoctrineBuildFormsTask::execute()

} // dinDoctrineBuildFormsTask

//EOF