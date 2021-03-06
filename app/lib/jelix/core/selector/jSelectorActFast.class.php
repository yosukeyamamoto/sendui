<?php
/**
* see jISelector.iface.php for documentation about selectors. 
* @package     jelix
* @subpackage  core_selector
* @author      Laurent Jouanneau
* @contributor Thibault PIRONT < nuKs >
* @copyright   2005-2007 Laurent Jouanneau
* @copyright   2007 Thibault PIRONT
* @link        http://www.jelix.org
* @licence    GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * Special Action selector for jcoordinator
 * Don't use it ! Only for internal purpose.
 * @internal
 * @package    jelix
 * @subpackage core_selector
 */
class jSelectorActFast extends jSelectorModule {
    protected $type = 'act';
    public $request = '';
    public $controller = '';
    public $method='';
    protected $_dirname='actions/';

    /**
     */
    function __construct($request, $module, $action){
        $this->module = $module;
        $r = explode(':',$action);
        if(count($r) == 1){
            $this->controller = 'default';
            $this->method = $r[0]==''?'index':$r[0];
        }else{
            $this->controller = $r[0]=='' ? 'default':$r[0];
            $this->method = $r[1]==''?'index':$r[1];
        }
        $this->resource = $this->controller.':'.$this->method;
        $this->request = $request;
        $this->_createPath();
    }

    protected function _createPath(){
        global $gJConfig;
        if(!isset($gJConfig->_modulesPathList[$this->module])){
            throw new jExceptionSelector('jelix~errors.selector.module.unknow', $this->toString());
        }else{
            $this->_path = $gJConfig->_modulesPathList[$this->module].'controllers/'.$this->controller.'.'.$this->request.'.php';
        }
    }

    protected function _createCachePath(){
        $this->_cachePath = '';
    }

    public function toString($full=false){
        if($full)
            return $this->type.':'.$this->module.'~'.$this->resource.'@'.$this->request;
        else
            return $this->module.'~'.$this->resource.'@'.$this->request;
    }

    public function getClass(){
        return $this->controller.'Ctrl';
    }

}
