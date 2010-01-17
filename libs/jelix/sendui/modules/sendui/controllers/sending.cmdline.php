<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 ff=unix fenc=utf8: */

/**
 * Envoyer le message en ligne de commande (send)
 *
 * @package      sendui
 * @subpackage   sendui
 * @author       Yves Tannier [grafactory.net]
 * @copyright    2009 Yves Tannier
 * @link         http://www.grafactory.net/sendui
 * @license      http://www.grafactory.net/sendui/licence GPL Gnu Public Licence
 * @version      0.1.0
 */

class sendingCtrl extends jControllerCmdLine {

    // message
    protected $dao_message = 'common~message';

    // log
    protected $log_process = true;
    protected $log_file = 'process';

    // silencieux ?
    protected $verbose = true;

    // retour de ligne
    protected $n = "\n";

    // abonnés
    protected $dao_subscriber = 'common~subscriber';
    protected $dao_subscriber_subscriber_list = 'common~subscriber_subscriber_list';

    // le pid
    protected $pid = 0;

    // le message
    protected $idmessage = 0;

    // log
    protected $dao_process = 'common~process';

    public $pluginParams = array(
      '*'=>array('auth.required'=>false)
    );

    /**
    * Options to the command line
    *  'method_name' => array('-option_name' => true/false)
    * true means that a value should be provided for the option on the command line
    */
    protected $allowed_options = array(
        'index' => array(
            '--idmessage' => true,
            '-i' => true,
            '--reset' => false,
            '-r' => false,
            '--forcereset' => false,
            '-f' => false,
            '--pid' => true,
            '-p' => true,
        ),
    );

    public $help = array(
        'index' => '
            -i --idmessage : identifiant du message
            -r --reset : remettre à zéro le flag d\'envoi (champ sent)
            -f --forcereset : force une remise à zero avant un envoi (champ sent)
            -p --pid : le pid du processus
            help : cette aide
        '
    );

    /**
     * Parameters for the command line
     * 'method_name' => array('parameter_name' => true/false)
     * false means that the parameter is optionnal. All parameters which follow an optional parameter
     * is optional
     */
    protected $allowed_parameters = array();

    // {{{ index()

