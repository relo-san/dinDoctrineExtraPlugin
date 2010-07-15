<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Widget for Jquery Autocompleter with Doctrine support
 * 
 * @package     dinDoctrineExtraPlugin.lib.widget
 * @signed      5
 * @signer      relo_san
 * @author      relo_san [http://relo-san.com/]
 * @since       march 7, 2010
 * @version     SVN: $Id: dinWidgetFormJqueryDoctrineAutocompleter.class.php 32 2010-03-07 10:27:32Z relo_san $
 */
class dinWidgetFormJqueryDoctrineAutocompleter extends dinWidgetFormJqueryAutocompleter
{

    /**
     * Configure widget
     * 
     * @param   array   $options    An array of options [optional]
     * @param   array   $attributes An array of default HTML attributes [optional]
     * @return  void
     * @author  relo_san
     * @since   march 7, 2010
     * @see     sfWidgetForm
     */
    protected function configure( $options = array(), $attributes = array() )
    {

        $this->addOption( 'model' );
        $this->addOption( 'method' );
        parent::configure( $options, $attributes );

    } // dinWidgetFormJqueryDoctrineAutocompleter::configure()


    /**
     * Get visible value
     * 
     * @param   mixed   $value  Source value
     * @return  string  Visible value
     * @author  relo_san
     * @since   march 7, 2010
     */
    protected function getVisibleValue( $value )
    {

        if ( $this->getOption( 'model' ) && $this->getOption( 'method' ) )
        {
            $method = $this->getOption( 'method' );
            $obj = Doctrine::getTable( $this->getOption( 'model' ) )->find( $value );
            if ( $obj && method_exists( $obj, $method ) )
            {
                return $obj->$method();
            }
        }
        return parent::getVisibleValue( $value );

    } // dinWidgetFormJqueryDoctrineAutocompleter::getVisibleValue()

} // dinWidgetFormJqueryDoctrineAutocompleter

//EOF