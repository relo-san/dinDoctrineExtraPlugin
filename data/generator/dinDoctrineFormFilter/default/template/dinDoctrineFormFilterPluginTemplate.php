[?php

<?php echo dinGeneratorSigner::getPluginHeader( $this->getPluginNameForModel( $this->table->getOption( 'name' ) ) ) . "\n" ?>

/**
 * Filter form class for <?php echo $this->table->getOption( 'name' ) ?> object
 * 
 * @package     <?php echo $this->getPluginNameForModel( $this->table->getOption( 'name' ) ) . "\n" ?>
 * @subpackage  lib.filter.doctrine
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
abstract class Plugin<?php echo $this->table->getOption( 'name' ) ?>FormFilter extends Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter
{
} // Plugin<?php echo $this->table->getOption( 'name' ) ?>FormFilter

//EOF