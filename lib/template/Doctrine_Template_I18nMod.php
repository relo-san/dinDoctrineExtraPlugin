<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Add multilingual capabilities to your Doctrine models
 * 
 * @package     dinDoctrineExtraPlugin.lib.template
 * @signed      5
 * @signer      relo_san
 * @author      relo_san [http://relo-san.com/]
 * @since       january 11, 2010
 * @version     SVN: $Id: Doctrine_Template_I18nMod.class.php 4 2010-01-12 19:18:15Z relo_san $
 */
class Doctrine_Template_I18nMod extends Doctrine_Template
{

    /**
     * Constructor
     * 
     * @param   array   $options    I18n options
     * @return  void
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function __construct( array $options = array() )
    {

        parent::__construct( $options );
        $this->_plugin = new Doctrine_I18n( $this->_options );

    } // Doctrine_Template_I18nMod::__construct()


    /**
     * Setup template
     * 
     * @return  void
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function setUp()
    {

        $this->_plugin->initialize( $this->_table );
        $this->setUniques();

    } // Doctrine_Template_I18nMod::setUp()


    /**
     * Setup template
     * 
     * @return  Doctrine_I18n object
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function getI18n()
    {

        return $this->_plugin;

    } // Doctrine_Template_I18nMod::getI18n()


    /**
     * Set unique indexes
     * 
     * @return  void
     * @author  relo_san
     * @since   january 11, 2010
     */
    public function setUniques()
    {

        $options = $this->_plugin->getOptions();
        $table = $this->_plugin->getTable();
        if ( isset( $options['unique'] ) && $options['unique'] )
        {
            foreach ( $options['unique'] as $field )
            {
                if ( $table->hasColumn( $field ) )
                {
                    $table->unique( array( $field, 'lang' ) );
                }
            }
        }

    } // Doctrine_Template_I18nMod::setUniques()

} // Doctrine_Template_I18nMod

//EOF