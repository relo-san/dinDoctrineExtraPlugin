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
    protected function setupInheritance()
    {

        parent::setupInheritance();

<?php
foreach ( $this->getColumns() as $column )
{
    echo "        \$this->widgetSchema['" . $column->getFieldName() . "']    = new ";
    echo $this->getWidgetClassForColumn( $column ) . "(" . $this->getWidgetOptionsForColumn( $column, 8 ) . ");\n";

    echo "        \$this->validatorSchema['" . $column->getFieldName() . "'] = ";
    echo $this->getWidgetClassForColumn( $column ) . "(" . $this->getValidatorOptionsForColumn( $column, 8 ) . ");\n\n";
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

echo "        \$this->widgetSchema->setNameFormat( '" . $this->underscore( $this->modelName ) . "[%s]' );\n";

?>

    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::setupInheritance()


    /**
     * Get associated model name
     * 
     * @return  string  Class name of associated model
     */
    public function getModelName()
    {

        return '<?php echo $this->modelName ?>';

    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::getModelName()

<?php if ( $this->getManyToManyRelations() ): ?>

    /**
     * Update defaults from object with relations
     * 
     * @return  void
     */
    public function updateDefaultsFromObject()
    {

        parent::updateDefaultsFromObject();

<?php
foreach ( $this->getManyToManyRelations() as $relation )
{
    echo "        if ( isset( \$this->widgetSchema['" . $this->underscore( $relation['alias'] ) . "_list'] ) )\n";
    echo "        {\n";
    echo "            \$this->setDefault( '" . $this->underscore( $relation['alias'] ) . "_list', \$this->object->" . $relation['alias'] . "->getPrimaryKeys() );\n";
    echo "        }\n";
}
?>

    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::updateDefaultsFromObject()


    /**
     * Save objects
     * 
     * @param   Doctrine_Connection $con    [optional]
     * @return  void
     */
    protected function doSave( $con = null )
    {

<?php
foreach ( $this->getManyToManyRelations() as $relation )
{
    echo "        \$this->save" . $relation['alias'] . "List( \$con );\n";
}
?>

        parent::doSave( $con );

    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::doSave()
<?php foreach ( $this->getManyToManyRelations() as $relation ): ?>


    /**
     * Save related objects
     * 
     * @param   Doctrine_Connection $con    [optional]
     * @return  void
     */
    public function save<?php echo $relation['alias'] ?>List( $con = null )
    {

<?php
echo "        if ( !\$this->isValid() )\n";
echo "        {\n";
echo "            throw \$this->getErrorSchema();\n";
echo "        }\n\n";

echo "        if ( !isset( \$this->widgetSchema['" . $this->underscore( $relation['alias'] ) . "_list'] ) )\n";
echo "        {\n";
echo "            // somebody has unset this widget\n";
echo "            return;\n";
echo "        }\n\n";

echo "        if ( null === \$con )\n";
echo "        {\n";
echo "            \$con = \$this->getConnection();\n";
echo "        }\n\n";

echo "        \$existing = \$this->object->" . $relation['alias'] . "->getPrimaryKeys();\n";
echo "        \$values = \$this->getValue( '" . $this->underscore( $relation['alias'] ) . "_list' );\n";
echo "        if ( !is_array( \$values ) )\n";
echo "        {\n";
echo "            \$values = array();\n";
echo "        }\n\n";

echo "        \$unlink = array_diff( \$existing, \$values );\n";
echo "        if ( count( \$unlink ) )\n";
echo "        {\n";
echo "            \$this->object->unlink( '" . $relation['alias'] . "', array_values( \$unlink ) );\n";
echo "        }\n\n";

echo "        \$link = array_diff( \$values, \$existing );\n";
echo "        if ( count( \$link ) )\n";
echo "        {\n";
echo "            \$this->object->link( '" . $relation['alias'] . "', array_values( \$link ) );\n";
echo "        }\n";

?>

    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::save<?php echo $relation['alias'] ?>List()
<?php endforeach ?>

<?php endif ?>
} // Base<?php echo $this->table->getOption( 'name' ) ?>Form

//EOF