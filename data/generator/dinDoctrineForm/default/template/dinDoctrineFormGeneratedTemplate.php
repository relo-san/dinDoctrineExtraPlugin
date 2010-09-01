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

<?php
echo "        \$this->setWidgets( array(\n";
foreach ( $this->getColumns() as $column )
{
    echo "            '" . $column->getFieldName() . "'";
    echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) );
    echo ' => new ' . $this->getWidgetClassForColumn( $column ) . '(';
    echo $this->getWidgetOptionsForColumn( $column, 12 ) . "),\n";
}
foreach ( $this->getManyToManyRelations() as $relation )
{
    echo "            '" . $this->underscore( $relation['alias'] ) . "_list'";
    echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) );
    echo " => new sfWidgetFormDoctrineChoice( array(\n";
    echo "                'multiple' => true,\n";
    echo "                'model' => '" . $relation['table']->getOption( 'name' ) . "'\n";
    echo "            ) ),\n";
}
echo "        ) );\n\n";

echo "        \$this->setValidators( array(\n";
foreach ( $this->getColumns() as $column )
{
    echo "            '" . $column->getFieldName() . "'";
    echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $column->getFieldName() ) );
    echo ' => new ' . $this->getValidatorClassForColumn( $column ) . '(';
    echo $this->getValidatorOptionsForColumn( $column, 12 ) . "),\n";
}
foreach ( $this->getManyToManyRelations() as $relation )
{
    echo "            '" . $this->underscore( $relation['alias'] ) . "_list'";
    echo str_repeat( ' ', $this->getColumnNameMaxLength() - strlen( $this->underscore( $relation['alias'] ) . '_list' ) );
    echo " => new sfValidatorDoctrineChoice( array(\n";
    echo "                'multiple' => true,\n";
    echo "                'model' => '" . $relation['table']->getOption( 'name' ) . "',\n";
    echo "                'required' => false\n";
    echo "            ) ),\n";
}
echo "        ) );\n\n";

if ( $uniqueColumns = $this->getUniqueColumnNames() )
{
echo "        \$this->validatorSchema->setPostValidator(\n";
    if ( count( $uniqueColumns ) > 1 )
    {
        echo "            new sfValidatorAnd( array(\n";
        foreach ( $uniqueColumns as $uniqueColumn )
        {
            echo "                new sfValidatorDoctrineUnique( array(\n";
            echo "                    'model' => '" . $this->table->getOption( 'name' ) . "',\n";
            echo "                    'column' => array( '" . implode( "', '", $uniqueColumn ) . "' )\n";
            echo "                ) ),\n";
        }
        echo "            ) )\n";
    }
    else
    {
        echo "            new sfValidatorDoctrineUnique( array(\n";
        echo "                'model' => '" . $this->table->getOption( 'name' ) . "',\n";
        echo "                'column' => array( '" . implode( "', '", $uniqueColumns[0] ) . "' )\n";
        echo "            ) )\n";
    }
echo "        );\n\n";
}

if ( $this->isI18n() )
{
    echo "        \$this->embedI18n( sfConfig::get( 'sf_active_languages', array( 'en' ) ) );\n\n";
}

echo "        \$this->widgetSchema->setNameFormat( '" . $this->underscore( $this->modelName ) . "[%s]' );\n\n";

echo "        \$this->errorSchema = new sfValidatorErrorSchema( \$this->validatorSchema );\n\n";

echo "        \$this->setupInheritance();\n\n";
?>
        parent::setup();

    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::setup()


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

<?php endif ?>
<?php if ( $this->table->hasTemplate( 'NestedSet' ) ): ?>

    public function updateParentIdColumn( $parentId )
    {

        <?php echo "\$this->parentId = \$parentId;\n" ?>

    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::updateParentIdColumn()

<?php endif ?>
<?php if ( $this->getManyToManyRelations() || $this->table->hasTemplate( 'NestedSet' ) ): ?>

    /**
     * Save objects
     * 
     * @param   Doctrine_Connection $con    [optional]
     * @return  void
     */
    protected function doSave( $con = null )
    {

<?php
if ( $this->getManyToManyRelations() )
{
    foreach ( $this->getManyToManyRelations() as $relation )
    {
        echo "        \$this->save" . $relation['alias'] . "List( \$con );\n";
    }
    echo "\n";
}
echo "        parent::doSave( \$con );\n\n";

if ( $this->table->hasTemplate( 'NestedSet' ) )
{
    echo "        \$node = \$this->object->getNode();\n\n";

    echo "        if ( \$this->parentId != \$this->object->getParentId() || !\$node->isValidNode() )\n";
    echo "        {\n";
    echo "            if ( empty( \$this->parentId ) )\n";
    echo "            {\n";
    echo "                if ( \$node->isValidNode() )\n";
    echo "                {\n";
    echo "                    \$node->makeRoot( \$this->object->getId() );\n";
    echo "                    \$this->object->save( \$con );\n";
    echo "                }\n";
    echo "                else\n";
    echo "                {\n";
    echo "                    \$this->object->getTable()->getTree()->createRoot( \$this->object );\n";
    echo "                }\n";
    echo "            }\n";
    echo "            else\n";
    echo "            {\n";
    echo "                \$parent = \$this->object->getTable()->find( \$this->parentId );\n";
    echo "                \$method = ( \$node->isValidNode() ? 'move' : 'insert' ) . 'AsLastChildOf';\n";
    echo "                \$node->\$method( \$parent );\n";
    echo "            }\n";
    echo "        }\n\n";
}
?>
    } // Base<?php echo $this->table->getOption( 'name' ) ?>Form::doSave()

<?php endif ?>
<?php if ( $this->getManyToManyRelations() ): ?>
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