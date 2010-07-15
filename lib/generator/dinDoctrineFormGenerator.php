<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Form generator for Doctrine
 * 
 * @package     dinDoctrineExtraPlugin
 * @subpackage  lib.generator
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinDoctrineFormGenerator extends sfDoctrineFormGenerator
{

    protected
        $options,
        $config;


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
        $this->setGeneratorClass( 'dinDoctrineForm' );
        $this->config = dinGeneratorModelConfig::getInstance( $generatorManager->getConfiguration() );

    } // dinDoctrineFormGenerator::initialize()


    /**
     * Generate form classes
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

        // create the project base form class
        $file = $libDir . '/form/doctrine/BaseFormDoctrine.php';
        if ( !file_exists( $file ) && !file_exists( $libDir . '/form/doctrine/BaseFormDoctrine.class.php' ) )
        {
            if ( !is_dir( $directory = dirname( $file ) ) )
            {
                mkdir( $directory, 0777, true );
            }
            file_put_contents( $file, $this->evalTemplate( 'dinDoctrineFormBaseTemplate.php' ) );
        }

        // create a form class for every Doctrine class
        foreach ( $models as $model )
        {
            $this->table = Doctrine_Core::getTable( $model );
            $this->modelName = $model;
            $formDir = $libDir . '/form/doctrine';
            $isPluginModel = $this->isPluginModel( $model );
            if ( $isPluginModel )
            {
                $pluginName = $this->getPluginNameForModel( $model );
                $formDir .= '/' . $pluginName;
            }

            if ( !$this->config->allowForm( $model ) )
            {
                continue;
            }

            // generate base class form for model
            if ( !is_dir( $formDir . '/base' ) )
            {
                mkdir( $formDir . '/base', 0777, true );
            }
            file_put_contents( $formDir . '/base/Base' . $model . 'Form.php', $this->evalTemplate( null === $this->getParentModel() ? 'dinDoctrineFormGeneratedTemplate.php' : 'dinDoctrineFormGeneratedInheritanceTemplate.php' ) );

            // generate plugin class form if needed
            $strategy = $this->config->getFormPluginStrategy( $model );
            if ( $isPluginModel && $strategy )
            {
                $pluginBaseDir = $pluginPaths[$pluginName] . '/lib/form/doctrine';
                if ( !file_exists( $classFile = $pluginBaseDir . '/Plugin' . $model . 'Form.php' )
                    && !file_exists( $pluginBaseDir . '/Plugin' . $model . 'Form.class.php' ) )
                {
                    if ( $strategy != 'exist' )
                    {
                        if ( !is_dir( $pluginBaseDir ) )
                        {
                            mkdir( $pluginBaseDir, 0777, true );
                        }
                        file_put_contents( $classFile, $this->evalTemplate( 'dinDoctrineFormPluginTemplate.php' ) );
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
            if ( !file_exists( $classFile = $formDir . '/' . $model . 'Form.php' )
                && !file_exists( $formDir . '/' . $model . 'Form.class.php' ) )
            {
                if ( $isPluginModel && $strategy && $isPluginForm )
                {
                    file_put_contents( $classFile, $this->evalTemplate( 'dinDoctrinePluginFormTemplate.php' ) );
                }
                else
                {
                    file_put_contents( $classFile, $this->evalTemplate( 'dinDoctrineFormTemplate.php' ) );
                }
            }
        }

    } // dinDoctrineFormGenerator::generate()







    
    /**
     * Returns a PHP string representing options to pass to a widget for a given column
     * 
     * @param   sfDoctrineColumn    $column
     * @return  string  The options to pass to the widget as a PHP string
     * @author  relo_san
     * @since   february 2, 2010
     * @see     sfDoctrineFormGenerator::getWidgetOptionsForColumn()
     */
    public function getWidgetOptionsForColumn( $column )
    {

        $options = array();

        if ( $column->isForeignKey() )
        {
            $options[] = sprintf( '\'model\' => $this->getRelatedModelName( \'%s\' )', $column->getRelationKey( 'alias' ) );
            $options[] = sprintf( '\'add_empty\' => %s', $column->isNotNull() ? 'false' : 'true' );
        }
        
        else if ( 'enum' == $column->getDoctrineType() && is_subclass_of( $this->getWidgetClassForColumn( $column ), 'sfWidgetFormChoiceBase' ) )
        {
            $options[] = "'choices' => " . $this->arrayExport( array_combine( $column['values'], $column['values'] ) );
        }

        if ( !$column->isPrimaryKey() )
        {
            $camName = sfInflector::camelize( $column->getName() );
            $options[] = "'label' => 'formLabels." . strtolower( substr( $camName, 0, 1 ) ) . substr( $camName, 1 ) . "'";
        }

        return count( $options ) ? sprintf( ' array( %s ) ', implode( ', ', $options ) ) : '';

    } // dinDoctrineFormGenerator::getWidgetOptionsForColumn()


    /**
     * Returns a PHP string representing options to pass to a validator for a given column
     * 
     * @param   sfDoctrineColumn    $column
     * @return  The options to pass to the validator as a PHP string
     * @author  relo_san
     * @since   february 2, 2010
     * @see     sfDoctrineFormGenerator::getValidatorOptionsForColumn()
     */
    public function getValidatorOptionsForColumn( $column )
    {

        $options = array();

        if ( $column->isForeignKey() )
        {
            $options[] = sprintf( '\'model\' => $this->getRelatedModelName(\'%s\')', $column->getRelationKey( 'alias' ) );
        }
        else if ( $column->isPrimaryKey() )
        {
            $options[] = sprintf( '\'choices\' => array( $this->getObject()->get( \'%s\' ) ), \'empty_value\' => $this->getObject()->get( \'%1$s\' )', $column->getFieldName() );
        }
        else
        {
            switch ( $column->getDoctrineType() )
            {
                case 'string':
                    if ( $column['length'] )
                    {
                        $options[] = sprintf( '\'max_length\' => %s', $column['length'] );
                    }
                    if ( isset( $column['minlength'] ) )
                    {
                        $options[] = sprintf( '\'min_length\' => %s', $column['minlength'] );
                    }
                    if ( isset( $column['regexp'] ) )
                    {
                        $options[] = sprintf( '\'pattern\' => \'%s\'', $column['regexp'] );
                    }
                    break;
                case 'enum':
                    $options[] = "'choices' => " . $this->arrayExport( $column['values'] );
                    break;
              }
        }

        // If notnull = false, is a primary or the column has a default value then
        // make the widget not required
        if ( !$column->isNotNull() || $column->isPrimaryKey() || $column->hasDefinitionKey( 'default' ) )
        {
            $options[] = '\'required\' => false';
            if ( !$column->isNotNull() && !$column->isPrimaryKey() )
            {
                $options[] = '\'empty_value\' => null';
            }
        }

        return count( $options ) ? sprintf( ' array( %s ) ', implode( ', ', $options ) ) : '';

    } // dinDoctrineFormGenerator::getValidatorOptionsForColumn()


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

    } // dinDoctrineFormGenerator::getPluginModels()


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

    } // dinDoctrineFormGenerator::loadModels()


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

        if ( !isset( $this->options['form_dir_name'] ) )
        {
            $this->options['form_dir_name'] = 'form';
        }

    } // dinDoctrineFormGenerator::addOptions()

} // dinDoctrineFormGenerator

//EOF