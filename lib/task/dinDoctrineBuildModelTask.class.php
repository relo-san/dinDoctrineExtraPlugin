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
 * dinDoctrineBuildModelTask
 * 
 * @package     dinDoctrineExtraPlugin
 * @subpackage  lib.task
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class dinDoctrineBuildModelTask extends sfDoctrineBaseTask
{

    /**
     * Configure task
     * 
     * @return  void
     */
    protected function configure()
    {

        $this->addOptions( array(
            new sfCommandOption(
                'application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name',
                true
            ),
            new sfCommandOption(
                'env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'
            )
        ) );

        $this->namespace = 'doctrine-ext';
        $this->name = 'build-model';
        $this->briefDescription = 'Creates classes for the current model';

        $d[] = 'The [doctrine:build-model|INFO] task creates model classes from the schema:';
        $d[] = '';
        $d[] = '  [./symfony doctrine:build-model|INFO]';
        $d[] = '';
        $d[] = 'The task read the schema information in [config/doctrine/*.yml|COMMENT]';
        $d[] = 'from the project and all enabled plugins.';
        $d[] = '';
        $d[] = 'The model classes files are created in [lib/model/doctrine|COMMENT].';
        $d[] = '';
        $d[] = 'This task never overrides custom classes in [lib/model/doctrine|COMMENT].';
        $d[] = 'It only replaces files in [lib/model/doctrine/base|COMMENT].';
        $this->detailedDescription = implode( "\n", $d );

    } // dinDoctrineBuildModelTask::configure()


    /**
     * Execute task
     * 
     * @param   array   $arguments  Task arguments [optional]
     * @param   array   $options    Task options [optional]
     * @return  void
     */
    protected function execute( $arguments = array(), $options = array() )
    {

        $this->logSection( 'doctrine', 'generating model classes' );

        $config = $this->getCliConfig();
        $builderOptions = $this->configuration->getPluginConfiguration( 'sfDoctrinePlugin' )
            ->getModelBuilderOptions();

        $finder = sfFinder::type( 'file' )->prune( 'base' )->name( '*' . $builderOptions['suffix'] );
        $before = $finder->in( $config['models_path'] );

        $schema = $this->prepareSchemaFile( $config['yaml_schema_path'] );

        $import = new Doctrine_Import_Schema();
        $import->setOptions( $builderOptions );
        $import->importSchema( $schema, 'yml', $config['models_path'] );

        $base = $builderOptions['baseClassesDirectory'];
        $suff = $builderOptions['suffix'];
        $src = array( '##PACKAGE##', '##SUBPACKAGE##', '##NAME##', ' <##EMAIL##>' );

        // markup base classes with magic methods
        foreach ( sfYaml::load( $schema ) as $model => $definition )
        {

            $package = isset( $definition['package'] )
                ? substr( $definition['package'], 0, strpos( $definition['package'], '.' ) ) : '';
            $basePath = $config['models_path'] . ( $package ? '/' . $package : '' ) . '/';

            $file = $basePath . $base . '/Base' . $model . $suff;
            $code = file_get_contents( $file );

            // introspect the model without loading the class
            if ( preg_match_all( '/@property (\w+) \$(\w+)/', $code, $matches, PREG_SET_ORDER ) )
            {
                $properties = array();
                foreach ( $matches as $match )
                {
                    $properties[$match[2]] = $match[1];
                }

                $typePad = max( array_map(
                    'strlen', array_merge( array_values( $properties ), array( $model ) )
                ) );
                $namePad = max( array_map(
                    'strlen', array_keys( array_map( array( 'sfInflector', 'camelize' ), $properties ) )
                ) );
                $setters = array();
                $getters = array();

                foreach ( $properties as $name => $type )
                {
                    $camelized = sfInflector::camelize( $name );
                    $collection = 'Doctrine_Collection' == $type;

                    $getters[] = sprintf(
                        '@method %-' . $typePad . 's %s%-' . ( $namePad + 2 )
                            . 's Returns the current record\'s "%s" %s',
                        $type, 'get', $camelized . '()', $name, $collection ? 'collection' : 'value'
                    );
                    $setters[] = sprintf(
                        '@method %-' . $typePad . 's %s%-' . ( $namePad + 2 )
                            . 's Sets the current record\'s "%s" %s',
                        $model, 'set', $camelized . '()', $name, $collection ? 'collection' : 'value'
                    );

                }

                $dst = array(
                    dinGeneratorSigner::getProjectName(),
                    'lib.model.doctrine' . ( $package ? '.' . $package : '' ) . '.base',
                    dinGeneratorSigner::getAuthor(), ''
                );

                $code = str_replace(
                    $match[0],
                    $match[0] . PHP_EOL . ' * ' . PHP_EOL . ' * ' . implode( PHP_EOL . ' * ',
                    array_merge( $getters, $setters ) ), $code
                );
                $code = str_replace( $src, $dst, $code );
                file_put_contents( $file, $code );
            }

            $this->replaceLibClasses( $basePath, $model, $package, $suff );
            if ( $package )
            {
                $basePath = $this->configuration->getPluginConfiguration( $package )
                    ->getRootDir() . '/lib/model/doctrine/';
                $this->replaceLibClasses( $basePath, $model, $package, $suff, true );
            }

        }

        $this->reloadAutoload();

    } // dinDoctrineBuildModelTask::execute()


    /**
     * Merges and configure all project and plugin schema files into one
     * 
     * @param   string  $yamlSchemaPath Project schema path
     * @return  string  Absolute path to the consolidated schema file
     */
    protected function prepareSchemaFile( $yamlSchemaPath )
    {

        $models = array();
        $finder = sfFinder::type( 'file' )->name( '*.yml' )->sort_by_name()->follow_link();
        $config = dinGeneratorModelConfig::getInstance( $this->configuration );

        foreach ( $this->configuration->getPlugins() as $name )
        {
            $plugin = $this->configuration->getPluginConfiguration( $name );
            foreach ( $finder->in( $plugin->getRootDir() . '/config/doctrine' ) as $schema )
            {
                $pluginModels = (array) sfYaml::load( $schema );
                $globals = $this->filterSchemaGlobals( $pluginModels );

                foreach ( $pluginModels as $model => $definition )
                {
                    $definition = $this->canonicalizeModelDefinition( $model, $definition );
                    $definition = array_merge( $globals, $definition );

                    $models[$model] = isset( $models[$model] )
                        ? sfToolkit::arrayDeepMerge( $models[$model], $definition ) : $definition;

                    if ( !isset( $models[$model]['package'] ) )
                    {
                        $models[$model]['package'] = $plugin->getName() . '.lib.model.doctrine';
                    }

                    if ( !isset( $models[$model]['package_custom_path'] )
                        && 0 === strpos( $models[$model]['package'], $plugin->getName() ) )
                    {
                        $models[$model]['package_custom_path'] = $plugin->getRootDir()
                            . '/lib/model/doctrine';
                    }
                }
            }
        }

        foreach ( $finder->in( $yamlSchemaPath ) as $schema )
        {
            $projectModels = (array) sfYaml::load( $schema );
            $globals = $this->filterSchemaGlobals( $projectModels );

            foreach ( $projectModels as $model => $definition )
            {
                $definition = $this->canonicalizeModelDefinition( $model, $definition );
                $definition = array_merge( $globals, $definition );

                $models[$model] = isset( $models[$model] )
                    ? sfToolkit::arrayDeepMerge( $models[$model], $definition ) : $definition;
            }
        }

        foreach ( $models as $model => $definition )
        {
            $models[$model] = $config->prepareModelDefinition( $model, $definition );
            if ( !$models[$model] )
            {
                unset( $models[$model] );
            }
        }

        $file = realpath( sys_get_temp_dir() ) . '/doctrine_schema_' . rand( 11111, 99999 ) . '.yml';
        $this->logSection( 'file+', $file );
        file_put_contents( $file, sfYaml::dump( $models, 4 ) );

        return $file;

    } // dinDoctrineBuildModelTask::prepareSchemaFile()


    /**
     * Replace tokens in project model classes
     * 
     * @param   string  $libDir     Base directory for model classes
     * @param   string  $model      Model name
     * @param   string  $package    Package name
     * @param   string  $suffix     Suffix for class files
     * @param   boolean $isPlugin   Is plugin model classes pair [optional, default false]
     * @return  void
     */
    protected function replaceLibClasses( $libDir, $model, $package, $suffix, $isPlugin = false )
    {

        // record class file
        $recFile = $libDir . ( $isPlugin ? 'Plugin' : '' ) . $model . $suffix;
        $code = str_replace( "\r\n", "\n", file_get_contents( $recFile ) );
        $eol = "\n";

        $pt = '|/\*\*' . $eol . ' \* ([\w]+)' . $eol . ' \* ' . $eol
            . ' \* This class has been auto-generated by the Doctrine ORM Framework' . $eol
            . ' \* ' . $eol . ' \* @package    ##PACKAGE##.* \*/|Uumsi';
        preg_match( $pt, $code, $m );
        if ( $m )
        {
            $header = ( $isPlugin ? dinGeneratorSigner::getPluginHeader( $package )
                : dinGeneratorSigner::getHeader() ) . $eol . $eol . '/**' . $eol
                . ' * ' . ( $isPlugin ? 'Plugin c' : 'C' ) . 'lass that represents a record of '
                . $model . ' model' . $eol . ' * ' . $eol . ' * @package     '
                . ( $isPlugin ? $package : dinGeneratorSigner::getProjectName() ) . $eol
                . ' * @subpackage  lib.model.doctrine'
                . ( ( !$isPlugin && $package ) ? '.' . $package : '' )
                . $eol . ' * @author      ' . dinGeneratorSigner::getAuthor() . $eol . ' */';
            $code = str_replace( $m[0], $header, $code );

            $pt = '|class ([\w]+) extends ([\w]+)' . $eol . '{' . $eol . $eol . '}|Uumsi';
            preg_match( $pt, $code, $m );
            if ( $m )
            {
                $cont = 'class ' . ( $isPlugin ? 'Plugin' : '' ) . $model . ' extends '
                    . ( $isPlugin ? 'Base' : 'Plugin' ) . $model . $eol
                    . '{' . $eol . '} // ' . ( $isPlugin ? 'Plugin' : '' ) . $model . $eol
                    . $eol . '//EOF';
                $code = str_replace( $m[0], $cont, $code );
            }
            file_put_contents( $recFile, $code );
        }

        // table class file
        $tblFile = $libDir . ( $isPlugin ? 'Plugin' : '' ) . $model . 'Table' . $suffix;
        $code = str_replace( "\r\n", "\n", file_get_contents( $tblFile ) );

        $pt = '|/\*\*' . $eol . ' \* ([\w]+)' . $eol . ' \* ' . $eol
            . ' \* This class has been auto-generated by the Doctrine ORM Framework' . $eol
            . ' \*/|Uumsi';
        preg_match( $pt, $code, $m );
        if ( $m )
        {
            $header = ( $isPlugin ? dinGeneratorSigner::getPluginHeader( $package )
                : dinGeneratorSigner::getHeader() ) . $eol . $eol . '/**' . $eol
                . ' * ' . ( $isPlugin ? 'Plugin c' : 'C' )
                . 'lass for performing query and update operations for ' . $model . ' model table'
                . $eol . ' * ' . $eol . ' * @package     '
                . ( $isPlugin ? $package : dinGeneratorSigner::getProjectName() ) . $eol
                . ' * @subpackage  lib.model.doctrine'
                . ( ( !$isPlugin && $package ) ? '.' . $package : '' )
                . $eol . ' * @author      ' . dinGeneratorSigner::getAuthor() . $eol . ' */';
            $code = str_replace( $m[0], $header, $code );

            $pt = '|class ([\w]+) extends ([\w]+)' . $eol . '{' . $eol . '    /\*\*' . $eol
                . '     \* Returns an instance of this class\.' . $eol . '     \*' . $eol
                . '     \* @return object ([\w]+)' . $eol . '.*    }' . $eol . '}|Uumsi';
            preg_match( $pt, $code, $m );
            if ( $m )
            {
                $cont = 'class ' . ( $isPlugin ? 'Plugin' : '' ) . $model . 'Table extends '
                    . ( $isPlugin ? $m[2] : 'Plugin' . $model . 'Table' ) . $eol . '{'
                    . $eol . $eol . '    /**' . $eol . '     * Returns an instance of '
                    . ( $isPlugin ? 'Plugin' : '' ) . $model . 'Table' . $eol . '     * ' . $eol
                    . '     * @return  ' . ( $isPlugin ? 'Plugin' : '' ) . $model . 'Table' . $eol
                    . '     */' . $eol . '    public static function getInstance()'
                    . $eol . '    {' . $eol . $eol . "        return Doctrine_Core::getTable( '"
                    . ( $isPlugin ? 'Plugin' : '' ) . $model . "' );" . $eol . $eol . '    } // '
                    . ( $isPlugin ? 'Plugin' : '' ) . $model . 'Table::getInstance()' . $eol . $eol
                    . '} // ' . ( $isPlugin ? 'Plugin' : '' ) . $model . 'Table' . $eol . $eol
                    . '//EOF';
                $code = str_replace( $m[0], $cont, $code );
            }
            file_put_contents( $tblFile, $code );
        }

    } // dinDoctrineBuildModelTask::replaceLibClasses()

} // dinDoctrineBuildModelTask

//EOF