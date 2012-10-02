<?php
/**
 * @package     
 * @subpackage  
 * @author      Brice Tencé
 * @copyright   2012 Brice Tencé
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */


/**
 * interface for plugins for jPhpsass plugin
 */
class jPhpsassCompassPlugin implements IjPhpsassPlugin {

    public function __construct(jPhpsassCSSpreproPlugin $jPhpsassInstance) {
    }

    /**
     * returns an array of functions handled by the plugin : array('functionName' => array($class, 'method'))
     */
    public function getPhpsassFunctions( ) {
        return sassy_compass_sassy_functions();
    }

    /**
     * return a string (or NULL if it does not exist) of the plugin Sass file corresponding to the filename
     */
    public function resolvePath( $filename, $syntax='scss' ) {
    }
}
