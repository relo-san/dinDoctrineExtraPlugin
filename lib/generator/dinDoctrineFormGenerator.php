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
        $config,
        $widgetClasses = array(
            'boolean'   => 'sfWidgetFormInputCheckbox',
            'blob'      => 'sfWidgetFormTextarea',
            'clob'      => 'sfWidgetFormTextarea',
            'date'      => 'sfWidgetFormDate',
            'time'      => 'sfWidgetFormTime',
            'datetime'  => 'sfWidgetFormDateTime',
            'timestamp' => 'sfWidgetFormDateTime',
            'enum'      => 'sfWidgetFormChoice'
        ),
        $validatorClasses = array(
            'boolean'   => 'sfValidatorBoolean',
            'string'    => 'sfValidatorString',
            'clob'      => 'sfValidatorString',
            'blob'      => 'sfValidatorString',
            'float'     => 'sfValidatorNumber',
            'decimal'   => 'sfValidatorNumber',
            'integer'   => 'sfValidatorInteger',
            'date'      => 'sfValidatorDate',
            'time'      => 'sfValidatorTime',
            'datetime'  => 'sfValidatorDateTime',
            'timestamp' => 'sfValidatorDateTime',
            'enum'      => 'sfValidatorChoice',
        );


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
     * Get columns, associated with form
     * 
     * @return  array   Columns
     */
    public function getColumns()
    {

        $parentModel = $this->getParentModel();
        $parentColumns = $parentModel
            ? array_keys( Doctrine_Core::getTable( $parentModel )->getColumns() )
            : array();
        $columns = array();

        foreach ( array_diff( array_keys( $this->table->getColumns() + $this->config->getVirtualColumns( $this->table->getComponentName(), 'form' ) ), $parentColumns ) as $name )
        {
            if ( $this->config->allowColumn( $this->table->getComponentName(), $name, 'form' ) )
            {
                $columns[] = new sfDoctrineColumn( $name, $this->table );
            }
        }

        return $columns;

    } // dinDoctrineFormGenerator::getColumns()


    /**
     * Returns an array of relations that represents a many to many relationship.
     * 
     * @return  array   An array of relations
     */
    public function getManyToManyRelations()
    {

        $relations = array();
        foreach ( $this->table->getRelations() as $relation )
        {
            if ( Doctrine_Relation::MANY == $relation->getType() && isset( $relation['refTable'] )
                && ( null === $this->getParentModel() || !Doctrine_Core::getTable( $this->getParentModel() )->hasRelation( $relation->getAlias() ) )
                && $this->config->allowRelation( $this->table->getComponentName(), $relation->getAlias(), 'form' ) )
            {
                $relations[] = $relation;
            }
        }

        return $relations;

    } // dinDoctrineFormGenerator::getManyToManyRelations()


    /**
     * Get widget class for column
     * 
     * @param   sfDoctrineColumn    $column
     * @return  string  Name of widget class
     */
    public function getWidgetClassForColumn( $column )
    {

        $type = $column->getDoctrineType();
        $default = isset( $this->widgetClasses[$type] )
            ? $this->widgetClasses[$type]
            : 'sfWidgetFormInputText';

        if ( $type == 'string' && ( is_null( $column->getLength() ) || $column->getLength() > 255 ) )
        {
            $default = 'sfWidgetFormTextarea';
        }

        $name = $this->config->getFormWidgetClass(
            $this->modelName, $column->getName(), $type, $default
        );

        if ( $column->isPrimaryKey() )
        {
            $name = 'sfWidgetFormInputHidden';
        }
        else if ( $column->isForeignKey() )
        {
            $name = 'sfWidgetFormDoctrineChoice';
        }

        $name = $this->config->getFormWidgetClass(
            $this->modelName, $column->getName(), $type, $name, true
        );

        return $name;

    } // dinDoctrineFormGenerator::getWidgetClassForColumn()


    /**
     * Returns a PHP string representing options to pass to a widget for a given column
     * 
     * @param   sfDoctrineColumn    $column
     * @param   integer             $indent Indentation value [optional]
     * @return  string  The options to pass to the widget as a PHP string
     */
    public function getWidgetOptionsForColumn( $column, $indent = 0 )
    {

        $options = array();
        $type = $column->getDoctrineType();

        if ( $column->isForeignKey() )
        {
            $options['model'] = "\$this->getRelatedModelName( '" . $column->getRelationKey( 'alias' ) . "' )";
            $options['add_empty'] = $column->isNotNull() ? 'false' : 'true';
        }
        else if ( $type == 'enum' && is_subclass_of( $this->getWidgetClassForColumn( $column ), 'sfWidgetFormChoiceBase' ) )
        {
            $options['choices'] = $this->arrayExport( array_combine( $column['values'], $column['values'] ) );
        }

        if ( !$column->isPrimaryKey() )
        {
            $camName = sfInflector::camelize( $column->getName() );
            $options['label'] = "'formLabels." . strtolower( substr( $camName, 0, 1 ) ) . substr( $camName, 1 ) . "'";
        }

        $options = array_merge( $options, $this->config->getFormWidgetOptions(
            $this->modelName, $column->getName(), $type, array()
        ) );

        $out = array();
        $ni = str_repeat( ' ', $indent );
        foreach ( $options as $k => $v )
        {
            if ( !is_null( $v ) )
            {
                $out[] = $ni . "    '" . $k . "' => " . $v;
            }
        }

        return count( $out ) ? " array(\n" . implode( ",\n", $out ) . "\n" . $ni .") " : '';

    } // dinDoctrineFormGenerator::getWidgetOptionsForColumn()


    /**
     * Get validator class for column
     * 
     * @param   sfDoctrineColumn    $column
     * @return  string  Name of validator class
     */
    public function getValidatorClassForColumn( $column )
    {

        $type = $column->getDoctrineType();
        $default = isset( $this->validatorClasses[$type] )
            ? $this->validatorClasses[$type]
            : 'sfValidatorPass';

        if ( $type == 'string' )
        {
            if ( $column->getDefinitionKey( 'email' ) )
            {
                $default = 'sfValidatorEmail';
            }
            else if ( $column->getDefinitionKey( 'regexp' ) )
            {
                $default = 'sfValidatorRegex';
            }
        }

        $name = $this->config->getFormValidatorClass(
            $this->modelName, $column->getName(), $type, $default
        );

        if ( $column->isForeignKey() )
        {
            $name = 'sfValidatorDoctrineChoice';
        }
        else if ( $column->isPrimaryKey() )
        {
            $name = 'sfValidatorChoice';
        }

        return $name;

    } // dinDoctrineFormGenerator::getValidatorClassForColumn()


    /**
     * Get validator options for column
     * 
     * @param   sfDoctrineColumn    $column
     * @param   integer             $indent Indentation value [optional]
     * @return  string  The options to pass to the validator as a PHP string
     */
    public function getValidatorOptionsForColumn( $column, $indent = 0 )
    {

        $type = $column->getDoctrineType();
        $options = array();

        if ( $column->isForeignKey() )
        {
            $options['model'] = "\$this->getRelatedModelName( '" . $column->getRelationKey( 'alias' ) . "' )";
        }
        else if ($column->isPrimaryKey())
        {
            $options['choices'] = "array( \$this->getObject()->get( '" . $column->getFieldName() . "' ) )";
            $options['empty_value'] = "\$this->getObject()->get( '" . $column->getFieldName() . "' )";
        }
        else
        {

            if ( $type == 'string' )
            {
                if ( $column['length'] )
                {
                    $options['max_length'] = $column['length'];
                }
                if ( isset( $column['minlength'] ) )
                {
                    $options['min_length'] = $column['minlength'];
                }
                if ( isset( $column['regexp'] ) )
                {
                    $options['pattern'] = "'" . $column['regexp'] . "'";
                }
            }
            else if ( $type == 'enum' )
            {
                $options['choices'] = $this->arrayExport( $column['values'] );
            }

        }

        if ( !$column->isNotNull() || $column->isPrimaryKey() || $column->hasDefinitionKey( 'default' ) )
        {
            $options['required'] = 'false';
            if ( !$column->isNotNull() && !$column->isPrimaryKey() )
            {
                $options['empty_value'] = 'null';
            }
        }

        $options = array_merge( $options, $this->config->getFormValidatorOptions(
            $this->modelName, $column->getName(), $type, array()
        ) );

        $out = array();
        $ni = str_repeat( ' ', $indent );
        foreach ( $options as $k => $v )
        {
            if ( !is_null( $v ) )
            {
                $out[] = $ni . "    '" . $k . "' => " . $v;
            }
        }

        return count( $out ) ? " array(\n" . implode( ",\n", $out ) . "\n" . $ni .") " : '';

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