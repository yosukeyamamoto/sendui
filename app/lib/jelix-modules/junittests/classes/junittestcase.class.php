<?php
/**
* @package     jelix
* @subpackage  junittests
* @author      Jouanneau Laurent
* @contributor
* @copyright   2006-2007 Jouanneau laurent
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class jUnitTestCase extends UnitTestCase {

    // for database management

    protected $dbProfile ='';
    protected $needPDO = false;

    function run(&$reporter) {
        SimpleTest::setCurrent($this);
        $this->_reporter = &$reporter;
        $this->_reporter->paintCaseStart($this->getLabel());
        if($this->needPDO){
            $this->_reporter->makeDry(!$this->assertTrue(class_exists('PDO',false), 'PDO does not exists ! You should install PDO because tests need it.'));
        }
        foreach ($this->getTests() as $method) {
            if ($this->_reporter->shouldInvoke($this->getLabel(), $method)) {
                $invoker = &$this->_reporter->createInvoker($this->createInvoker());
                $invoker->before($method);
                $invoker->invoke($method);
                $invoker->after($method);
            }
        }
        $this->_reporter->paintCaseEnd($this->getLabel());
        unset($this->_reporter);
        return $reporter->getStatus();
    }


    /**
    *    show difference between two strings
    *    @param string $stringA  the first string
    *    @param string $stringB  the second string
    *    @param string $message        Message to send.
    */
    function diff($stringA, $stringB, $message='') {
        if (! isset($this->_reporter)) {
            trigger_error('Can only show diff within test methods');
        }
        if($message != '')
            $this->_reporter->paintMessage($message);
        $this->_reporter->paintDiff($stringA, $stringB);
    }

    /**
    *    like assertEqual, but shows difference between two given strings
    *    if it the test fail.
    *    @param mixed $first  the first value
    *    @param mixed $second  the second value
    *    @param string $message        Message to send if it fail
    *    @return boolean true if the test pass
    */
    function assertEqualOrDiff($first, $second, $message = "%s"){
        $ret = $this->assertEqual($first, $second, $message);
        if(!$ret){
            if(is_string($first) && is_string($second))
                $this->diff($first, $second);
            else
                $this->diff(var_export($first,true), var_export($second,true));
        }
        return $ret;
    }

    //    complex equality

    function assertComplexIdentical($value, $file, $errormessage=''){
        $xml = simplexml_load_file($file);
        if(!$xml){
            trigger_error('Impossible de charger le fichier '.$file,E_USER_ERROR);
            return false;
        }
        return $this->_checkIdentical($xml, $value, '$value', $errormessage);
    }

    function assertComplexIdenticalStr($value, $string, $errormessage=''){
        $xml = simplexml_load_string($string);
        if(!$xml){
            trigger_error('mauvais contenu xml '.$string,E_USER_ERROR);
            return false;
        }
        if($errormessage != '')
            $errormessage = ' ('.$errormessage.')';
        return $this->_checkIdentical($xml, $value, '$value', $errormessage);
    }

