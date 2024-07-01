<?php

namespace OCA\GDataVaas\Service;

use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCP\IAppConfig;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;

class MailService {
    private IMailer $mailer;
    private IAppConfig $config;
    private LoggerInterface $logger;

    public function __construct(IMailer $mailer, IAppConfig $config, LoggerInterface $logger) {
        $this->mailer = $mailer;
        $this->config = $config;
        $this->logger = $logger;
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
            "<!doctype html>$message"
        );
        $receiver = $this->getNotifyMails();
        $msg->setTo($receiver);
        $this->mailer->send($msg);
        $this->logger->debug("Mail sent to " . implode(", ", $receiver));
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