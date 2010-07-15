[?php

<?php echo dinGeneratorSigner::getHeader() . "\n" ?>

/**
 * Base project filter form for doctrine
 * 
 * @package     <?php echo dinGeneratorSigner::getProjectName() . "\n" ?>
 * @subpackage  lib.filter.doctrine
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
abstract class BaseFormFilterDoctrine extends dinFormFilterDoctrine
{

    /**
     * Setup form
     * 
     * @return  void
     */
    public function setup()
    {
    } // BaseFormFilterDoctrine::setup()

} // BaseFormFilterDoctrine

//EOF