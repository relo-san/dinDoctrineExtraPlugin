[?php

<?php echo dinGeneratorSigner::getHeader() . "\n" ?>

/**
 * Form class for <?php echo $this->table->getOption( 'name' ) ?> object
 * 
 * @package     <?php echo dinGeneratorSigner::getProjectName() . "\n" ?>
 * @subpackage  lib.form.doctrine
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
class <?php echo $this->table->getOption( 'name' ) ?>Form extends Base<?php echo $this->table->getOption( 'name' ) ?>Form
{

    /**
     * Configure form
     * 
     * @return  void
<?php if ( $parent = $this->getParentModel() ): ?>
     * @see <?php echo $parent ?>Form
<?php endif ?>
     */
    public function configure()
    {
<?php if ( $parent ): ?>

        parent::configure();

<?php endif ?>
    } // <?php echo $this->table->getOption( 'name' ) ?>Form::configure()

} // <?php echo $this->table->getOption( 'name' ) ?>Form

//EOF