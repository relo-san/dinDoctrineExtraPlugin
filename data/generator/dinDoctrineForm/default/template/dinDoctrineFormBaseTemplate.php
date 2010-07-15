[?php

<?php echo dinGeneratorSigner::getHeader() . "\n" ?>

/**
 * Project form base class
 * 
 * @package     <?php echo dinGeneratorSigner::getProjectName() . "\n" ?>
 * @subpackage  lib.form.doctrine
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
abstract class BaseFormDoctrine extends dinFormDoctrine
{

    /**
     * Setup form
     * 
     * @return  void
     */
    public function setup()
    {
    } // BaseFormDoctrine::setup()

} // BaseFormDoctrine

//EOF