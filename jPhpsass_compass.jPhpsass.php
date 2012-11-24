<?php
/**
 * @package     
 * @subpackage  
 * @author      Brice Tencé
 * @copyright   2012 Brice Tencé
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

require_once('compassFunctions.class.php');

/**
 * interface for plugins for jPhpsass plugin
 */
class jPhpsassPlugin_compass extends compassFunctions implements IjPhpsassPlugin {

    public function __construct(jPhpsassCSSpreproPlugin $jPhpsassInstance) {
    }

    /**
     * returns an array of functions handled by the plugin : array('functionName' => array($class, 'method'))
     */
    public function getPhpsassFunctions( ) {
        $compassFunctions = array();
        foreach( $this->sassy_compass_sassy_functions() as $compassFunctionInfos ) {
            $compassFunctions[ $compassFunctionInfos['name'] ] = array($this, $compassFunctionInfos['callback']);
        }
        return $compassFunctions;
    }

    /**
     * return a string (or NULL if it does not exist) of the plugin Sass file corresponding to the filename
     */
    public function resolvePath( $filename, $syntax='scss' ) {
        
        // Check for compass installed as a Library, if not use ours.
        // The latest Compass build can be found at https://github.com/chriseppstein/compass
        $currentPluginPath = jApp::config()->_pluginsPathList_CSSprepro['jPhpsass_compass'];
        $path = $currentPluginPath;

        if ($filename == '*') {
            $filename = 'compass';
        }

        $filename = str_replace(array('.scss', '.sass'), '', $filename);
        $split = explode('/', $filename);
        if ($split[0] != 'compass' && $split[0] != 'lemonade') {
            array_unshift($split, 'compass');
        }
        $last = array_pop($split) . '.scss';
        if (substr($last, 0, 1) != '_') {
            $last = '_' . $last;
        }
        array_unshift($split, 'stylesheets');
        array_unshift($split, $path);
        $filename = str_replace('/_', '/', implode('/', $split)) . '/' . $last;

        return $filename;
    }
}
