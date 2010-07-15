[?php

<?php echo dinGeneratorSigner::getHeader() . "\n" ?>

/**
 * <?php echo $this->modelName ?> form base class
 * 
 * @method  <?php echo $this->modelName ?> getObject() Returns the current form's model object
 * 
 * @package     <?php echo dinGeneratorSigner::getProjectName() . "\n" ?>
 * @subpackage  lib.form.doctrine.<?php echo $this->getPluginNameForModel( $this->table->getOption( 'name' ) ) ?>.base
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
abstract class Base<?php echo $this->modelName ?>Form extends <?php echo $this->getFormClassToExtend() . "\n" ?>
{

    /**
     * Setup form
     * 
     * @return  void
     */
    public function setup()
    {

        $this-<?php echo '>' ?>setWidgets( array(
<?php foreach ( $this->getColumns() as $column ): ?>
            '<?php echo $column->getFieldName() ?>'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) ) ?> => new <?php echo $this->getWidgetClassForColumn( $column ) ?>(<?php echo $this->getWidgetOptionsForColumn( $column ) ?>),
<?php endforeach ?>
<?php foreach ( $this->getManyToManyRelations() as $relation): ?>
            '<?php echo $this->underscore( $relation['alias'] ) ?>_list'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) ) ?> => new sfWidgetFormDoctrineChoice( array( 'multiple' => true, 'model' => '<?php echo $relation['table']->getOption('name') ?>' ) ),
<?php endforeach ?>
        ) );

        $this->setValidators( array(
<?php foreach ( $this->getColumns() as $column ): ?>
            '<?php echo $column->getFieldName() ?>'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) ) ?> => new <?php echo $this->getValidatorClassForColumn( $column ) ?>(<?php echo $this->getValidatorOptionsForColumn( $column ) ?>),
<?php endforeach ?>
<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
            '<?php echo $this->underscore( $relation['alias'] ) ?>_list'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) ) ?> => new sfValidatorDoctrineChoice( array( 'multiple' => true, 'model' => '<?php echo $relation['table']->getOption( 'name' ) ?>', 'required' => false ) ),
<?php endforeach ?>
        ) );

<?php if ( $uniqueColumns = $this->getUniqueColumnNames() ): ?>
        $this->validatorSchema->setPostValidator(
<?php if ( count( $uniqueColumns ) > 1 ): ?>
            new sfValidatorAnd( array(
<?php foreach ($uniqueColumns as $uniqueColumn): ?>
                new sfValidatorDoctrineUnique( array( 'model' => '<?php echo $this->table->getOption( 'name' ) ?>', 'column' => array( '<?php echo implode("', '", $uniqueColumn ) ?>' ) ) ),
<?php endforeach; ?>
            ) )
<?php else: ?>
            new sfValidatorDoctrineUnique( array( 'model' => '<?php echo $this->table->getOption( 'name' ) ?>', 'column' => array( '<?php echo implode("', '", $uniqueColumns[0] ) ?>' ) ) )
<?php endif ?>
        );
<?php endif ?>

<?php if ( $this->isI18n() ): ?>
        <?php echo '$this->' ?>embedI18n( dinConfig::getActiveLanguages() );
<?php endif ?>

        <?php echo '$this->widgetSchema->' ?>setNameFormat( '<?php echo $this->underscore( $this->modelName ) ?>[%s]' );

        <?php echo '$this->' ?>errorSchema = new sfValidatorErrorSchema( <?php echo '$this->' ?>validatorSchema );

        <?php echo '$this->' ?>setupInheritance();

        parent::setup();

    } // Base<?php echo $this->modelName ?>Form::setup()


    /**
     * Get model name
     * 
     * @return  string  Model name
     */
    public function getModelName()
    {

        return '<?php echo $this->modelName ?>';

    } // Base<?php echo $this->modelName ?>Form::getModelName()


<?php if ( $this->getManyToManyRelations() ): ?>
    public function updateDefaultsFromObject()
    {

        parent::updateDefaultsFromObject();

<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
        if ( isset( $this->widgetSchema['<?php echo $this->underscore($relation['alias']) ?>_list'] ) )
        {
            $this->setDefault( '<?php echo $this->underscore($relation['alias']) ?>_list', $this->object-><?php echo $relation['alias']; ?>->getPrimaryKeys() );
        }

<?php endforeach ?>
    } // Base<?php echo $this->modelName ?>Form::updateDefaultsFromObject()


    protected function doSave( $con = null )
    {

<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
        $this->save<?php echo $relation['alias'] ?>List( $con );
<?php endforeach ?>

        parent::doSave( $con );

    } // Base<?php echo $this->modelName ?>Form::doSave()


<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
    public function save<?php echo $relation['alias'] ?>List( $con = null )
    {
        if ( !$this->isValid() )
        {
            throw $this->getErrorSchema();
        }

        if ( !isset( $this->widgetSchema['<?php echo $this->underscore($relation['alias']) ?>_list'] ) )
        {
            // somebody has unset this widget
            return;
        }

        if ( null === $con )
        {
            $con = $this->getConnection();
        }

        $existing = $this->object-><?php echo $relation['alias']; ?>->getPrimaryKeys();
        $values = $this->getValue( '<?php echo $this->underscore( $relation['alias'] ) ?>_list' );
        if ( !is_array( $values ) )
        {
            $values = array();
        }

        $unlink = array_diff( $existing, $values );
        if ( count( $unlink ) )
        {
            $this->object->unlink( '<?php echo $relation['alias'] ?>', array_values( $unlink ) );
        }

        $link = array_diff( $values, $existing );
        if ( count( $link ) )
        {
            $this->object->link( '<?php echo $relation['alias'] ?>', array_values( $link ) );
        }

    } // Base<?php echo $this->modelName ?>Form::save()

<?php endforeach ?>
<?php endif ?>
} // Base<?php echo $this->modelName ?>Form

//EOF