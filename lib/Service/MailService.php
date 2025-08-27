<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Service;

use Coduo\PHPHumanizer\NumberHumanizer;
use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCP\IAppConfig;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;
use VaasSdk\VaasVerdict;

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
	 * @param VaasVerdict $verdict
	 * @param string $path
	 * @param string $owner
	 * @param int $size
	 * @return void
	 * @throws Exception
	 */
	public function notifyMaliciousUpload(VaasVerdict $verdict, string $path, string $owner, int $size): void {
		$htmlMessage
			= "<p>User <strong>$owner</strong> tried to upload an infected file to <strong>$path</strong>:</p><br>";
		$htmlMessage .= '<p>Verdict: <strong>' . $verdict->verdict->value . '</strong></p>';
		$htmlMessage .= '<p>Detection: <strong>' . $verdict->detection . '</strong></p>';
		$htmlMessage .= '<p>Filetype: <strong>' . $verdict->fileType . '</strong></p>';
		$htmlMessage .= '<p>Mimetype: <strong>' . $verdict->mimeType . '</strong></p>';
		$htmlMessage .= '<p>SHA256: <strong>' . $verdict->sha256 . '</strong></p>';
		$htmlMessage .= '<p>Size: <strong>' . NumberHumanizer::binarySuffix($size, 'de') . '</strong></p>';

		$plainMessage = "User $owner tried to upload an infected file to $path:\n";
		$plainMessage .= 'Verdict: ' . $verdict->verdict->value . "\n";
		$plainMessage .= 'Detection: ' . $verdict->detection . "\n";
		$plainMessage .= 'Filetype: ' . $verdict->fileType . "\n";
		$plainMessage .= 'Mimetype: ' . $verdict->mimeType . "\n";
		$plainMessage .= 'SHA256: ' . $verdict->sha256 . "\n";
		$plainMessage .= 'Size: ' . NumberHumanizer::binarySuffix($size, 'de') . "\n";

		$msg = $this->mailer->createMessage();
		$msg->setSubject('Infected file uploaded');
		$msg->setHtmlBody($htmlMessage);
		$msg->setPlainBody($plainMessage);
		$receiver = $this->getNotifyMails();
		$msg->setTo($receiver);

		$this->mailer->send($msg);
		$this->logger->debug('Mail sent to ' . implode(', ', $receiver));
	}

	/**
	 * @return array
	 */
	private function getNotifyMails(): array {
		$notifyMails = $this->config->getValueString(Application::APP_ID, 'notifyMails');
		$notifyMails = preg_replace('/\s+/', '', $notifyMails);
		if (empty($notifyMails)) {
			return [];
		}
		return explode(',', $notifyMails);
	}
}
