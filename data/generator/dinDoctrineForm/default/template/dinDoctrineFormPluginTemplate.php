[?php

<?php echo dinGeneratorSigner::getPluginHeader( $this->getPluginNameForModel( $this->table->getOption( 'name' ) ) ) . "\n" ?>

/**
 * Form class for <?php echo $this->table->getOption( 'name' ) ?> object
 * 
 * @package     <?php echo $this->getPluginNameForModel( $this->table->getOption( 'name' ) ) . "\n" ?>
 * @subpackage  lib.form.doctrine
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
abstract class Plugin<?php echo $this->table->getOption( 'name' ) ?>Form extends Base<?php echo $this->table->getOption( 'name' ) ?>Form
{
} // Plugin<?php echo $this->table->getOption( 'name' ) ?>Form

//EOF