/*

<object class="jDaoMethod">
    <string property="name" value="" />
    <string property="type" value="" />
    <string property="distinct" value="" />

    <object method="getConditions()" class="jDaoConditions">
        <array property="order">array()</array>
        <array property="fields">array()</array>
        <object property="condition" class="jDaoCondition">
            <null property="parent"/>
            <array property="conditions"> array(...)</array>
            <array property="group">
                <object key="" class="jDaoConditions" test="#foo" />
             </array>
        </object>

    </object>
</object>


<ressource />
<string value="" />
<integer value="" />
<float value=""/>
<null />
<boolean value="" />
<array>
<object class="">
</object>*/

    function _checkIdentical($xml, $value, $name, $errormessage){
        $nodename  = dom_import_simplexml($xml)->nodeName;
        switch($nodename){
            case 'object':
                if(isset($xml['class'])){
                    $ok = $this->assertIsA($value,(string)$xml['class'], $name.': not a '.(string)$xml['class'].' object'.$errormessage);
                }else
                    $ok = $this->assertTrue(is_object($value),  $name.': not an object'.$errormessage);
                if(!$ok) return false;

                foreach ($xml->children() as $child) {
                    if(isset($child['property'])){
                        $n = (string)$child['property'];
                        $v = $value->$n;
                    }elseif(isset($child['p'])){
                        $n = (string)$child['p'];
                        $v = $value->$n;
                    }elseif(isset($child['method'])){
                        $n = (string)$child['method'];
                        eval('$v=$value->'.$n.';');
                    }elseif(isset($child['m'])){
                        $n = (string)$child['m'];
                        eval('$v=$value->'.$n.';');
                    }else{
                        trigger_error('no method or attribute on '.(dom_import_simplexml($child)->nodeName), E_USER_WARNING);
                        continue;
                    }
                    $ok &= $this->_checkIdentical($child, $v, $name.'->'.$n,$errormessage);
                }

                if(!$ok)
                    $this->fail($name.' : non identical objects'.$errormessage);
                return $ok;

            case 'array':
                $ok = $this->assertIsA($value,'array', $name.': not an array'.$errormessage);
                if(!$ok) return false;

                if(trim((string)$xml) != ''){
                    if( false === eval('$v='.(string)$xml.';')){
                        $this->fail("invalid php array syntax");
                        return false;
                    }
                    return $this->assertEqual($value,$v,'negative test on '.$name.': %s'.$errormessage);
                }else{
                    $key=0;
                    foreach ($xml->children() as $child) {
                        if(isset($child['key'])){
                            $n = (string)$child['key'];
                            if(is_numeric($n))
                                $key = intval($n);
                        }else{
                            $n = $key ++;
                        }
/*$this->dump($n, 'n=');
$this->dump($value, 'value');
if(isset($value[$n]))
$this->dump($value[$n],'value de n OK');
else
$this->dump('!!!!! value de n  pas ok');*/
                        if($this->assertTrue(array_key_exists($n,$value),$name.'['.$n.'] doesn\'t exist arrrg'.$errormessage)){
                            $v = $value[$n];
                            $ok &= $this->_checkIdentical($child, $v, $name.'['.$n.']',$errormessage);
                        }else $ok= false;
                    }
                    return $ok;
                }
                break;

            case 'string':
                $ok = $this->assertIsA($value,'string', $name.': not a string'.$errormessage);
                if(!$ok) return false;
                if(isset($xml['value']))
                    return $this->assertEqual($value, (string)$xml['value'],$name.': bad value. %s'.$errormessage);
                else
                    return true;
            case 'int':
            case 'integer':
                $ok = $this->assertIsA($value,'integer', $name.': not an integer'.$errormessage);
                if(!$ok) return false;
                if(isset($xml['value'])){
                    return $this->assertEqual($value, intval((string)$xml['value']),$name.': bad value. %s'.$errormessage);
                }else
                    return true;
            case 'float':
            case 'double':
                $ok = $this->assertIsA($value,'float', $name.': not a float'.$errormessage);
                if(!$ok) return false;
                if(isset($xml['value'])){
                    return $this->assertEqual($value, floatval((string)$xml['value']),$name.': bad value. %s'.$errormessage);
                }else
                    return true;
            case 'boolean':
                $ok = $this->assertIsA($value,'boolean', $name.': not a boolean'.$errormessage);
                if(!$ok) return false;
                if(isset($xml['value'])){
                    $v = ((string)$xml['value'] == 'true');
                    return $this->assertEqual($value, $v ,$name.': bad value. %s'.$errormessage);
                }else
                    return true;
            case 'null':
                return $this->assertNull($value, $name.': not null'.$errormessage);
            case 'notnull':
                return $this->assertNotNull($value, $name.' is null'.$errormessage);
            case 'resource':
                return $this->assertIsA($value,'resource', $name.': not a resource'.$errormessage);
            default:
                $this->fail("_checkIdentical: balise inconnue ".$nodename.$errormessage);
        }

    }

}



?>