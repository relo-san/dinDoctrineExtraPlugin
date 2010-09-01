[?php

<?php echo dinGeneratorSigner::getHeader() . "\n" ?>

/**
 * <?php echo $this->table->getOption( 'name' ) ?> filter form base class
 * 
 * @package     <?php echo dinGeneratorSigner::getProjectName() . "\n" ?>
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
    protected function setupInheritance()
    {

        parent::setupInheritance();

<?php
foreach ( $this->getColumns() as $column )
{
    echo "        \$this->widgetSchema['" . $column->getFieldName() . "']    = new ";
    echo $this->getWidgetClassForColumn( $column ) . "(" . $this->getWidgetOptionsForColumn( $column, 8 ) . ");\n";

    echo "        \$this->validatorSchema['" . $column->getFieldName() . "'] = ";
    echo $this->getValidatorForColumn( $column, 8 ) . ";\n\n";
}
foreach ( $this->getManyToManyRelations() as $relation )
{
    echo "        \$this->widgetSchema['" . $this->underscore( $relation['alias'] ) . "_list']    = ";
    echo "new sfWidgetFormDoctrineChoice( array(\n";
    echo "            'multiple' => true,\n";
    echo "            'model' => '" . $relation['table']->getOption( 'name' ) . "'\n        ) );\n";

    echo "        \$this->validatorSchema['" . $this->underscore( $relation['alias'] ) . "_list'] = ";
    echo "new sfValidatorDoctrineChoice( array(\n";
    echo "            'multiple' => true,\n";
    echo "            'model' => '" . $relation['table']->getOption( 'name' ) . "',\n";
    echo "            'required' => false\n        ) );\n";
}

echo "        \$this->widgetSchema->setNameFormat( '" . $this->underscore( $this->modelName ) . "_filters[%s]' );\n";

?>

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::setupInheritance()

<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>

    /**
     * Setup m-m <?php echo sfInflector::camelize( $relation['alias'] ) ?> relation query
     * 
     * @return  void
     */
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

<?php
echo "        \$query->leftJoin( 'r." . $relation['refTable']->getOption( 'name' ) . ' ';
echo $relation['refTable']->getOption( 'name' ) . "' )\n";
echo "            ->andWhereIn( '" . $relation['refTable']->getOption( 'name' ) . '.';
echo $relation->getForeignFieldName() . "', \$values );\n";
?>
    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::add<?php echo sfInflector::camelize( $relation['alias'] ) ?>ListColumnQuery()

<?php endforeach ?>

    /**
     * Get associated model name
     * 
     * @return  string  Class name of associated model
     */
    public function getModelName()
    {

        return '<?php echo $this->modelName ?>';

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::getModelName()
<?php if ( count( $this->getColumns() ) || count( $this->getManyToManyRelations() ) ): ?>


    /**
     * Get filter fields
     * 
     * @return  array   Fields with types
     */
    public function getFields()
    {

        return array(
<?php
foreach ( $this->getColumns() as $column )
{
    echo "            '" . $column->getFieldName() . "'";
    echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) );
    echo " => '" . $this->getType( $column ) . "',\n";
}
foreach ( $this->getManyToManyRelations() as $relation )
{
    echo "            '" . $this->underscore( $relation['alias'] ) . "_list'";
    echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) );
    echo " => 'ManyKey',\n";
}
?>
        );

    } // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter::getFields()
<?php endif ?>

} // Base<?php echo $this->table->getOption( 'name' ) ?>FormFilter

//EOF