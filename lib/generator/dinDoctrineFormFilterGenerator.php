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

    protected
        $widgetClasses = array(
            'boolean'   => 'sfWidgetFormChoice',
            'date'      => 'sfWidgetFormFilterDate',
            'datetime'  => 'sfWidgetFormFilterDate',
            'timestamp' => 'sfWidgetFormFilterDate',
            'enum'      => 'sfWidgetFormChoice'
        ),
        $widgetOptions = array(
            'boolean'   => array( 'choices' => "array( '' => 'labels.yesOrNo', 1 => 'labels.yes', 0 => 'labels.no' )" ),
            'date'      => array( 'from_date' => 'new sfWidgetFormDate()', 'to_date' => 'new sfWidgetFormDate()' ),
            'datetime'  => array( 'from_date' => 'new sfWidgetFormDate()', 'to_date' => 'new sfWidgetFormDate()' ),
            'timestamp' => array( 'from_date' => 'new sfWidgetFormDate()', 'to_date' => 'new sfWidgetFormDate()' )
        ),
        $validatorClasses = array(
            'boolean'   => 'sfValidatorChoice',
            'float'     => 'sfValidatorNumber',
            'decimal'   => 'sfValidatorNumber',
            'integer'   => 'sfValidatorInteger',
            'date'      => 'sfValidatorDateRange',
            'datetime'  => 'sfValidatorDateRange',
            'timestamp' => 'sfValidatorDateRange',
            'enum'      => 'sfValidatorChoice',
        ),
        $validatorOptions = array(
            'boolean'   => array( 'choices' => "array( '', 1, 0 )" ),
            'date'      => array( 'from_date' => "new sfValidatorDate( array( 'required' => false ) )", 'to_date' => "new sfValidatorDateTime( array( 'required' => false ) )" ),
            'datetime'  => array( 'from_date' => "new sfValidatorDateTime( array( 'required' => false, 'datetime_output' => 'Y-m-d 00:00:00' ) )", 'to_date' => "new sfValidatorDateTime( array( 'required' => false, 'datetime_output' => 'Y-m-d 23:59:59' ) )" ),
            'timestamp' => array( 'from_date' => "new sfValidatorDateTime( array( 'required' => false, 'datetime_output' => 'Y-m-d 00:00:00' ) )", 'to_date' => "new sfValidatorDateTime( array( 'required' => false, 'datetime_output' => 'Y-m-d 23:59:59' ) )" )
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
     * Get array of sfDoctrineColumn objects that exist on the current model and translation
     * 
     * @return array $columns
     */
    public function getColumns()
    {

        $parentModel = $this->getParentModel();
        $parentColumns = $parentModel
            ? array_keys( Doctrine_Core::getTable( $parentModel )->getColumns() )
            : array();

        $columns = array();
        foreach ( array_diff( array_keys( $this->table->getColumns() ), $parentColumns ) as $name )
        {
            if ( $this->config->allowColumn( $this->table->getComponentName(), $name, 'filter' ) )
            {
                $columns[] = new sfDoctrineColumn( $name, $this->table );
            }
        }
        if ( $this->table->isI18n() && $i18n = $this->table->getI18nTable() )
        {
            foreach ( array_keys( $i18n->getColumns() ) as $name )
            {
                if ( in_array( $name, array( 'id', 'lang' ) ) )
                {
                    continue;
                }
                if ( $this->config->allowColumn( $i18n->getComponentName(), $name, 'filter' ) )
                {
                    $columns[] = new sfDoctrineColumn( $name, $i18n );
                }
            }
        }

        return $columns;

    } // dinDoctrineFormFilterGenerator::getColumns()


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
            : 'sfWidgetFormFilterInput';

        $name = $this->config->getFilterWidgetClass(
            $this->modelName, $column->getName(), $type, $default
        );

        if ( $column->isForeignKey() )
        {
            $name = 'sfWidgetFormDoctrineChoice';
        }

        return $name;

    } // dinDoctrineFormFilterGenerator::getWidgetClassForColumn()


    /**
     * Returns a PHP string representing options to pass to a widget for a given column
     * 
     * @param   sfDoctrineColumn    $column
     * @param   integer             $indent Indentation value [optional]
     * @return  string  The options to pass to the widget as a PHP string
     */
    public function getWidgetOptionsForColumn( $column, $indent = 0 )
    {

        $type = $column->getDoctrineType();
        $default = isset( $this->widgetOptions[$type] ) ? $this->widgetOptions[$type] : array();
        $options = $column->isNotNull() && !$column->isForeignKey()
            && !in_array( $type, array( 'boolean', 'enum' ) )
            ? array( 'with_empty' => 'false' )
            : array();
        if ( !$options && !$column->isForeignKey() && !in_array( $type, array( 'boolean', 'enum' ) ) )
        {
            $options['empty_label'] = "'labels.isEmpty'";
            $options['template'] = "'%input% <div class=\"sf-filter-empty\">%empty_checkbox% %empty_label%</div>'";
        }

        if ( $type == 'enum' )
        {
            $values = array_merge( array( '' => '' ), $column['values'] );
            $values = array_combine( $values, $values );
            $options['choices'] = $this->arrayExport( $values );
        }

        $camName = sfInflector::camelize( $column->getName() );
        $options['label'] = "'formLabels." . strtolower( substr( $camName, 0, 1 ) ) . substr( $camName, 1 ) . "'";

        $options = array_merge( $options, $this->config->getFilterWidgetOptions(
            $this->modelName, $column->getName(), $type, $default
        ) );

        if ( $column->isForeignKey() )
        {
            $options['model'] = '$this->getRelatedModelName( \'' . $column->getRelationKey( 'alias' ) . "' )";
            $options['add_empty'] = 'true';
            $options['min'] = null;
            $options['max'] = null;
        }

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

    } // dinDoctrineFormFilterGenerator::getWidgetOptionsForColumn()


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

        $name = $this->config->getFilterValidatorClass(
            $this->modelName, $column->getName(), $type, $default
        );

        if ( $column->isPrimarykey() || $column->isForeignKey() )
        {
            $name = 'sfValidatorDoctrineChoice';
        }

        return $name;

    } // dinDoctrineFormFilterGenerator::getValidatorClassForColumn()


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
        $default = isset( $this->validatorOptions[$type] ) ? $this->validatorOptions[$type] : array();
        $options = array( 'required' => 'false' );

        if ( $column->isForeignKey() )
        {
            $columns = $column->getForeignTable()->getColumns();
            foreach ( $columns as $name => $col )
            {
                if ( isset( $col['primary'] ) && $col['primary'] )
                {
                    break;
                }
            }
            $options['model'] = "\$this->getRelatedModelName( '" . $column->getRelationKey( 'alias' ) . "' )";
            $options['column'] = "'" . $column->getForeignTable()->getFieldName( $name ) . "'";
        }
        else if ($column->isPrimaryKey())
        {
            $options['model'] = "'" . $this->table->getOption( 'name' ) . "'";
            $options['column'] = "'" . $column->getFieldName() . "'";
        }
        else
        {

            if ( $type == 'enum' )
            {
                $values = array_combine( $column['values'], $column['values'] );
                $options['choices'] = $this->arrayExport( $values );
            }

            $options = array_merge( $options, $this->config->getFilterValidatorOptions(
                $this->modelName, $column->getName(), $type, $default
            ) );

        }

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

    } // dinDoctrineFormFilterGenerator::getValidatorOptionsForColumn()


    /**
     * Returns a PHP string representing validator with options for a given column
     * 
     * @param   sfDoctrineColumn    $column
     * @param   integer             $indent Indentation value [optional]
     * @return  string  Validator with options as a PHP string
     */
    public function getValidatorForColumn( $column, $indent = 0 )
    {

        $format = 'new %s(%s)';

        $class = $this->getValidatorClassForColumn( $column );
        if ( in_array( $class, array( 'sfValidatorInteger', 'sfValidatorNumber' ) ) )
        {
            $format = "new sfValidatorSchemaFilter( 'text', new %s(%s) )";
        }

        return sprintf( $format, $class, $this->getValidatorOptionsForColumn( $column, $indent ) );

    } // dinDoctrineFormFilterGenerator::getValidatorForColumn()


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