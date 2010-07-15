<?php

/**
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Add simply join functionality to your Doctrine models
 * 
 * @package     dinDoctrineExtraPlugin.lib.template
 * @subpackage  Joinable
 * @signed      5
 * @signer      vatson
 * @author      vatson [http://vatson.com.ua/]
 * @since       january 11, 2010
 * @version     SVN: $Id$
 */
class Doctrine_Template_Joinable extends Doctrine_Template
{

    protected
        $_options = array(
            'withI18n' => true,
            'componentAlias' => null,
            'parentTable' => null,
            'parentTableAlias' => null,
            'tableAlias' => null,
            'alias' => null,
            'cultures' => array()
        );


    public function __construct( array $options = array() )
    {

        $this->_options = Doctrine_Lib::arrayDeepMerge( $this->_options, $options );

    } // Doctrine_Template_Joinable::__construct()


    public function getAliasTableProxy()
    {

        return empty( $this->_options['alias'] ) ? $this->_table->getComponentName() : $this->_options['alias'] ;

    } // Doctrine_Template_Joinable::getAliasTableProxy()


    public function setAliasTableProxy( $v )
    {

        $this->_options['alias'] = $v;

    } // Doctrine_Template_Joinable::setAliasTableProxy()

    //:TODO: refactor it
    public function joinForTableProxy( Doctrine_Query $q, array $options = array() )
    {

        $options = Doctrine_Lib::arrayDeepMerge( $this->_options, $options );
        $q->getRootAlias(); // fix for initialize root

        if( empty( $options['parentTable'] ) )
        {
            $parentTable = $q->getRoot();
            $parentTableAlias = $q->getRootAlias();
        }
        else
        {
            $parentTable = Doctrine::getTable( $options['parentTable'] );
            $parentTableAlias = empty( $options['parentTableAlias'] ) ? $q->getSqlTableAlias( sfInflector::tableize( $options['parentTable'] ) ) : $options['parentTableAlias'] ;
        }

        if( !empty( $options['componentAlias'] ) )
        {
            $componentAlias = $options['componentAlias'];
        }
        elseif( $parentTable->hasRelation( $this->_table->getComponentName() ) )
        {
            $componentAlias = $this->_table->getComponentName();
        }
        else
        {
            $componentName = $this->_table->getComponentName();
            $relations = $parentTable->getRelations();

            foreach( $relations as $relation )
            {
                if( $relation['class'] == $componentName )
                {
                    $componentAlias = $relation['alias'];
                    break;
                }
            }
        }

        $tableAlias = empty( $options['tableAlias'] ) ? $this->_table->getAlias() : $options['tableAlias'];
        $q->addSqlTableAlias( $tableAlias, sfInflector::tableize( $this->_table->getComponentName() ) );

        $q->leftJoin( $parentTableAlias . '.' . $componentAlias . ' ' . $tableAlias );
        if($options['withI18n'] == true && $this->_table->hasTemplate( 'Doctrine_Template_I18nMod' )) // no need to join non exist translations
        {
            $cultures = empty( $options['cultures'] ) ? sfContext::getInstance()->getUser()->getCulture() : $options['cultures'];
            $chunkWith = implode( ' AND ', array_fill( 0, count( $cultures ), ' ' . $tableAlias . '_trans.lang = ? ' ) );
            $q->leftJoin($tableAlias . '.Translation ' . $tableAlias . '_trans WITH ' . $chunkWith, $cultures );
        }

        return $q;

    } // Doctrine_Template_Joinable::joinForTableProxy()

} // Doctrine_Template_Joinable

//EOF