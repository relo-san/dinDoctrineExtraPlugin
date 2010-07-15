<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Widget for Jquery Autochanger with Doctrine support
 * 
 * @package     dinDoctrineExtraPlugin.lib.widget
 * @signed      5
 * @signer      relo_san
 * @author      relo_san [http://relo-san.com/]
 * @since       march 8, 2010
 * @version     SVN: $Id: dinWidgetFormJqueryDoctrineAutochanger.class.php 34 2010-03-08 12:29:22Z relo_san $
 */
class dinWidgetFormJqueryDoctrineAutochanger extends sfWidgetFormChoice
{

    /**
     * Configure widget
     * 
     * @param   array   $options    An array of options [optional]
     * @param   array   $attributes An array of default HTML attributes [optional]
     * @return  void
     * @author  relo_san
     * @since   february 8, 2010
     * @see     sfWidgetForm
     */
    protected function configure( $options = array(), $attributes = array() )
    {

        $this->addRequiredOption( 'url' );
        $this->addRequiredOption( 'parent' );

        parent::configure( $options, $attributes );

    } // dinWidgetFormJqueryAutocompleter::configure()


    /**
     * Render field
     * 
     * @param   string  $name       Element name
     * @param   string  $value      Element value
     * @param   array   $attributes HTML attributes [optional]
     * @param   array   $errors     Errors for the field [optional]
     * @return  string  XHTML compliant tag
     * @author  relo_san
     * @since   february 8, 2010
     */
    public function render( $name, $value = null, $attributes = array(), $errors = array() )
    {

        $s[] = '<script type="text/javascript">';
        $s[] = 'jQuery(document).ready(function(){';
        $s[] = '$(\'#' . $this->generateId( $this->getOption( 'parent' ) ) . '\').change(function(){';
        $s[] = 'var objectId=' . ( $value ? $value : 'null' ) . ';$.getJSON(\'' . $this->getOption( 'url' ) . '\',';
        $s[] = '{id:$(\'#' . $this->generateId( $this->getOption( 'parent' ) ) . '\').val(),ajax:\'true\'},';
        $s[] = 'function(j){var options=\'\';for(var i=0;i<j.length;i++){';
        $s[] = 'options+=\'<option value="\'+j[i].id+\'"\'+(j[i].id==objectId?\' selected=[selected]\':\'\')+\'>\'+j[i].value+\'</option>\';';
        $s[] = '}$(\'select#' . $this->generateId( $name ) . '\').html(options);});});});';
        $s[] = '</script>';

        return parent::render( $name, $value, $attributes, $errors ) . implode( $s );

    } // dinWidgetFormJqueryAutocompleter::render()

    

} // dinWidgetFormJqueryDoctrineAutochanger

//EOF