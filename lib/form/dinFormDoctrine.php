<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Base form class
 * 
 * @package     dinDoctrineExtraPlugin.lib.form
 * @signed      5
 * @signer      relo_san
 * @author      relo_san [http://relo-san.com/]
 * @since       february 17, 2010
 * @version     SVN: $Id: dinFormDoctrine.class.php 24 2010-02-19 11:30:25Z relo_san $
 */
abstract class dinFormDoctrine  extends sfFormDoctrine
{

    /**
     * Returns true if the current form has some associated i18n objects
     * 
     * @return  boolean     Is the current form has some associated i18n objects
     * @author  relo_san
     * @since   february 17, 2010
     */
    public function isI18n()
    {

        return $this->getObject()->getTable()->isI18n();

    } // dinFormDoctrine::isI18n()


    /**
     * Returns the name of the i18n model
     * 
     * @return  string  The name of the i18n model
     * @author  relo_san
     * @since   february 17, 2010
     */
    public function getI18nModelName()
    {

        return $this->getObject()->getTable()->getTemplate( 'Doctrine_Template_I18nMod' )
            ->getI18n()->getOption( 'className' );

    } // dinFormDoctrine::getI18nModelName()


    /**
     * Save embedded form objects
     * 
     * @param   mixed   $conn   Connection object [optional]
     * @param   array   $forms  Embedded forms [optional]
     * @return  void
     * @author  relo_san
     * @since   february 19, 2010
     */
    public function saveEmbeddedForms( $conn = null, $forms = null )
    {

        if ( null === $conn )
        {
            $conn = $this->getConnection();
        }

        if ( null === $forms )
        {
            $forms = $this->embeddedForms;
        }

        foreach ( $forms as $form )
        {
            if ( $form instanceof sfFormObject )
            {
                $form->saveEmbeddedForms( $conn );
                $form->preSave();
                $form->getObject()->save( $conn );
            }
            else
            {
                $this->saveEmbeddedForms( $conn, $form->getEmbeddedForms() );
            }
        }

    } // dinFormDoctrine::saveEmbeddedForms()


    /**
     * Save form object
     * 
     * @param   mixed   $conn   Connection object [optional]
     * @return  mixed   The current saved object
     * @author  relo_san
     * @since   february 19, 2010
     */
    public function save( $conn = null )
    {

        if ( !$this->isValid() )
        {
            throw $this->getErrorSchema();
        }

        if ( null === $conn )
        {
            $conn = $this->getConnection();
        }

        try
        {
            $conn->beginTransaction();
            $this->preSave();
            $this->doSave( $conn );
            $conn->commit();
        }
        catch ( Exception $e )
        {
            $conn->rollBack();
            throw $e;
        }

        return $this->getObject();

    } // dinFormDoctrine::save()


    /**
     * Prepare save
     * 
     * @return  void
     * @author  relo_san
     * @since   february 19, 2010
     */
    public function preSave()
    {
    } // dinFormDoctrine::preSave()

} // dinFormDoctrine

//EOF