    /**
     * Envoyer
     *
     * @template    send_index
     * @return      html
     */
    public function index() 
    {


        $rep = $this->getResponse(); 

        $idmessage = $this->option('--idmessage');
        if(empty($idmessage)) {
            $idmessage = $this->option('-i');
        }

        // il faut l'identifiant du message
        if(empty($idmessage)) {
            $rep->addContent('Vous devez préciser l\'identifiant du message'.$this->n);
            return $rep;
        } else {
            $this->idmessage = $idmessage;    
        }

        // identifiant du pid
        $pid = $this->option('--pid');
        if(empty($pid)) {
            $pid = getmypid();
        }
        $pid = str_replace('\n','',$pid);
        $this->pid = $pid;

        // mise à zero
        $reset = $this->option('--reset');
        if(empty($reset)) {
            $reset = $this->option('-r');
        }

        // force la remise à zero avant l'envoi
        $forcereset = $this->option('--forcereset');
        if(empty($forcereset)) {
            $reset = $this->option('-f');
        }

        // le message
        $message = jDao::get($this->dao_message);
        $message_infos = $message->get($idmessage);

        // la table de process
        $process = jDao::get($this->dao_process);

        // utilitaires
        $utils = jClasses::getService('sendui~utils');

        // les destinataires trouvé via message_subscriber_list
        $subscriber = jDao::get($this->dao_subscriber);
        $subscribers_list = $subscriber->getSubscribers($idmessage,1); 

        // compter le nb d'abonné
        $nb_subscribers = 0;
        foreach($subscribers_list as $s) {
            $nb_subscribers++;
        }

        // marqué envoyé ou autres actions
        $subscriber_subscriber_list = jDao::get($this->dao_subscriber_subscriber_list);

        // message inconnu
        if(empty($message_infos->idmessage)) {
            $rep->addContent('L\'identifiant ne correspond pas a un message valide'.$this->n);
            return $rep;
        }

        // mise à zéro du champ d'envoi, vide process et change status
        if(!empty($reset) || !empty($forcereset)) {

            $this->setLog('Remise a zero du champ "sent" et des logs/process/compteur');

            $subscriber_subscriber_list->resetSent($idmessage);
            $message->setStatus($idmessage,1);
            $message->resetCount($idmessage);
            $process->deleteLogs($idmessage);

            // ne pas continuer sauf si forcé
            if(empty($forcereset)) {
                $this->setLog('OK');
                exit;
            }

        }

        // on instancie swiftmailer
        require_once JELIX_APP_PATH.'/lib/swiftmailer/lib/swift_required.php';

        // smtp
        $transport = Swift_SmtpTransport::newInstance($GLOBALS['gJConfig']->mailerInfo['sSmtpHost'], 587)
            ->setUsername($GLOBALS['gJConfig']->mailerInfo['sSmtpUsername'])
            ->setPassword($GLOBALS['gJConfig']->mailerInfo['sSmtpPassword'])
        ;

        // objet
        $mailer = Swift_Mailer::newInstance($transport);
    
        // on précise le serveur de mail
        /*if(!defined(SERVER_MAIL)) {
            $mailer->setDomain(SERVER_MAIL);
        }*/

        // composition du message
        $message_compose = Swift_Message::newInstance();
        $message_compose->setReturnPath($message_infos->return_path); // adresse de retour des bounces

        // sujet
        $message_compose->setSubject($message_infos->subject);

        // expediteur
        $message_compose->setFrom(array($message_infos->from_email => $message_infos->from_name));

        // entêtes
        $headers = $message_compose->getHeaders();

        // reply-to
        if($message_infos->reply_to!='') {
            $headers->addTextHeader('Reply-to', $message_infos->reply_to);
        }

        //  entêtes antispam
        $headers->addTextHeader('X-Mailer', 'grafactory.net');
        $headers->addTextHeader('X-Complaints-To', 'abuse@grafactory.net');

        // replacement dans les messages
        jClasses::inc('sendui~template');

        // champs disponibles pour le remplacement
        $replace_array = array('email','lastname','firstname','token');
       
        function replaceArray($row,$replace_array) {
            foreach($replace_array as $k=>$v) {
                $tab[$v] = $row->$v;    
            }
            return $tab;
        }

        // envoyer un message de test seulement
        if(!empty($test)) {

            // remplacement avec valeur du client ?

            // message

            // envoi
            
        }

        // les infos pause et lot sont dans la conf du message aussi (pause, batch)
        if($nb_subscribers==0) {
            $this->setLog('[NOTICE] Il n\'y a aucun abonné pour ce message. Avez-vous réinitialisé le champ "sent" (option -r)');
            exit;
        } else {
            $this->setLog('[START] Envoi du message ['.$idmessage.'] "'.$message_infos->subject.'" à '.$nb_subscribers.' abonnés');    
        }

        // valeur max
        $max = 0;

        // récuperer le compteur
        $max_record = $process->getMaxCounter($idmessage);
        $max = $max_record->max+1;

        // commencer la boucle

        // début 
        $time_start = microtime(true);

        // marquer le début et le statut en cours d'envoi
        $message->setStart($idmessage);
        $message->setStatus(2);

        // init compteurs
        $i = $max;
        $count_success = 0;

        foreach($subscribers_list as $s) {

            $email = $s->email;
            $email = strtolower($email);    
            
            // on verifie la validite syntaxique de l'adresse mail
            if (!$utils->isEmailSyntaxValid($email)) {
                if(empty($silent)) {
                    $this->setLog('Adresse '.$email.' ['.$s->idsubscriber.'] non valide');
                }
                // TODO : marquer l'email invalide dans la base ou dans un fichier de logs
                continue;
            }

            // si force limitation
            if (!empty($limit) && $i>= $limit) {
                break;
            }

            // la pause (deconnection/reconnection)
            if (($i % $message_infos->batch) == 0 && $i>1) {

                // on deconnecte avant la pause
                try {
                    $mailer->getTransport()->stop();
                    $this->setLog('[DISCONNECT] Déconnexion');
                } catch (Exception $e) {
                    if(empty($silent)) {
                        $this->setLog('[WARN] Déconnexion impossible, arrêt de l\'envoi');
                    }
                    exit;
                }
                
                if (empty($silent)) {
                    $this->setLog('[PAUSE] Pause de '.$message_infos->pause.' seconde(s) au niveau '.$i.' (CTRL+c pour couper le script)');
                }

                for($t=0;$t<$message_infos->pause;$t++) {
                    if(empty($silent)) {
                        echo ".";
                    }
                    sleep(1);
                }

                if(empty($silent)) {
                    echo "\n";
                }
                // on reconnecte apres la pause
                try {
                    $mailer->getTransport()->start();
                    if(empty($silent)) {
                        $this->setLog('[CONNECT] Connexion');
                    }
                } catch (Exception $e) {
                    if(empty($silent)) {
                        $this->setLog('[WARN] Connexion impossible, arrêt de l\'envoi');
                    }
                    exit;
                }

            }

            // on envoie effectivement le message (sauf si option nosend)
            $success = false;

            if (empty($nosend)) {

                // remplacement
                $r = new Template(replaceArray($s,$replace_array));

                $headers->addTextHeader('List-Unsubscribe', 'http://www.grafactory.net/'.$s->token);

                // contenu HTML ou simple TEXT
                if($message_infos->html_message!='') {
                    $message_compose->setBody($r->parse($message_infos->html_message), 'text/html');
                } else {
                    $message_compose->setBody($r->parse($message_infos->text_message), 'text/plain');
                }

                // contenu TEXT en plus du HTML
                if($message_infos->html_message!='' && $message_infos->text_message!='') {
                    $message_compose->addPart($r->parse($message_infos->text_message), 'text/plain');
                }

                // destinataire
                $message_compose->setTo($email);

                //$success = $mailer->send($message);
                $success = true;

                // on comptabilise les succes
                if ($success) $count_success++;

            }

            // on met a jour le flag "envoye" dans subscriber_subscriber_list et le count message
            if (empty($noupdate) && ($success || $nosend)) {
                $subscriber_subscriber_list->updateSent($s->idsubscriber,$s->idsubscriber_list);
                $message->updateCount($idmessage);
            }

            if (empty($silent)) {
                $this->setLog('[SEND] Envoi en cours à '.$email.' ['.$s->idsubscriber.']');
            }

            // on enregistre le process
            if ($success || $nosend) {
                $log = '[sendTo] '.$email.' [ID'.$s->idsubscriber.']';
                $record_process = jDao::createRecord($this->dao_process);
                $record_process->log = $log;
                $record_process->pid = $pid;
                $record_process->idmessage = $idmessage;
                $record_process->counter = $i;
                $process->insert($record_process);
            }

            $i++;

        }

         // deconnexion
        $mailer->getTransport()->stop();

        // fin
        $time_end = microtime(true);

        // temps d'execution
        $time_exec = $time_end - $time_start;

        // marquer la fin et le status à 5
        $message->setEnd($idmessage);
        $message->setStatus($idmessage,5);
        
        // noter le nb de destinataire à partir de subscriber_list
        $nb_recipients = $subscriber_subscriber_list->countSent($idmessage);
        
        // fin de l'envoi
        $this->setLog('[END] Envoi terminé en '.$utils->getTimeExec($time_start,$time_end).' ('.$count_success.'/'.$i.') !');

        return $rep;

    }

    protected function setLog($msg)
    {

        $msg = '['.$this->pid.']['.$this->idmessage.'] '.$msg;

        // affiche
        if($this->verbose) {
            echo $msg.$this->n;
        }

        // logue dans un fichier
        if($this->log_process) {
            jLog::log($msg, $this->log_file);
        }    

    }

}

?>