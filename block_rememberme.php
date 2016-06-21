<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * RememberMe is a plugin for Moodle v2.0 that automatically sends a reminder
 * email to members of a course who have not logged in recently.  
 *
 * RememberMe is a block type plugin for Moodle v2.0 made by students of the
 * Berlin School of Economics and Law. It periodically checks if a user has not
 * logged in for a specified amount of time and if so, it sends a reminder
 * email to this user. Both the time without log-in required to trigger the
 * reminder, as well as the content of the email can be customised.
 *
 * @package    moodle-block_RememberMe
 * @category   block-plugin
 * @copyright  2016 ProPlug
 * @license
 */


//load PHP mailer classes
require_once 'PHPMailerAutoload.php';

class emails {

    /**
     * sendEmails
     *
     * function sends mails to moodle users
     * on success: update timestamp (lastaccess) of user with current timestamp
     *
     * @return array with TRUE or FALSE and error or success message
     */
    public function sendEmails() {
        //import globale database- and config object
        global $DB, $CFG;
        $xml = simplexml_load_file($CFG->dirroot . "/blocks/rememberme/settings.xml");
        $days = (int) $xml->login;
        if ($days > 1) {
            $dayString = $days . " Tagen";
        } else {
            $dayString = $days . " Tag";
        }
        $seconds = $days * 24 * 60 * 60; //2592000 s = 30 d 
        $date = time();
        $text = (string) $xml->text1;
        //read affected userobjects
        $sqlSelectUsers = 'SELECT * FROM {user} WHERE lastaccess < :lastaccess AND id != :guestuser AND id != :adminuser';
        $userObjects = $DB->get_records_sql($sqlSelectUsers, array('lastaccess' => $date - $seconds, 'guestuser' => 1, 'adminuser' => 2));

        $log = date("d.m.Y H.i:s") . "\n";

        if ($userObjects != NULL) {
            //trim hostname / port (smtphostname:port)
            $smtpInfos = explode(":", $CFG->smtphosts);

            //create mailobject
            $mail = new PHPMailer;
            //Set mailer to use SMTP
            $mail->isSMTP();
            //Specify main and backup SMTP servers
            $mail->Host = $smtpInfos[0];
            //Enable SMTP authentication
            $mail->SMTPAuth = true;
            //SMTP username
            $mail->Username = $CFG->smtpuser;
            //SMTP password
            $mail->Password = $CFG->smtppass;
            //Enable TLS encryption, `ssl` also accepted
            $mail->SMTPSecure = $CFG->smtpsecure;
            //TCP port to connect to
            $mail->Port = $smtpInfos[1];
            //Email adress sender
            $mail->From = $CFG->noreplyaddress;
            //Sender name
            $mail->FromName = 'Moodle no reply';
            //Reply adresse
            $mail->addReplyTo($CFG->noreplyaddress, 'Moodle no reply');
            //Set email format to HTML
            $mail->isHTML(true);
            //set charset to utf8
            $mail->CharSet = 'utf-8';
            //subject
            $mail->Subject = 'Moodle';

            //Message (HTML)
            $mail->Body = sprintf($text, $dayString);

            //Add email adress of user to mail object
            foreach ($userObjects as $userObject) {
                //clear all recipients
                $mail->clearAllRecipients();
                //add recipient
                $mail->addAddress($userObject->email);
                //send email and add success or error to array
                if (!$mail->send()) {
                    $success = array('success' => FALSE, 'message' => 'error.<br />info: ' . $mail->ErrorInfo . "\n");
                } else {
                    $success = array('success' => TRUE, 'message' => 'success' . "\n");
                }
                $log .= "recipient:" . "\n";
                $log .= $userObject->email . "\n";
                $log .= "note:" . "\n";
                $log .= $success['message'] . "\n\n";
                if ($success['success'] == TRUE) {
                    //update timestamp (lastaccess) of user with current timestamp
                    $sqlUpdateUsers = "UPDATE {user} SET lastaccess = :now WHERE id = :userid";
                    $DB->execute($sqlUpdateUsers, array('now' => time(), 'userid' => $userObject->id));
                }
            }
        } else {            
            $success = array('success' => TRUE, 'message' => 'nothing to do' . "\n");
            $log .= "note:" . "\n";
            $log .= $success['message']."\n\n";
        }
        //write into log
        file_put_contents($CFG->dirroot . "/blocks/rememberme/remember_me_log.txt", $log, FILE_APPEND);
        //return result
        return $success;
    }

}

class block_rememberme extends block_base {

    public function init() {
        //add headline
        $this->title = "Remember Me";
    }

    /**
     * cron
     *
     * Cronjob to perform email function
     *
     * @return bool TRUE or FALSE
     */
    public function cron() {
        //create new email object
        $emails = new emails();
        //perform sendEmails()        
        $success = $emails->sendEmails();
        if ($success['success'] === TRUE) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        global $CFG;
        $this->content = new stdClass;
        $this->content->text = '<a href= "https://docs.moodle.org/31/en/RememberMe" target="_blank"><div style="width:20%; float: left;"><img width="90%" src="' . $CFG->wwwroot . '/blocks/rememberme/RememberMe.jpg" /></div>';
        $this->content->text .= '<div style="width:80%; float: left;">RememberMe plugin is active. The plugin requires no graphical output.</div><div style="clear: both;"></div></a>';
        return $this->content;
    }

}
