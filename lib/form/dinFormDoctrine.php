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
 * @package     dinDoctrineExtraPlugin
 * @subpackage  lib.form
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
abstract class dinFormDoctrine  extends sfFormDoctrine
{

    /**
     * Returns true if the current form has some associated i18n objects
     * 
     * @return  boolean     Is the current form has some associated i18n objects
     */
    public function isI18n()
    {

        return $this->getObject()->getTable()->isI18n();

    } // dinFormDoctrine::isI18n()


    /**
     * Returns the name of the i18n model
     * 
     * @return  string  The name of the i18n model
     */
    public function getI18nModelName()
    {

        return $this->getI18nTemplate()->getI18n()->getOption( 'className' );

    } // dinFormDoctrine::getI18nModelName()


    /**
     * Returns the i18nField name of the i18n model
     * 
     * @return  string  The i18nField name of the i18n model
     */
    public function getI18nModelI18nField()
    {

        return $this->getI18nTemplate()->getI18n()->getOption( 'i18nField' );

    } // dinFormDoctrine::getI18nModelI18nField()


    /**
     * Get i18n template
     * 
     * @return  Doctrine_Template_I18n
     */
    public function getI18nTemplate()
    {

        return $this->getObject()->getTable()->getTemplate( 'Doctrine_Template_I18nMod' );

    } // dinFormDoctrine::getI18nTemplate()


    /**
     * Save embedded form objects
     * 
     * @param   mixed   $conn   Connection object [optional]
     * @param   array   $forms  Embedded forms [optional]
     * @return  void
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
     */
    public function preSave()
    {
    } // dinFormDoctrine::preSave()

} // dinFormDoctrine

//EOF