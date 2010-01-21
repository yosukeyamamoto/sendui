<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 ff=unix fenc=utf8: */

/**
 * Entête de la partie privée
 *
 * @package      sendui
 * @subpackage   sendui
 * @author       Yves Tannier [grafactory.net]
 * @copyright    2009 Yves Tannier
 * @link         http://www.grafactory.net/sendui
 * @license      http://www.grafactory.net/sendui/licence GPL Gnu Public Licence
 * @version      0.1.0
 */

require_once (JELIX_LIB_CORE_PATH.'response/jResponseHtml.class.php');

class senduiHtmlResponse extends jResponseHtml {

    // template principal
    public $bodyTpl = 'sendui~main';

    // {{{ __construct

    /**
     * Constructeur feuille de style et js de base
     *
     */
    public function __construct() {

        parent::__construct();

        $base_path = $GLOBALS['gJConfig']->urlengine['basePath'];

        // ajouter une feuille de style 
        foreach(array('reset','style') as $k) {
            $this->addCSSLink($base_path.'/css/'.$k.'.css');
        }

        // jquery-ui
        $this->addCSSLink($base_path.'/css/start/jquery-ui-1.7.2.custom.css');

        // ajouter les javascript jquery
        $this->addJSLink($base_path.'/js/jquery-1.3.2.min.js');
        $this->addJSLink($base_path.'/js/jquery-ui-1.7.2.custom.min.js');
        $this->addJSLink($base_path.'/js/jquery.masonry.min.js');
        $this->addJSLink($base_path.'/js/button.js');
        $this->addJSLink($base_path.'/js/function.js');

    }

    // }}}

    // {{{ doAfterActions()

    /**
     * Que faire à la fin de l'action ? contenu alternatif
     *
     * @template    send_index
     * @return      html
     */
    protected function doAfterActions() {

        // utilisateur connecté
        $session = jAuth::getUserSession();
        $this->body->assign('session', $session);

        // le menu
        $menu_items = array(
            'dashboard' => array(
                'url' => 'sendui~default:index',
                'name' => 'tableau de bord',
            ),
            'subscribers' => array(
                'url' => 'sendui~subscribers:index',
                'name' => 'gérer vos listes d\'abonnés',
            ),
            'newmessage' => array(
                'url' => 'sendui~settings:prepare',
                'name' => 'créer &amp; envoyer un message',
            ),
            'drafts' => array(
                'url' => 'sendui~messages:drafts',
                'name' => 'brouillons',
            ),
            'archives' => array(
                'url' => 'sendui~messages:sent',
                'name' => 'messages en cours &amp; envoyés',
            ),
        );
        foreach($menu_items as $k=>$m) {
            $menu_items[$k]['state'] = 'default';    
        }
        $this->body->assign('menu_items', $menu_items);
         
        // si pas de contenu
        $this->body->assignIfNone('MAIN','<p>Cette page n\'existe pas</p>');

    }

    // }}}

}
