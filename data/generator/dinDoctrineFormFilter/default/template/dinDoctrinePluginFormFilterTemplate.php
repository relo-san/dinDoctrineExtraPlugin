[?php

<?php echo dinGeneratorSigner::getHeader() . "\n" ?>

/**
 * Filter form class for <?php echo $this->table->getOption( 'name' ) ?> object
 * 
 * @package     <?php echo dinGeneratorSigner::getProjectName() . "\n" ?>
 * @subpackage  lib.filter.doctrine.<?php echo $this->getPluginNameForModel( $this->table->getOption( 'name' ) ) . "\n" ?>
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
class <?php echo $this->table->getOption( 'name' ) ?>FormFilter extends Plugin<?php echo $this->table->getOption( 'name' ) ?>FormFilter
{

    /**
     * Configure form
     * 
     * @return  void
<?php if ( $parent = $this->getParentModel() ): ?>
     * @see <?php echo $parent ?>FormFilter
<?php endif ?>
     */
    public function configure()
    {
<?php if ( $parent ): ?>

        parent::configure();

<?php endif ?>
    } // <?php echo $this->table->getOption( 'name' ) ?>FormFilter::configure()

} // <?php echo $this->table->getOption( 'name' ) ?>FormFilter

//EOF