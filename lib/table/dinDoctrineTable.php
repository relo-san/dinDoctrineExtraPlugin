<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Base class for performing query and update operations for tables
 * 
 * @package     dinDoctrineExtraPlugin.lib.table
 * @signed      4
 * @signer      relo_san
 * @author      relo_san [http://relo-san.com/]
 * @since       january 11, 2010
 * @version     SVN: $Id: DinDoctrineTable.class.php 48 2010-05-31 23:28:21Z relo_san $
 */
class dinDoctrineTable extends Doctrine_Table
{

    /**
     * Plugin config.
     * @var PluginConfiguration object
     */
    protected
        $pluginConfig = null,
        $pluginName = null;


    /**
     * Add query for retrieving data with I18n
     *
     * @param   object  $q  Doctrine_Query object
     * @return  Doctrine_Query object
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function retrieveWithI18n( Doctrine_Query $q )
    {

        if ( $this->isI18n() )
        {
            $q = $this->joinI18n( $q );
        }
        return $q;

    } // DinDoctrineTable::retrieveWithI18n()


    /**
     * Join table with I18n
     * 
     * @param   object  $q      Doctrine_Query object
     * @param   string  $alias  Table alias [optional]
     * @return  Doctrine_Query object
     * @author  relo_san
     * @since   march 10, 2010
     */
    public function joinI18n( Doctrine_Query $q, $alias = null )
    {

        if ( !$this->isI18n() )
        {
            return $q;
        }

        if ( is_null( $alias ) )
        {
            $alias = $q->getRootAlias();
        }

        $tAlias = $q->getSqlTableAlias( 'Translation', $this->getComponentName() );
        $q->leftJoin(
            $alias . '.Translation ' . $tAlias . ' WITH ' . $tAlias . '.lang = ?',
            sfContext::getInstance()->getUser()->getCulture()
        );

        return $q;

    } // DinDoctrineTable::joinI18n()


    /**
     * addSelect
     * 
     * @return  Doctrine_Query
     * @author  relo_san
     * @since   22.03.2010
     */
    public function addSelect( Doctrine_Query $q, $columns, $alias = null )
    {

        if ( is_null( $alias ) )
        {
            $alias = $q->getRootAlias();
        }

        if ( $isI18n = $this->isI18n() )
        {
            $i18nTable = $this->getI18nTable();
            $tAlias = $q->getSqlTableAlias( 'Translation', $this->getComponentName() );
        }
        foreach ( $columns as $column )
        {
            if ( $this->hasColumn( $column ) )
            {
                $q->addSelect( $alias . '.' . $column );
            }
            else if ( $isI18n && $i18nTable->hasColumn( $column ) )
            {
                $q->addSelect( $tAlias . '.' . $column );
            }
        }

        return $q;

    } // DinDoctrineTable::addSelect()


    /**
     * getColumn
     * 
     * @return  
     * @author  relo_san
     * @since   22.03.2010
     */
    public function getColumn( Doctrine_Query $q, $column, $alias = null )
    {

        if ( is_null( $alias ) )
        {
            $alias = $q->getRootAlias();
        }
        if ( $this->hasColumn( $column ) )
        {
            return $alias . '.' . $column;
        }
        if ( $this->isI18n() && $this->getI18nTable()->hasColumn( $column ) )
        {
            return $q->getSqlTableAlias( 'Translation', $this->getComponentName() ) . '.' . $column;
        }
        return false;

    } // DinDoctrineTable::getColumn()


    /**
     * getColumnPart
     * 
     * @return  
     * @author  relo_san
     * @since   10.03.2010
     */
    public function getColumnPart( $column, $alias )
    {

        if ( $this->hasColumn( $column ) )
        {
            return $alias . '.' . $column;
        }
        return $this->getI18nAlias( $alias ) . '.' . $column;

    } // DinDoctrineTable::getColumnPart()


    /**
     * Search in field
     * 
     * @param   string  $field      Column name
     * @param   array   $source     Search query string
     * @param   integer $limit      Count items limit in result
     * @param   Doctrine_Query      $q  [optional]
     * @param   boolean $isBegin    Is search from begin [optional]
     * @return  array   Result collection
     * @author  relo_san
     * @since   march 6, 2010
     */
    public function searchInField( $field, $source, $limit = 10, $q = null, $isBegin = false )
    {

        if( !$source )
        {
            return false;
        }

        if ( is_null( $q ) )
        {
            $q = $this->createQuery();
        }

        $fieldName = $this->getFieldName( $field );
        $alias = $this->hasField( $fieldName )
            ? $q->getRootAlias()
            : $this->getI18nAlias( $q->getRootAlias() );

        $isBegin = $isBegin ? '' : '%';
        return $q->addWhere( $alias . '.' . $fieldName . ' LIKE ?', $isBegin . $source . '%' )
            ->limit( $limit )->execute();

    } // DinDoctrineTable::searchInField()


    /**
     * Remove cache
     * 
     * @param   integer $itemId         Item identifier
     * @param   integer $parentId       Parent identifier [optional]
     * @param   integer $oldParentId    Old parent identifier [optional]
     * @return  void
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function removeCache( array $params )
    {

        $model = $this->getComponentName();
        sfContext::getInstance()->get( 'cache_routing' )->removeCacheForModel( $model, $params );

    } // DinDoctrineTable::removeCache()


    /**
     * Get alias for translation table
     * 
     * @param   string  $rootAlias  Root alias [optional]
     * @return  string  Alias for i18n table (or root if disabled)
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function getI18nAlias( $rootAlias = '' )
    {

        return $rootAlias . ( $this->isI18n() ? ( $rootAlias ? '.' : '' ) . 'Translation' : '' );

    } // DinDoctrineTable::getI18nAlias()


    /**
     * Returns true if the current table has some associated i18n objects
     * 
     * @return  boolean Has table some associated i18n objects
     * @author  relo_san
     * @since   february 17, 2010
     */
    public function isI18n()
    {

        return $this->hasTemplate( 'Doctrine_Template_I18nMod' );

    } // DinDoctrineTable::isI18n()


    /**
     * Get i18n table
     * 
     * @return  Doctrine_Table
     * @author  relo_san
     * @since   march 6, 2010
     */
    public function getI18nTable()
    {

        if ( $this->isI18n() )
        {
            return $this->getTemplate( 'Doctrine_Template_I18nMod' )->getI18n()->getTable();
        }
        return false;

    } // DinDoctrineTable::getI18nTable()


    /**
     * Get select query (all required fields)
     * 
     * @param   object  $q  Doctrine_Query object [optional]
     * @return  Doctrine_Query object
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function getSelectQuery( Doctrine_Query $q = null )
    {

        if ( is_null( $q ) )
        {
            $q = $this->createQuery();
        }
        return $q;

    } // DinDoctrineTable::getSelectQuery()


    /**
     * Get select query for choices (all required fields)
     * 
     * @param   array   $params Query parameters [optional]
     * @return  Doctrine_Query object
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function getChoicesQuery( $params = array() )
    {

        $q = $this->createQuery();
        $q = $this->joinI18n( $q );
        return $q;

    } // DinDoctrineTable::getChoicesQuery()


    /**
     * getItemQuery
     * 
     * @return  array   $params Query parameters [optional]
     * @author  relo_san
     * @since   15.03.2010
     */
    public function getItemQuery( $params = array() )
    {

        $q = $this->createQuery();
        $q = $this->joinI18n( $q );
        return $q;

    } // DinDoctrineTable::getItemQuery()


    /**
     * Get plugin config
     * 
     * @return  void
     * @author  relo_san
     * @since   january 11, 2010
     * @throws  sfConfigurationException if plugin name not exist
     */
    public function getPluginConfig()
    {

        if ( !$this->pluginConfig )
        {
            if ( !$this->pluginName )
            {
                throw new sfConfigurationException( 'Plugin name must be set for model '
                    . $this->getComponentName() );
            }
            if ( sfContext::hasInstance() )
            {
                $this->pluginConfig = sfContext::getInstance()->getConfiguration()
                    ->getPluginConfiguration( $this->pluginName );
            }
            else
            {
                $class = ucfirst( $this->pluginName ) . 'Configuration';
                $this->pluginConfig = new $class( new sfProjectConfiguration() );
            }
        }
        return $this->pluginConfig;

    } // DinDoctrineTable::getPluginConfig()


    /**
     * Get plugin name
     * 
     * @return  string  Plugin name
     * @author  relo_san
     * @since   february 26, 2010
     */
    public function getPluginName()
    {

        return $this->pluginName;

    } // DinDoctrineTable::getPluginName()

} // DinDoctrineTable

//EOF