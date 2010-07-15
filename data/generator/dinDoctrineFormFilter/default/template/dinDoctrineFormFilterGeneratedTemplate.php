[?php

<?php echo dinGeneratorSigner::getHeader() . "\n" ?>

/**
 * <?php echo $this->table->getOption( 'name' ) ?> filter form base class
 * 
 * @package     <?php echo dinGeneratorSigner::getProjectName() ?>
 * @subpackage  lib.filter.doctrine.<?php echo $this->getPluginNameForModel( $this->table->getOption( 'name' ) ) ?>.base
 * @author      <?php echo dinGeneratorSigner::getAuthor() . "\n" ?>
 */
abstract class Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter extends <?php echo $this->getFormClassToExtend() . "\n" ?>
{

    /**
     * Setup filter form
     * 
     * @return  void
     */
    public function setup()
    {

        $this->setWidgets( array(
<?php foreach ( $this->getColumns() as $column ): ?>
<?php if ( $column->isPrimaryKey() ) continue ?>
            '<?php echo $column->getFieldName() ?>'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) ) ?> => new <?php echo $this->getWidgetClassForColumn( $column ) ?>(<?php echo $this->getWidgetOptionsForColumn( $column ) ?>),
<?php endforeach ?>
<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
            '<?php echo $this->underscore( $relation['alias'] ) ?>_list'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) ) ?> => new sfWidgetFormDoctrineChoice( array( 'multiple' => true, 'model' => '<?php echo $relation['table']->getOption( 'name' ) ?>' ) ),
<?php endforeach ?>
        ) );

        $this->setValidators( array(
<?php foreach ( $this->getColumns() as $column ): ?>
<?php if ( $column->isPrimaryKey() ) continue ?>
            '<?php echo $column->getFieldName() ?>'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) ) ?> => <?php echo $this->getValidatorForColumn( $column ) ?>,
<?php endforeach ?>
<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
            '<?php echo $this->underscore( $relation['alias'] ) ?>_list'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) ) ?> => new sfValidatorDoctrineChoice( array( 'multiple' => true, 'model' => '<?php echo $relation['table']->getOption( 'name' ) ?>', 'required' => false ) ),
<?php endforeach ?>
        ) );

        $this->widgetSchema->setNameFormat( '<?php echo $this->underscore( $this->modelName ) ?>_filters[%s]' );

        $this->errorSchema = new sfValidatorErrorSchema( $this->validatorSchema );

        $this->setupInheritance();

        parent::setup();

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::setup()


<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
    public function add<?php echo sfInflector::camelize( $relation['alias'] ) ?>ListColumnQuery( Doctrine_Query $query, $field, $values )
    {

        if ( !is_array( $values ) )
        {
            $values = array( $values );
        }

        if ( !count( $values ) )
        {
            return;
        }

        $query->leftJoin( 'r.<?php echo $relation['refTable']->getOption( 'name' ) ?> <?php echo $relation['refTable']->getOption( 'name' ) ?>' )
            ->andWhereIn( '<?php echo $relation['refTable']->getOption( 'name' ) ?>.<?php echo $relation->getForeignFieldName() ?>', $values );

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::add<?php echo sfInflector::camelize( $relation['alias'] ) ?>ListColumnQuery()


<?php endforeach ?>
    public function getModelName()
    {

        return '<?php echo $this->modelName ?>';

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::getModelName()


    public function getFields()
    {

        return array(
<?php foreach ( $this->getColumns() as $column ): ?>
            '<?php echo $column->getFieldName() ?>'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) ) ?> => '<?php echo $this->getType( $column ) ?>',
<?php endforeach ?>
<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>
            '<?php echo $this->underscore( $relation['alias'] ) ?>_list'<?php echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) ) ?> => 'ManyKey',
<?php endforeach ?>
        ) + $this->getExtraFields();

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::getFields()


    public function getExtraFields()
    {

        return array();

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::getExtraFields()

} // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter

//EOF