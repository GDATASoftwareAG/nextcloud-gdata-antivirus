<?php

namespace OCA\GDataVaas\Service;

use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCP\IAppConfig;
use OCP\Mail\IMailer;

class MailService {
    private IMailer $mailer;
    private IAppConfig $config;

    public function __construct(IMailer $mailer, IAppConfig $config) {
        $this->mailer = $mailer;
        $this->config = $config;
    }

    /**
     * @param string $subject
     * @param string $message
     * @return void
     * @throws Exception
     */
    public function notify(string $subject, string $message): void {
        $msg = $this->mailer->createMessage();
        $msg->setSubject($subject);
        $msg->setHtmlBody(
            "<!doctype html><html><body>$message</body></html>"
        );
        $msg->setTo($this->getNotifyMails());
        $this->mailer->send($msg);
    }
    
    private function getNotifyMails(): array {
        $notifyMails = $this->config->getValueString(Application::APP_ID, 'notifyMails');
        $notifyMails = preg_replace('/\s+/', '', $notifyMails);
        if (empty($notifyMails)) {
            return [];
        }
        return explode(",", $notifyMails);
    }
}