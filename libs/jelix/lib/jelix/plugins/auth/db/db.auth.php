<?php
/**
* @package    jelix
* @subpackage auth_driver
* @author     Laurent Jouanneau
* @contributor
* @copyright  2001-2005 CopixTeam, 2005-2006 Laurent Jouanneau
* This classe was get originally from an experimental branch of the Copix project (Copix 2.3dev, http://www.copix.org)
* Few lines of code are still copyrighted 2001-2005 CopixTeam (LGPL licence).
* Initial author of this Copix classe is Laurent Jouanneau, and this classe was adapted for Jelix by him
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/


/**
* authentification driver for authentification information stored in a database
* @package    jelix
* @subpackage auth_driver
*/
class dbAuthDriver implements jIAuthDriver {

    protected $_params;

    function __construct($params){
        $this->_params = $params;
        if(!isset($this->_params['profile'])) {
            if(isset($this->_params['profil']))
                //compatibility with 1.0
                $this->_params['profile'] = $this->_params['profil'];
            else
                $this->_params['profile'] = '';
        }
    }

    public function saveNewUser($user){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $dao->insert($user);
        return true;
    }

    public function removeUser($login){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $dao->deleteByLogin($login);
        return true;
    }

    public function updateUser($user){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        $dao->update($user);
        return true;
    }

    public function getUser($login){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        return $dao->getByLogin($login);
    }

    public function createUserObject($login,$password){
        $user = jDao::createRecord($this->_params['dao'], $this->_params['profile']);
        $user->login = $login;
        $user->password = $this->cryptPassword($password);
        return $user;
    }

    public function getUserList($pattern){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        if($pattern == '%' || $pattern == ''){
            return $dao->findAll();
        }else{
            return $dao->findByLogin($pattern);
        }
    }

    public function changePassword($login, $newpassword){
        $dao = jDao::get($this->_params['dao'], $this->_params['profile']);
        return $dao->updatePassword($login, $this->cryptPassword($newpassword));
    }

    public function verifyPassword($login, $password){
        if (trim($password) == '')
            return false;
        $daouser = jDao::get($this->_params['dao'], $this->_params['profile']);
        $user = $daouser->getByLoginPassword($login, $this->cryptPassword($password));
        return ($user?$user:false);
    }


    protected function cryptPassword($password){
        if(isset($this->_params['password_crypt_function'])){
            $f=$this->_params['password_crypt_function'];
            if( $f != '')
               $password = $f($password);
        }
        return $password;
    }
}
