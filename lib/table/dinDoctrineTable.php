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
 * @package     dinDoctrineExtraPlugin
 * @subpackage  lib.table
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinDoctrineTable extends Doctrine_Table
{

    protected
        $tempQuery = null,
        $tempAlias = null;


    /**
     * Returns true if the current table has some associated i18n objects
     * 
     * @return  boolean Has table some associated i18n objects
     */
    public function isI18n()
    {

        return $this->hasRelation( 'Translation' );

    } // dinDoctrineTable::isI18n()


    /**
     * Add query for processing
     * 
     * @param   Doctrine_Query  $q
     * @param   string          $alias  Table alias [optional]
     * @return  dinDoctrineTable
     */
    public function addQuery( Doctrine_Query $q, $alias = null )
    {

        $this->tempQuery = $q;
        $this->tempAlias = is_null( $alias ) ? $q->getRootAlias() : $alias;
        return $this;

    } // dinDoctrineTable::addQuery()


    /**
     * Remove stored query
     * 
     * @return  Doctrine_Query  Last query
     */
    public function free()
    {

        $q = $this->tempQuery;
        $this->tempQuery = $this->tempAlias = null;
        return $q;

    } // dinDoctrineTable::free()


    /**
     * Add query for retrieving data with I18n
     *
     * @param   Doctrine_Query  $q
     * @return  Doctrine_Query
     */
    public function retrieveWithI18n( Doctrine_Query $q )
    {

        if ( $this->isI18n() )
        {
            $this->addQuery( $q )->joinI18n()->free();
        }
        return $q;

    } // dinDoctrineTable::retrieveWithI18n()


    /**
     * Join table with I18n
     * 
     * @return  dinDoctrineTable
     */
    public function joinI18n()
    {

        if ( !$this->isI18n() )
        {
            return $this;
        }

        $tAlias = $this->tempQuery->getSqlTableAlias( 'Translation', $this->getComponentName() );
        $this->tempQuery->leftJoin( $this->tempAlias . '.Translation ' . $tAlias );
//            ->addWhere( $tAlias . '.lang = ?', sfContext::getInstance()->getUser()->getCulture() );

        //$this->tempQuery->leftJoin(
        //    $this->tempAlias . '.Translation ' . $tAlias . ' WITH ' . $tAlias . '.lang = ?',
        //    sfContext::getInstance()->getUser()->getCulture()
        //);

        return $this;

    } // dinDoctrineTable::joinI18n()


    /**
     * Add select fields
     * 
     * @return  dinDoctrineTable
     */
    public function addSelect( $columns )
    {

        $q = $this->tempQuery;
        $alias = $this->tempAlias;

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

        return $this;

    } // dinDoctrineTable::addSelect()


    /**
     * Add where condition
     * 
     * @return  dinDoctrineTable
     */
    public function addWhere( $column, $value, $clause = '=' )
    {

        if ( $col = $this->getColumn( $column ) )
        {
            if ( !is_null( $value ) )
            {
                $this->tempQuery->addWhere( $col . ' ' . $clause . ' ?', $value );
            }
            else
            {
                $this->tempQuery->addWhere( $col . ' ' . $clause );
            }
        }
        return $this;

    } // dinDoctrineTable::addWhere()


    /**
     * Add order by condition
     * 
     * @param   array   $columns    Columns and directions
     * @return  dinDoctrineTable
     */
    public function addOrderBy( $columns )
    {

        $rules = array();
        foreach ( $columns as $col => $rule )
        {
            if ( is_int( $col ) && $column = $this->getColumn( $rule ) )
            {
                $rules[] = $column;
            }
            else if ( !is_int( $col ) && $column = $this->getColumn( $col ) )
            {
                $rules[] = $column . ' ' . $rule;
            }
        }
        if ( $rules )
        {
            $this->tempQuery->addOrderBy( implode( ', ', $rules ) );
        }
        return $this;

    } // dinDoctrineTable::addOrderBy()


    /**
     * Get column
     * 
     * @return  string  Column name (with alias)
     */
    public function getColumn( $column )
    {

        if ( $this->hasColumn( $column ) )
        {
            return $this->tempAlias . '.' . $column;
        }
        if ( $this->isI18n() && $this->getI18nTable()->hasColumn( $column ) )
        {
            return $this->tempQuery->getSqlTableAlias( 'Translation', $this->getComponentName() )
                . '.' . $column;
        }
        return false;

    } // dinDoctrineTable::getColumn()


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
     */
    public function getSelectQuery( Doctrine_Query $q = null )
    {

        if ( is_null( $q ) )
        {
            $q = $this->createQuery();
        }
        return $q;

    } // dinDoctrineTable::getSelectQuery()


    /**
     * Get select query for choices (all required fields)
     * 
     * @param   array   $params Query parameters [optional]
     * @return  Doctrine_Query object
     */
    public function getChoicesQuery( $params = array() )
    {

        $q = $this->createQuery();
        if ( $this->isI18n() )
        {
            $this->addQuery( $q )->joinI18n()->free();
        }
        return $q;

    } // dinDoctrineTable::getChoicesQuery()


    /**
     * getItemQuery
     * 
     * @return  array   $params Query parameters [optional]
     */
    public function getItemQuery( $params = array() )
    {

        $q = $this->createQuery();
        if ( $this->isI18n() )
        {
            $this->addQuery( $q )->joinI18n()->free();
        }
        return $q;

    } // dinDoctrineTable::getItemQuery()

} // dinDoctrineTable

//EOF