<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

require_once sfConfig::get( 'sf_symfony_lib_dir' ) . '/plugins/sfDoctrinePlugin/lib/task/sfDoctrineBaseTask.class.php';

/**
 * Generate doctrine module
 * 
 * @package     dinDoctrineExtraPlugin.lib.task
 * @signed      5
 * @signer      relo_san
 * @author      relo_san [http://relo-san.com/]
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @since       february 26, 2010
 * @version     SVN: $Id: dinDoctrineGenerateModuleTask.class.php 49 2010-07-01 18:29:15Z relo_san $
 */
class dinDoctrineGenerateModuleTask extends sfDoctrineBaseTask
{

    /**
     * Configure task
     * 
     * @return  void
     * @author  relo_san
     * @since   february 26, 2010
     */
    protected function configure()
    {

        $this->addArguments( array(
            new sfCommandArgument( 'application', sfCommandArgument::REQUIRED, 'The application name' ),
            new sfCommandArgument( 'module', sfCommandArgument::REQUIRED, 'The module name' ),
            new sfCommandArgument( 'model', sfCommandArgument::REQUIRED, 'The model class name' )
        ) );

        $this->addOptions(array(
            new sfCommandOption( 'theme', null, sfCommandOption::PARAMETER_REQUIRED, 'The theme name', 'default' ),
            new sfCommandOption( 'generate-in-cache', null, sfCommandOption::PARAMETER_NONE, 'Generate the module in cache' ),
            new sfCommandOption( 'non-verbose-templates', null, sfCommandOption::PARAMETER_NONE, 'Generate non verbose templates' ),
            new sfCommandOption( 'with-show', null, sfCommandOption::PARAMETER_NONE, 'Generate a show method' ),
            new sfCommandOption( 'singular', null, sfCommandOption::PARAMETER_REQUIRED, 'The singular name', null ),
            new sfCommandOption( 'plural', null, sfCommandOption::PARAMETER_REQUIRED, 'The plural name', null ),
            new sfCommandOption( 'plugin', null, sfCommandOption::PARAMETER_REQUIRED, 'The plugin name', null ),
            new sfCommandOption( 'route-prefix', null, sfCommandOption::PARAMETER_REQUIRED, 'The route prefix', null ),
            new sfCommandOption( 'with-doctrine-route', null, sfCommandOption::PARAMETER_NONE, 'Whether you will use a Doctrine route' ),
            new sfCommandOption( 'env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev' ),
            new sfCommandOption( 'actions-base-class', null, sfCommandOption::PARAMETER_REQUIRED, 'The base class for the actions', 'sfActions' )
        ) );

        $this->namespace = 'doctrine-ext';
        $this->name = 'generate-module';
        $this->briefDescription = 'Generates a Doctrine module';

        $d[] = 'The [doctrine:generate-module|INFO] task generates a Doctrine module:';
        $d[] = '';
        $d[] = '  [./symfony doctrine:generate-module frontend article Article|INFO]';
        $d[] = '';
        $d[] = 'The task creates a [%module%|COMMENT] module in the [%application%|COMMENT] application';
        $d[] = 'for the model class [%model%|COMMENT].';
        $d[] = '';
        $d[] = 'You can also create an empty module that inherits its actions and templates from';
        $d[] = 'a runtime generated module in [%sf_app_cache_dir%/modules/auto%module%|COMMENT] by';
        $d[] = 'using the [--generate-in-cache|COMMENT] option:';
        $d[] = '';
        $d[] = '  [./symfony doctrine:generate-module --generate-in-cache frontend article Article|INFO]';
        $d[] = '';
        $d[] = 'The generator can use a customized theme by using the [--theme|COMMENT] option:';
        $d[] = '';
        $d[] = '  [./symfony doctrine:generate-module --theme="custom" frontend article Article|INFO]';
        $d[] = '';
        $d[] = 'This way, you can create your very own module generator with your own conventions.';
        $d[] = '';
        $d[] = 'You can also change the default actions base class (default to sfActions) of';
        $d[] = 'the generated modules:';
        $d[] = '';
        $d[] = '  [./symfony doctrine:generate-module --actions-base-class="ProjectActions" frontend article Article|INFO]';
        $this->detailedDescription = implode( "\n", $d );

    } // dinDoctrineGenerateModuleTask::configure()


    /**
     * Execute task
     * 
     * @param   array   $arguments  Task arguments [optional]
     * @param   array   $options    Task options [optional]
     * @return  void
     * @author  relo_san
     * @since   february 20, 2010
     */
    protected function execute( $arguments = array(), $options = array() )
    {

        $databaseManager = new sfDatabaseManager( $this->configuration );

        $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);

