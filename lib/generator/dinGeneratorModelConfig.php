<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Model config for generators
 * 
 * @package     dinDoctrineExtraPlugin
 * @subpackage  lib.generator
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinGeneratorModelConfig
{

    static protected
        $inctance = null;

    protected
        $configuration = null,
        $config = array();


    /**
     * Get config manager instance
     * 
     * @return  dinGeneratorModelConfig
     */
    static public function getInstance( sfProjectConfiguration $configuration = null )
    {

        if ( !self::$inctance )
        {
            self::$inctance = new dinGeneratorModelConfig( $configuration );
        }
        return self::$inctance;

    } // dinGeneratorModelConfig::getInstance()


    /**
     * Constructor
     * 
     * @return  void
     */
    public function __construct( sfProjectConfiguration $configuration )
    {

        $this->configuration = $configuration;
        $this->loadConfig();

    } // dinGeneratorModelConfig::__construct()


    /**
     * Get strategy type for form classes
     * 
     * @param   string  $model  Model name [optional]
     * @return  string  Strategy type
     */
    public function getFormPluginStrategy( $model = null )
    {

        if ( $model && isset( $this->config['forms'][$model]['plugin_strategy'] ) )
        {
            return $this->config['forms'][$model]['plugin_strategy'];
        }
        return isset( $this->config['classes']['form_plugin_strategy'] )
            ? $this->config['classes']['form_plugin_strategy'] : 'exist';

    } // dinGeneratorModelConfig::getFormPluginStrategy()


    /**
     * Get strategy type for filter form classes
     * 
     * @param   string  $model  Model name [optional]
     * @return  string|boolean  Strategy type
     */
    public function getFilterPluginStrategy( $model = null )
    {

        if ( $model && isset( $this->config['filters'][$model]['plugin_strategy'] ) )
        {
            return $this->config['filters'][$model]['plugin_strategy'];
        }
        return isset( $this->config['classes']['filter_plugin_strategy'] )
            ? $this->config['classes']['filter_plugin_strategy'] : 'exist';

    } // dinGeneratorModelConfig::getFilterPluginStrategy()


    /**
     * Allow generate form classes for model (or both)
     * 
     * @param   string  $model  Model name [optional]
     * @return  boolean Is allow form
     */
    public function allowForm( $model = null )
    {

        if ( $model && isset( $this->config['forms'][$model]['disable'] ) )
        {
            return !$this->config['forms'][$model]['disable'];
        }
        return isset( $this->config['classes']['allow_forms'] )
            ? (boolean) $this->config['classes']['allow_forms'] : true;

    } // dinGeneratorModelConfig::allowForm()


    /**
     * Allow generate filter form classes for model (or both)
     * 
     * @param   string  $model  Model name [optional]
     * @return  boolean Is allow filter form
     */
    public function allowFilter( $model = null )
    {

        if ( $model && isset( $this->config['filters'][$model]['disable'] ) )
        {
            return !$this->config['filters'][$model]['disable'];
        }
        return isset( $this->config['classes']['allow_filters'] )
            ? (boolean) $this->config['classes']['allow_filters'] : true;

    } // dinGeneratorModelConfig::allowFilter()


    /**
     * Allow generate model form classes for model
     * 
     * @param   string  $model  Model name
     * @return  boolean Is allow model
     */
    public function allowModel( $model )
    {

        if ( isset( $this->config['models'][$model]['disable'] ) )
        {
            return !$this->config['models'][$model]['disable'];
        }
        return true;

    } // dinGeneratorModelConfig::allowFilter()


    /**
     * Allow behavior for model (or both)
     * 
     * @param   string  $behavior   Behavior name (standart)
     * @param   string  $model      Model name [optional]
     * @return  boolean Is allow behavior
     */
    public function allowBehavior( $behavior, $model = null )
    {

        if ( $model && isset( $this->config['models'][$model]['actAs'][$behavior]['disable'] ) )
        {
            return !$this->config['models'][$model]['actAs'][$behavior]['disable'];
        }

        if ( isset( $this->config['behaviors'][$behavior]['disable'] ) )
        {
            return !$this->config['behaviors'][$behavior]['disable'];
        }

        return true;

    } // dinGeneratorModelConfig::allowBehavior()


    /**
     * Allow column in model
     * 
     * @param   string  $model  Model name [optional]
     * @return  boolean Is allow column
     */
    public function allowColumn( $model, $column )
    {

        if ( isset( $this->config['models'][$model]['columns'][$column]['disable'] ) )
        {
            return !$this->config['models'][$model]['columns'][$column]['disable'];
        }
        return true;

    } // dinGeneratorModelConfig::allowColumn()


    /**
     * Get database options for model (or both)
     * 
     * @param   string  $model  Model name [optional]
     * @return  array   Database options
     */
    public function getDbOptions( $model = null )
    {

        if ( $model && isset( $this->config['models'][$model]['db_options'] ) )
        {
            return (array) $this->config['models'][$model]['db_options'];
        }
        return isset( $this->config['db_options'] )
            ? (array) $this->config['db_options'] : array();

    } // dinGeneratorModelConfig::getDbOptions()


    /**
     * Get name of behavior for model (or both)
     * 
     * @param   string  $stdName    Standart behavior name
     * @param   string  $model      Model name [optional]
     * @return  string  Behavior name
     */
    public function getBehaviorName( $stdName, $model = null )
    {

        if ( $model && isset( $this->config['models'][$model]['actAs'][$stdName]['name'] ) )
        {
            return $this->config['models'][$model]['actAs'][$stdName]['name'];
        }

        if ( isset( $this->config['behaviors'][$stdName]['name'] ) )
        {
            return $this->config['behaviors'][$stdName]['name'];
        }
        return $stdName;

    } // dinGeneratorModelConfig::getBehaviorName()


    /**
     * Preparing model definition
     * 
     * @param   string  $model      Model name
     * @param   array   $definition Model definition [optional]
     * @return  array   Model definition
     */
    public function prepareModelDefinition( $model, $definition = array() )
    {

        if ( !$this->allowModel( $model ) )
        {
            return false;
        }

        $definition = $this->prepareModelDbOptions( $model, $definition );
        $definition = $this->prepareModelColumns( $model, $definition );
        $definition = $this->prepareModelBehaviors( $model, $definition );
        $definition = $this->prepareModelRelations( $model, $definition );
        $definition = $this->prepareModelIndexes( $model, $definition );

        return $definition;

    } // dinGeneratorModelConfig::prepareModelDefinition()


    /**
     * Preparing database options for model
     * 
     * @param   string  $model      Model name
     * @param   array   $definition Model definition
     * @return  array   Model definition
     */
    protected function prepareModelDbOptions( $model, $definition )
    {

        $definition['options'] = $this->getDbOptions( $model );
        if ( !$definition['options'] )
        {
            unset( $definition['options'] );
        }
        return $definition;

    } // dinGeneratorModelConfig::prepareModelDbOptions()


    /**
     * Preparing columns for model
     * 
     * @param   string  $model      Model name
     * @param   array   $definition Model definition
     * @return  array   Model definition
     */
    protected function prepareModelColumns( $model, $definition )
    {

        $srcs = $definition['columns'];
        if ( isset( $this->config['models'][$model]['columns'] ) )
        {
            $srcs = array_merge( $srcs, $this->config['models'][$model]['columns'] );
        }

        // disabling columns
        foreach ( $srcs as $column => $def )
        {
            if ( isset( $srcs[$column]['disable'] ) )
            {
                if ( $srcs[$column]['disable'] )
                {
                    unset( $srcs[$column] );
                }
                else
                {
                    unset( $srcs[$column]['disable'] );
                }
            }
        }

        // renaming columns
        $columns = array();
        foreach ( $srcs as $column => $def )
        {
            if ( isset( $def['name'] ) && !isset( $srcs[$def['name']] ) )
            {
                $columns[$def['name']] = $def;
                unset( $columns[$def['name']]['name'] );
            }
            else
            {
                $columns[$column] = $def;
                if ( isset( $columns[$column]['name'] ) )
                {
                    unset( $columns[$column]['name'] );
                }
            }
        }
        $srcs = $columns;

        // sorting columns
        $columns = array();
        $curr = null;
        foreach ( $srcs as $column => $def )
        {
            $isAdd = false;
            foreach ( $srcs as $column1 => $def1 )
            {
                if ( isset( $def1['after'] ) && $def1['after'] == $curr && !isset( $columns[$column1] ) )
                {
                    $columns[$column1] = $def1;
                    unset( $columns[$column1]['after'] );
                    $curr = $column1;
                    $isAdd = true;
                }
            }
            if ( !$isAdd && !isset( $def['after'] ) && !isset( $columns[$column] ) )
            {
                $columns[$column] = $def;
                $curr = $column;
            }
        }

        $definition['columns'] = $columns;
        return $definition;

    } // dinGeneratorModelConfig::prepareModelColumns()


    /**
     * Preparing behaviors for model
     * 
     * @param   string  $model      Model name
     * @param   array   $definition Model definition
     * @return  array   Model definition
     */
    protected function prepareModelBehaviors( $model, $definition )
    {

        $srcs = isset( $definition['actAs'] ) ? $definition['actAs'] : array();
        if ( isset( $this->config['models'][$model]['actAs'] ) )
        {
            foreach ( $this->config['models'][$model]['actAs'] as $behavior => $def )
            {
                if ( isset( $def['disable'] ) )
                {
                    if ( $def['disable'] )
                    {
                        if ( isset( $srcs[$behavior] ) )
                        {
                            unset( $srcs[$behavior] );
                        }
                        continue;
                    }
                    unset( $def['disable'] );
                }
                $srcs[$behavior] = array_merge(
                    isset( $srcs[$behavior] ) ? $srcs[$behavior] : array(), $def
                );
                foreach ( $srcs[$behavior] as $key => $value )
                {
                    if ( is_null( $value ) )
                    {
                        unset( $srcs[$behavior][$key] );
                    }
                }
                if ( isset( $def['fields'] ) )
                {
                    $srcs[$behavior]['fields'] = $def['fields'];
                }
            }
        }

        // renaming behaviors (and remove global)
        foreach ( $srcs as $behavior => $def )
        {
            if ( !$this->allowBehavior( $behavior, $model ) )
            {
                unset( $srcs[$behavior] );
                continue;
            }
            $name = $this->getBehaviorName( $behavior, $model );
            if ( $name != $behavior )
            {
                $srcs[$name] = $def;
                unset( $srcs[$behavior] );
            }
        }
        $definition['actAs'] = $srcs;
        if ( !$definition['actAs'] )
        {
            unset( $definition['actAs'] );
        }

        return $definition;

    } // dinGeneratorModelConfig::prepareModelBehaviors()


    /**
     * Preparing relations for model
     * 
     * @param   string  $model      Model name
     * @param   array   $definition Model definition
     * @return  array   Model definition
     */
    protected function prepareModelRelations( $model, $definition )
    {

        $srcs = array_merge(
            isset( $definition['relations'] ) ? $definition['relations'] : array(),
            isset( $this->config['models'][$model]['relations'] )
                ? $this->config['models'][$model]['relations'] : array()
        );

        foreach ( $srcs as $relation => $def )
        {
            foreach ( $def as $key => $value )
            {
                if ( is_null( $value ) )
                {
                    unset( $srcs[$relation][$key] );
                }
            }
            if ( isset( $def['disable'] ) )
            {
                if ( $def['disable'] )
                {
                    if ( isset( $srcs[$relation] ) )
                    {
                        unset( $srcs[$relation] );
                    }
                    continue;
                }
                unset( $def['disable'] );
            }
            if ( !$this->allowModel( $relation )
                || ( isset( $def['class'] ) && !$this->allowModel( $def['class'] ) )
                || ( isset( $def['local'] ) && !isset( $definition['columns'][$def['local']] ) ) )
            {
                unset( $srcs[$relation] );
                continue;
            }
        }
        $definition['relations'] = $srcs;
        if ( !$definition['relations'] )
        {
            unset( $definition['relations'] );
        }

        return $definition;

    } // dinGeneratorModelConfig::prepareModelRelations()


    /**
     * Preparing indexes for model
     * 
     * @param   string  $model      Model name
     * @param   array   $definition Model definition
     * @return  array   Model definition
     */
    protected function prepareModelIndexes( $model, $definition )
    {

        $srcs = isset( $definition['indexes'] ) ? $definition['indexes'] : array();
        if ( isset( $this->config['models'][$model]['indexes'] ) )
        {
            foreach ( $this->config['models'][$model]['indexes'] as $index => $def )
            {
                if ( isset( $def['disable'] ) )
                {
                    if ( $def['disable'] )
                    {
                        if ( isset( $srcs[$index] ) )
                        {
                            unset( $srcs[$index] );
                        }
                        continue;
                    }
                    unset( $def['disable'] );
                }
                if ( isset( $def['fields'] ) )
                {
                    $srcs[$index]['fields'] = $def['fields'];
                }
                if ( isset( $def['type'] ) )
                {
                    $srcs[$index]['type'] = $def['type'];
                }
            }
        }

        // check indexes
        foreach ( $srcs as $index => $def )
        {
            foreach ( $def['fields'] as $k => $field )
            {
                if ( !isset( $definition['columns'][$field] )
                    || $this->isTranslated( $field, $model, $definition ) )
                {
                    unset( $def['fields'][$k] );
                }
            }
            if ( !$def['fields'] )
            {
                unset( $srcs[$index] );
                continue;
            }
            $srcs[$index] = $def;
        }
        $definition['indexes'] = $srcs;
        if ( !$definition['indexes'] )
        {
            unset( $definition['indexes'] );
        }

        return $definition;

    } // dinGeneratorModelConfig::prepareModelIndexes()


    /**
     * Check if column in Translation table
     * 
     * @param   string  $field      Field name
     * @param   string  $model      Model name
     * @param   array   $definition Model definition
     * @return  boolean Is field translated
     */
    protected function isTranslated( $field, $model, $definition )
    {

        $i18n = $this->getBehaviorName( 'I18n', $model );
        if ( isset( $definition['actAs'][$i18n] ) )
        {
            foreach ( $definition['actAs'][$i18n]['fields'] as $tField )
            {
                if ( $tField == $field )
                {
                    return true;
                }
            }
        }
        return false;

    } // dinGeneratorModelConfig::isTranslated()


    /**
     * Load model configuration
     * 
     * @return  void
     */
    protected function loadConfig()
    {

        $rootDir = $this->configuration->getRootDir();
        $files[] = $rootDir . '/config/model.yml';
        $files[] = $rootDir . '/lib/config/model.yml';

        $pluginPaths = $this->configuration->getAllPluginPaths();
        $plugins = $this->configuration->getPlugins();
        foreach ( $pluginPaths as $plugin => $path )
        {
            if ( in_array( $plugin, $plugins ) )
            {
                $files[] = $path . '/config/model.yml';
            }
        }
        foreach ( $plugins as $plugin )
        {
            $files[] = $rootDir . '/lib/config/' . $plugin . '/model.yml';
        }

        $config = array();
        foreach ( $files as $file )
        {
            if ( is_file( $file ) && is_readable( $file ) )
            {
                $config = sfToolkit::arrayDeepMerge( $config, sfYaml::load( $file ) );
            }
        }
        $this->config = $config;

    } // dinGeneratorModelConfig::loadConfig()

} // dinGeneratorModelConfig

//EOF