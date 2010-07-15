<?php

/*
 * This file is part of the dinDoctrineExtraPlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Signer for generators
 * 
 * @package     dinDoctrineExtraPlugin
 * @subpackage  lib.generator
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinGeneratorSigner
{

    static protected
        $isLoaded = false,
        $info = array();


    /**
     * Get header content
     * 
     * @param   string  $type   Destination file type [optional, default is php]
     * @return  string  Header content
     */
    static public function getHeader( $type = 'php' )
    {

        if ( !self::$isLoaded )
        {
            self::loadConfig();
        }

        return isset( self::$info['header'][$type] ) ? self::$info['header'][$type] : '';

    } // dinGeneratorSigner::getHeader()


    /**
     * Get header content for plugins
     * 
     * @param   string  $pluginName Plugin name
     * @param   string  $type       Destination file type [optional, default is php]
     * @return  string  Header content
     */
    static public function getPluginHeader( $pluginName, $type = 'php' )
    {

        if ( !self::$isLoaded )
        {
            self::loadConfig();
        }

        return isset( self::$info['pluginHeader'][$type] )
            ? str_replace( '#pluginName#', $pluginName,  self::$info['pluginHeader'][$type] )
            : '';

    } // dinGeneratorSigner::getHeader()


    /**
     * Get project name
     * 
     * @return  string  Project (package) name
     */
    static public function getProjectName()
    {

        if ( !self::$isLoaded )
        {
            self::loadConfig();
        }

        return isset( self::$info['name'] ) ? self::$info['name'] : '';

    } // dinGeneratorSigner::getProjectName()


    /**
     * Get author
     * 
     * @return  string  Author name
     */
    static public function getAuthor()
    {

        if ( !self::$isLoaded )
        {
            self::loadConfig();
        }

        return isset( self::$info['author'] ) ? self::$info['author'] : '';

    } // dinGeneratorSigner::getAuthor()


    /**
     * Load sign configuration
     * 
     * @return  void
     */
    static private function loadConfig()
    {

        self::$info = sfYaml::load( sfConfig::get( 'sf_lib_dir' ) . '/config/sign.yml' );
        self::$isLoaded = true;

    } // dinGeneratorSigner::loadConfig()

} // dinGeneratorSigner

//EOF