        $this->constants = array(
            'PROJECT_NAME'      => dinGeneratorSigner::getProjectName(),
            'APP_NAME'          => $arguments['application'],
            'MODULE_NAME'       => $arguments['module'],
            'UC_MODULE_NAME'    => ucfirst( $arguments['module'] ),
            'MODEL_CLASS'       => $arguments['model'],
            'PLUGIN_NAME'       => $options['plugin'],
            'PHP'               => '<?php',
            'AUTHOR'            => dinGeneratorSigner::getAuthor(),
            'DATE'              => strtolower( date( 'F d, Y' ) ),
            'AUTHOR_NAME'       => dinGeneratorSigner::getAuthor(),
        );

        $method = $options['generate-in-cache'] ? 'executeInit' : 'executeGenerate';

        $this->$method( $arguments, $options );

    } // dinDoctrineGenerateModuleTask::execute()


    protected function executeGenerate( $arguments = array(), $options = array() )
    {

        // generate module
        $tmpDir = sfConfig::get('sf_cache_dir') . DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.md5(uniqid(rand(), true));
        $generatorManager = new sfGeneratorManager($this->configuration, $tmpDir);
        $generatorManager->generate( 'sfDoctrineGenerator', array(
            'model_class'           => $arguments['model'],
            'moduleName'            => $arguments['module'],
            'theme'                 => $options['theme'],
            'non_verbose_templates' => $options['non-verbose-templates'],
            'with_show'             => $options['with-show'],
            'singular'              => $options['singular'] ? $options['singular'] : sfInflector::underscore($arguments['model']),
            'plural'                => $options['plural'] ? $options['plural'] : sfInflector::underscore($arguments['model'].'s'),
            'route_prefix'          => $options['route-prefix'],
            'with_doctrine_route'   => $options['with-doctrine-route'],
            'actions_base_class'    => $options['actions-base-class'],
        ) );

        $moduleDir = $options['plugin'] ? sfConfig::get( 'sf_plugins_dir' ) . '/' . $options['plugin'] . '/modules/' . $arguments['module'] : sfConfig::get( 'sf_app_module_dir' ) . '/' . $arguments['module'];

        // copy our generated module
        $this->getFilesystem()->mirror( $tmpDir . DIRECTORY_SEPARATOR . 'auto' . ucfirst( $arguments['module'] ), $moduleDir, sfFinder::type( 'any' ) );

        if ( !$options['with-show'] )
        {
            $this->getFilesystem()->remove( $moduleDir . '/templates/showSuccess.php' );
        }

        // create module definition
        //$this->getFilesystem()->copy( $moduleDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'moduleDefinition.php', $moduleDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Plugin' . ucfirst( $arguments['module'] ) . 'ModuleDefinition.class.php' );
        //$this->getFilesystem()->remove( $moduleDir . '/config/moduleDefinition.php' );

        // change module name
        $finder = sfFinder::type( 'file' )->name( '*.php' );
        $this->getFilesystem()->replaceTokens( $finder->in( $moduleDir ), '', '', array( 'auto' . ucfirst( $arguments['module'] ) => $arguments['module'] ) );

        // customize php and yml files
        $finder = sfFinder::type( 'file' )->name( '*.php', '*.yml' );
        $this->getFilesystem()->replaceTokens( $finder->in( $moduleDir ), '##', '##', $this->constants );

        // create basic test
        $this->getFilesystem()->copy( sfConfig::get( 'sf_symfony_lib_dir' ) . DIRECTORY_SEPARATOR . 'task' . DIRECTORY_SEPARATOR . 'generator' . DIRECTORY_SEPARATOR . 'skeleton' . DIRECTORY_SEPARATOR . 'module'.DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'actionsTest.php', sfConfig::get( 'sf_test_dir' ) . DIRECTORY_SEPARATOR . 'functional' . DIRECTORY_SEPARATOR . $arguments['application'] . DIRECTORY_SEPARATOR . $arguments['module'] . 'ActionsTest.php' );

        // customize test file
        $this->getFilesystem()->replaceTokens( sfConfig::get( 'sf_test_dir' ) . DIRECTORY_SEPARATOR . 'functional' . DIRECTORY_SEPARATOR . $arguments['application'] . DIRECTORY_SEPARATOR . $arguments['module'] . 'ActionsTest.php', '##', '##', $this->constants );

        // delete temp files
        $this->getFilesystem()->remove( sfFinder::type( 'any' )->in( $tmpDir ) );

    } // dinDoctrineGenerateModuleTask::executeGenerate()


    protected function executeInit( $arguments = array(), $options = array() )
    {

        $moduleDir = $options['plugin'] ? sfConfig::get( 'sf_plugins_dir' ) . '/' . $options['plugin'] . '/modules/' . $arguments['module'] : sfConfig::get( 'sf_app_module_dir' ) . '/' . $arguments['module'];

        // create basic application structure
        $finder = sfFinder::type( 'any' )->discard( '.sf' );
        $dirs = $this->configuration->getGeneratorSkeletonDirs( 'sfDoctrineModule', $options['theme'] );

        foreach ( $dirs as $dir )
        {
            if ( is_dir( $dir ) )
            {
                $this->getFilesystem()->mirror( $dir, $moduleDir, $finder );
                break;
            }
        }

        // move configuration file
        if ( file_exists( $config = $moduleDir . '/lib/configuration.php' ) )
        {
            if ( file_exists( $target = $moduleDir . '/lib/' . $arguments['module'] . 'GeneratorConfiguration.php' ) )
            {
                $this->getFilesystem()->remove( $config );
            }
            else
            {
                $this->getFilesystem()->rename( $config, $target );
            }
        }

        // move helper file
        if ( file_exists( $config = $moduleDir . '/lib/helper.php' ) )
        {
            if ( file_exists( $target = $moduleDir . '/lib/' . $arguments['module'] . 'GeneratorHelper.php' ) )
            {
                $this->getFilesystem()->remove( $config );
            }
            else
            {
                $this->getFilesystem()->rename( $config, $target );
            }
        }

        // create module definition
        //if ( file_exists( $config = $moduleDir . '/config/moduleDefinition.php' ) )
        //{
        //    if ( file_exists( $target = $moduleDir . '/config/Plugin' . ucfirst( $arguments['module'] ) . 'ModuleDefinition.class.php' ) )
        //    {
        //        $this->getFilesystem()->remove( $config );
        //    }
        //    else
        //    {
        //        $this->getFilesystem()->rename( $config, $target );
        //    }
        //}

        // create lang file
        if ( file_exists( $config = $moduleDir . '/i18n/lang.ru.xml' ) )
        {
            if ( file_exists( $target = $moduleDir . '/i18n/' . $arguments['module'] . '.ru.xml' ) )
            {
                $this->getFilesystem()->remove( $config );
            }
            else
            {
                $this->getFilesystem()->rename( $config, $target );
            }
        }

        // create basic test
        $this->getFilesystem()->copy(
            sfConfig::get( 'sf_symfony_lib_dir' ) . DIRECTORY_SEPARATOR . 'task'
            . DIRECTORY_SEPARATOR . 'generator' . DIRECTORY_SEPARATOR . 'skeleton'
            . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR
            . 'actionsTest.php',
            sfConfig::get( 'sf_test_dir' ) . DIRECTORY_SEPARATOR . 'functional'
            . DIRECTORY_SEPARATOR . $arguments['application'] . DIRECTORY_SEPARATOR
            . $arguments['module'] . 'ActionsTest.php'
        );

        // customize test file
        $this->getFilesystem()->replaceTokens(
            sfConfig::get( 'sf_test_dir' ) . DIRECTORY_SEPARATOR . 'functional'
            . DIRECTORY_SEPARATOR . $arguments['application'] . DIRECTORY_SEPARATOR
            . $arguments['module'] . 'ActionsTest.php', '##', '##', $this->constants
        );

        // customize php and yml files
        $finder = sfFinder::type( 'file' )->name( '*.php', '*.yml', '*.xml' );

        $this->constants['WITH_SHOW'] = $options['with-show'] ? 'true' : 'false';
        $this->constants['ROUTE_PREFIX'] = $options['route-prefix'] ? "'" . $options['route-prefix'] . "'" : 'null';
        $this->constants['SINGULAR'] = $options['singular'] ? "'" . $options['singular'] . "'" : 'null';
        $this->constants['PLURAL'] = $options['plural'] ? "'" . $options['plural'] . "'" : 'null';
        $this->constants['THEME'] = $options['theme'];
        $this->constants['VERBOSE'] = $options['non-verbose-templates'] ? 'true' : 'false';
        $this->constants['WITH_ROUTE'] = $options['with-doctrine-route'] ? 'true' : 'false';
        $this->constants['BASE_ACTION'] = $options['actions-base-class'];

        $this->getFilesystem()->replaceTokens( $finder->in( $moduleDir ), '##', '##', $this->constants );

    } // dinDoctrineGenerateModuleTask

} // dinDoctrineGenerateModuleTask

//EOF