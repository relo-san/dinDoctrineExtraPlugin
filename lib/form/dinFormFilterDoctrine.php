<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Base filter form class
 * 
 * @package     dinDoctrineExtraPlugin.lib.form
 * @signed      5
 * @signer      relo_san
 * @author      relo_san [http://relo-san.com/]
 * @since       february 17, 2010
 * @version     SVN: $Id: dinFormFilterDoctrine.class.php 28 2010-02-25 17:18:36Z relo_san $
 */
abstract class dinFormFilterDoctrine extends sfFormFilterDoctrine
{

    /**
     * Returns true if the current form has some associated i18n objects
     * 
     * @return  boolean     True if the current form has some associated i18n objects
     * @author  relo_san
     * @since   february 17, 2010
     */
    public function isI18n()
    {

        return $this->getTable()->hasRelation( 'Translation' );

    } // dinFormFilterDoctrine::isI18n()


    /**
     * Builds a Doctrine query with processed values
     * 
     * @param   array   $values Values
     * @return  Doctrine_Query
     * @author  relo_san
     * @since   february 17, 2010
     */
    protected function doBuildQuery( array $values )
    {

        $query = isset( $this->options['query'] )
            ? clone $this->options['query']
            : $this->getTable()->createQuery( 'r' );

        if ( $method = $this->getTableMethod() )
        {
            $query = $this->getTable()->$method( $query );
        }

        $fields = $this->getFields();

        $names = array_merge( $fields, array_diff(
            array_keys( $this->validatorSchema->getFields() ), array_keys( $fields )
        ) );
        $fields = array_merge( $fields, array_combine( $names, array_fill( 0, count( $names ), null ) ) );

        foreach ( $fields as $field => $type )
        {
            if ( !isset( $values[$field] ) || null === $values[$field] || '' === $values[$field])
            {
                continue;
            }

            $method = sprintf( 'add%sColumnQuery', self::camelize( $this->getFieldName( $field ) ) );

            if ( method_exists( $this, $method ) )
            {
                $this->$method( $query, $field, $values[$field] );
            }
            else if ( null !== $type )
            {
                if ( !method_exists( $this, $method = sprintf( 'add%sQuery', $type ) ) )
                {
                    throw new LogicException( sprintf( 'Unable to filter for the "%s" type.', $type ) );
                }

                $this->$method( $query, $field, $values[$field] );
            }
        }

        return $query;

    } // dinFormFilterDoctrine::doBuildQuery()


    /**
     * Filter query for text fields
     * 
     * @param   Doctrine_Query      $q
     * @param   string  $field      Column name
     * @param   array   $values     Values
     * @param   boolean $isBegin    Is search from begin [optional]
     * @return  void
     * @author  relo_san
     * @since   february 17, 2010
     */
    protected function addTextQuery( Doctrine_Query $q, $field, $values, $isBegin = false )
    {

        $fieldName = $this->getFieldName( $field );
        $alias = $this->getTable()->hasField( $fieldName )
            ? $q->getRootAlias()
            : $this->getTable()->getI18nAlias( $q->getRootAlias() );

        if ( is_array( $values ) && isset( $values['is_empty'] ) && $values['is_empty'] )
        {
            $q->addWhere( sprintf( '%s.%s IS NULL', $alias, $fieldName ) );
        }
        else if ( is_array( $values ) && isset( $values['text'] ) && '' != $values['text'] )
        {
            $q->addWhere(
                sprintf( '%s.%s LIKE ?', $alias, $fieldName ),
                ( $isBegin ? '' : '%' ) . $values['text'] . '%'
            );
        }

    } // dinFormFilterDoctrine::addTextQuery()


    /**
     * Filter query for boolean fields
     * 
     * @param   Doctrine_Query  $q
     * @param   string  $field  Column name
     * @param   array   $values Values
     * @return  void
     * @author  relo_san
     * @since   february 17, 2010
     */
    public function addBooleanQuery( Doctrine_Query $q, $field, $values )
    {

        $fieldName = $this->getFieldName( $field );
        $alias = $this->getTable()->hasField( $fieldName )
            ? $q->getRootAlias()
            : $this->getTable()->getI18nAlias( $q->getRootAlias() );
        $q->addWhere( sprintf( '%s.%s = ?', $alias, $fieldName ), $values );

    } // dinFormFilterDoctrine::addBooleanQuery()


    /**
     * Filter query for data range fields
     * 
     * @param   Doctrine_Query  $q
     * @param   string  $field  Column name
     * @param   array   $values Values
     * @return  void
     * @author  relo_san
     * @since   february 17, 2010
     */
    protected function addDateQuery( Doctrine_Query $q, $field, $values )
    {

        $fieldName = $this->getFieldName( $field );
        $alias = $this->getTable()->hasField( $fieldName )
            ? $q->getRootAlias()
            : $this->getTable()->getI18nAlias( $q->getRootAlias() );

        if ( isset( $values['is_empty'] ) && $values['is_empty'] )
        {
            $q->addWhere( sprintf( '%s.%s IS NULL', $alias, $fieldName ) );
        }
        else
        {
            if ( null !== $values['from'] && null !== $values['to'] )
            {
                $q->andWhere( sprintf( '%s.%s >= ?', $alias, $fieldName ), $values['from'] );
                $q->andWhere( sprintf( '%s.%s <= ?', $alias, $fieldName ), $values['to'] );
            }
            else if ( null !== $values['from'] )
            {
                $q->andWhere( sprintf( '%s.%s >= ?', $alias, $fieldName ), $values['from'] );
            }
            else if ( null !== $values['to'] )
            {
                $q->andWhere( sprintf( '%s.%s <= ?', $alias, $fieldName ), $values['to'] );
            }
        }

    } // dinFormFilterDoctrine::addDateQuery()


    /**
     * Filter query for number fields
     * 
     * @param   Doctrine_Query  $q
     * @param   string  $field  Column name
     * @param   array   $values Values
     * @author  relo_san
     * @since   february 17, 2010
     */
    protected function addNumberQuery( Doctrine_Query $q, $field, $values )
    {

        $fieldName = $this->getFieldName( $field );
        $alias = $this->getTable()->hasField( $fieldName )
            ? $q->getRootAlias()
            : $this->getTable()->getI18nAlias( $q->getRootAlias() );

        if ( is_array( $values ) )
        {
            if ( isset( $values['is_empty'] ) && $values['is_empty'] )
            {
                $query->addWhere( sprintf( '%s.%s IS NULL', $alias, $fieldName ) );
            }
            else if ( isset( $values['text'] ) && '' != $values['text'] )
            {
                $query->addWhere( sprintf( '%s.%s = ?', $alias, $fieldName ), $values['text'] );
            }
            else if ( isset( $values['min'] ) || isset( $values['max'] ) )
            {
                if ( '' != $values['min'] && '' != $values['max'] )
                {
                    $q->andWhere( sprintf( '%s.%s >= ?', $alias, $fieldName ), $values['min'] );
                    $q->andWhere( sprintf( '%s.%s <= ?', $alias, $fieldName ), $values['max'] );
                }
                else if ( '' != $values['min'] )
                {
                    $q->andWhere( sprintf( '%s.%s >= ?', $alias, $fieldName ), $values['min'] );
                }
                else if ( '' != $values['max'] )
                {
                    $q->andWhere( sprintf( '%s.%s <= ?', $alias, $fieldName ), $values['max'] );
                }
            }
        }
        else if ( '' != $values )
        {
            $q->addWhere( sprintf( '%s.%s = ?', $alias, $fieldName ), $values );
        }

    } // dinFormFilterDoctrine::addNumberQuery()

} // dinFormFilterDoctrine

//EOF