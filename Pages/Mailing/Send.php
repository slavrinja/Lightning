<?php
/**
 * @file
 * Lightning\Pages\Mailing\Send
 */

namespace Lightning\Pages\Mailing;

use Lightning\Model\Message;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Mailer;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Lightning\View\Page;

/**
 * A page handler for the send message controls and callbacks.
 *
 * @package Lightning\Pages\Mailing
 */
class Send extends Page {
    /**
     * Require admin privileges.
     */
    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    /**
     * The main page with options to send emails or tests.
     */
    public function get() {
        $message_id = Request::get('id', 'int');
        if (!$message_id || !$message = Database::getInstance()->selectRow('message', array('message_id' => $message_id))) {
            Messenger::error('Message not found.');
            return;
        }

        $template = Template::getInstance();
        $template->set('content', 'mailing_send');
        $template->set('message', $message);
        JS::set('message_id', $message['message_id']);
        JS::addSessionToken();
    }

    /**
     * Send all the messages with streaming output for XHR monitoring.
     */
    public function postSendAll() {
        Output::disableBuffering();
        Messenger::setVerbose(true);
        $mailer = new Mailer(true);
        $mailer->sendBulk(Request::get('id', 'int'), false);
        exit;
    }

    /**
     * Get a count of how many emails to be sent with output for XHR monitoring.
     */
    public function postSendCount() {
        Messenger::setVerbose(true);
        $message = new Message(Request::get('id', 'int'));
        echo 'Sending now will go to ' . $message->getUsersCount() . ' users.';
        exit;
    }

    /**
     * Send a test email.
     */
    public function postSendTest() {
        Output::disableBuffering();
        Messenger::setVerbose(true);
        $mailer = new Mailer(true);
        $mailer->sendBulk(Request::get('id', 'int'), true);
        exit;
    }
}
