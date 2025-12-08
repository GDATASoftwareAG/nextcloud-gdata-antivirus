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

	public function __construct(private \OCP\IL10N $l, IMailer $mailer, IAppConfig $config, LoggerInterface $logger) {
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
		$htmlMessage = sprintf(
			'<p>%s</p><br>',
			$this->l->t(
				'User <strong>{owner}</strong> tried to upload an infected file to <strong>{path}</strong>.',
				[
					'owner' => $owner,
					'path'  => $path,
				]
			)
		);
		$htmlMessage .= sprintf(
			'<p>%s <strong>%s</strong></p>',
			$this->l->t('Verdict:'),
			$verdict->verdict->value
		);
		$htmlMessage .= sprintf(
			'<p>%s <strong>%s</strong></p>',
			$this->l->t('Detection:'),
			$verdict->detection
		);

		$htmlMessage .= sprintf(
			'<p>%s <strong>%s</strong></p>',
			$this->l->t('Filetype:'),
			$verdict->fileType
		);
		$htmlMessage .= sprintf(
			'<p>%s <strong>%s</strong></p>',
			$this->l->t('Mimetype:'),
			$verdict->mimeType
		);
		$htmlMessage .= sprintf(
			'<p>%s <strong>%s</strong></p>',
			$this->l->t('SHA256:'),
			$verdict->sha256
		);
		$htmlMessage .= sprintf(
			'<p>%s <strong>%s</strong></p>',
			$this->l->t('Size:'),
			NumberHumanizer::binarySuffix($size, 'de')
		);

		$plainMessage = sprintf(
			"%s\n",
			$this->l->t(
				"User %1\$s tried to upload an infected file to %2\$s:",
				[
					$owner,
					$path,
				]
			)
		);
		$plainMessage .= sprintf(
			"%s: %s\n",
			$this->l->t('Verdict'),
			$verdict->verdict->value
		);
		$plainMessage .= sprintf(
			"%s: %s\n",
			$this->l->t('Detection'),
			$verdict->detection
		);
		$plainMessage .= sprintf(
			"%s: %s\n",
			$this->l->t('Filetype'),
			$verdict->fileType
		);
		$plainMessage .= sprintf(
			"%s: %s\n",
			$this->l->t('Mimetype'),
			$verdict->mimeType
		);
		$plainMessage .= sprintf(
			"%s: %s\n",
			$this->l->t('SHA256'),
			$verdict->sha256
		);
		$plainMessage .= sprintf(
			"%s: %s\n",
			$this->l->t('Size'),
			NumberHumanizer::binarySuffix($size, 'de')
		);

		$msg = $this->mailer->createMessage();
		$msg->setSubject($this->l->t('Infected file uploaded'));
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
