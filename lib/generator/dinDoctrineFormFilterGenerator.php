<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Filter generator for doctrine
 * 
 * @package     dinDoctrineExtraPlugin
 * @subpackage  lib.generator
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinDoctrineFormFilterGenerator extends sfDoctrineFormFilterGenerator
{

    /**
     * Initialize generator
     * 
     * @param   sfGeneratorManager
     * @return  void
     */
    public function initialize( sfGeneratorManager $generatorManager )
    {

        parent::initialize( $generatorManager );

        $this->getPluginModels();
        $this->setGeneratorClass( 'dinDoctrineFormFilter' );
        $this->config = dinGeneratorModelConfig::getInstance( $generatorManager->getConfiguration() );

    } // dinDoctrineFormFilterGenerator::initialize()


    /**
     * Generate filter form classes
     * 
     * @param   array   $options    Generator options [optional]
     * @return  void
     */
    public function generate( $options = array() )
    {

        $this->addOptions( $options );
        $models = $this->loadModels();
        $pluginPaths = $this->generatorManager->getConfiguration()->getAllPluginPaths();
        $libDir = sfConfig::get( 'sf_lib_dir' );

        // create the project base filter form class
        $file = $libDir . '/filter/doctrine/BaseFormFilterDoctrine.php';
        if ( !file_exists( $file ) && !file_exists( $libDir . '/filter/doctrine/BaseFormFilterDoctrine.class.php' ) )
        {
            if ( !is_dir( $directory = dirname( $file ) ) )
            {
                mkdir( $directory, 0777, true );
            }
            file_put_contents( $file, $this->evalTemplate( 'dinDoctrineFormFilterBaseTemplate.php' ) );
        }

        // create a filter form class for every Doctrine class
        foreach ( $models as $model )
        {
            $this->table = Doctrine_Core::getTable( $model );
            $this->modelName = $model;
            $filterDir = $libDir . '/filter/doctrine';
            $isPluginModel = $this->isPluginModel( $model );
            if ( $isPluginModel )
            {
                $pluginName = $this->getPluginNameForModel( $model );
                $filterDir .= '/' . $pluginName;
            }

            if ( !$this->config->allowFilter( $model ) )
            {
                continue;
            }

            // generate base class filter form for model
            if ( !is_dir( $filterDir . '/base' ) )
            {
                mkdir( $filterDir . '/base', 0777, true );
            }
            file_put_contents( $filterDir . '/base/Base' . $model . 'FormFilter.php', $this->evalTemplate( null === $this->getParentModel() ? 'dinDoctrineFormFilterGeneratedTemplate.php' : 'dinDoctrineFormFilterGeneratedInheritanceTemplate.php' ) );

            // generate plugin class form if needed
            $strategy = $this->config->getFilterPluginStrategy( $model );
            if ( $isPluginModel && $strategy )
            {
                $pluginBaseDir = $pluginPaths[$pluginName] . '/lib/filter/doctrine';
                if ( !file_exists( $classFile = $pluginBaseDir . '/Plugin' . $model . 'FormFilter.php' )
                    && !file_exists( $pluginBaseDir . '/Plugin' . $model . 'FormFilter.class.php' ) )
                {
                    if ( $strategy != 'exist' )
                    {
                        if ( !is_dir( $pluginBaseDir ) )
                        {
                            mkdir( $pluginBaseDir, 0777, true );
                        }
                        file_put_contents( $classFile, $this->evalTemplate( 'dinDoctrineFormFilterPluginTemplate.php' ) );
                        $isPluginForm = true;
                    }
                    else
                    {
                        $isPluginForm = false;
                    }
                }
                else
                {
                    $isPluginForm = true;
                }
            }

            // generate project class form
            if ( !file_exists( $classFile = $filterDir . '/' . $model . 'FormFilter.php' )
                && !file_exists( $filterDir . '/' . $model . 'FormFilter.class.php' ) )
            {
                if ( $isPluginModel && $strategy && $isPluginForm )
                {
                    file_put_contents( $classFile, $this->evalTemplate( 'dinDoctrinePluginFormFilterTemplate.php' ) );
                }
                else
                {
                    file_put_contents( $classFile, $this->evalTemplate( 'dinDoctrineFormFilterTemplate.php' ) );
                }
            }
        }

    } // dinDoctrineFormFilterGenerator::generate()


    /**
     * Returns a PHP string representing options to pass to a widget for a given column
     * 
     * @param   sfDoctrineColumn    $column
     * @return  string  The options to pass to the widget as a PHP string
     * @author  relo_san
     * @since   february 7, 2010
     * @see     sfDoctrineFormFilterGenerator::getWidgetOptionsForColumn()
     */
    public function getWidgetOptionsForColumn( $column )
    {

        $options = array();
        $withEmpty = $column->isNotNull() && !$column->isForeignKey() ? array( "'with_empty' => false" ) : array();
        if ( !$withEmpty && !$column->isForeignKey() )
        {
            $options[] = "'empty_label' => 'labels.isEmpty'";
            $options[] = "'template' => '%input% <div class=\"sf-filter-empty\">%empty_checkbox% %empty_label%</div>'";
        }

        switch ( $column->getDoctrineType() )
        {
            case 'boolean':
                $options[] = "'choices' => array( '' => 'labels.yesOrNo', 1 => 'labels.yes', 0 => 'labels.no' )";
                break;
            case 'date':
            case 'datetime':
            case 'timestamp':
                $options[] = "'from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate()";
                $options = array_merge( $options, $withEmpty );
                break;
            case 'enum':
                $values = array( '' => '' );
                $values = array_merge( $values, $column['values'] );
                $values = array_combine( $values, $values );
                $options[] = "'choices' => " . $this->arrayExport( $values );
                break;
            default:
                $options = array_merge( $options, $withEmpty );
        }

        
        if ( $column->isForeignKey() )
        {
            $options[] = sprintf( '\'model\' => $this->getRelatedModelName( \'%s\' )', $column->getRelationKey( 'alias' ) );
            $options[] = "'add_empty' => true";
        }
        else
        {
            switch ( $column->getDoctrineType() )
            {
                case 'enum':
                    $values = $column->getDefinitionKey( 'values' );
                    $values = array_combine( $values, $values );
                    $options[] = "'choices' => " . str_replace( "\n", '', $this->arrayExport( $values ) );
                    break;
            }
        }

        $camName = sfInflector::camelize( $column->getName() );
        $options[] = "'label' => 'formLabels." . strtolower( substr( $camName, 0, 1 ) ) . substr( $camName, 1 ) . "'";

        return count( $options ) ? sprintf( ' array( %s ) ', implode( ', ', $options ) ) : '';

    } // dinDoctrineFormFilterGenerator::getWidgetOptionsForColumn()


    /**
     * Get plugin models
     * 
     * @return  array   Plugin models
     */
    public function getPluginModels()
    {

        if ( $this->pluginModels )
        {
            return $this->pluginModels;
        }

        $plugins     = $this->generatorManager->getConfiguration()->getPlugins();
        $pluginPaths = $this->generatorManager->getConfiguration()->getAllPluginPaths();

        foreach ( $pluginPaths as $pluginName => $path )
        {
            if ( !in_array( $pluginName, $plugins ) )
            {
                continue;
            }

            foreach ( sfFinder::type( 'file' )->name( '*.php' )->in( $path . '/lib/model/doctrine' ) as $path )
            {
                $info = pathinfo( $path );
                $e = explode( '.', $info['filename'] );
                $modelName = substr( $e[0], 6, strlen( $e[0] ) );

                if ( class_exists( $modelName ) && class_exists( $e[0] ) )
                {
                    $parent = new ReflectionClass( 'Doctrine_Record' );
                    $reflection = new ReflectionClass( $modelName );
                    if ( $reflection->isSubClassOf( $parent ) )
                    {
                        $this->pluginModels[$modelName] = $pluginName;
                        $generators = Doctrine_Core::getTable( $modelName )->getGenerators();
                        foreach ( $generators as $generator )
                        {
                            $this->pluginModels[$generator->getOption( 'className' )] = $pluginName;
                        }
                    }
                }
            }
        }

        return $this->pluginModels;

    } // dinDoctrineFormFilterGenerator::getPluginModels()


    /**
     * Load builders
     * 
     * @return  array   Loaded models
     */
    protected function loadModels()
    {

        Doctrine_Core::loadModels( array( sfConfig::get( 'sf_lib_dir' ) . '/model' ) );
        $this->models = $this->filterModels( Doctrine_Core::filterInvalidModels(
            Doctrine_Core::initializeModels( Doctrine_Core::getLoadedModels() )
        ) );
        return $this->models;

    } // dinDoctrineFormFilterGenerator::loadModels()


    /**
     * Add generator options
     * 
     * @return  void
     */
    protected function addOptions( $options )
    {

        $this->options = $options;

        if ( !isset( $this->options['model_dir_name'] ) )
        {
            $this->options['model_dir_name'] = 'model';
        }

        if ( !isset( $this->options['filter_dir_name'] ) )
        {
            $this->options['filter_dir_name'] = 'filter';
        }

    } // dinDoctrineFormFilterGenerator::addOptions()

} // dinDoctrineFormFilterGenerator

//EOF