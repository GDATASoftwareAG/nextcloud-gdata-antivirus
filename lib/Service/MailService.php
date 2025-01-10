<?php

namespace OCA\GDataVaas\Service;

use Coduo\PHPHumanizer\NumberHumanizer;
use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
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
		$htmlMessage = "<p>User <strong>$owner</strong> tried to upload an infected file to <strong>$path</strong>:</p><br>";
		$htmlMessage .= "<p>Verdict: <strong>" . $verdict->verdict->value . "</strong></p>";
		$htmlMessage .= "<p>Detection: <strong>" . $verdict->detection . "</strong></p>";
		$htmlMessage .= "<p>Filetype: <strong>" . $verdict->fileType . "</strong></p>";
		$htmlMessage .= "<p>Mimetype: <strong>" . $verdict->mimeType . "</strong></p>";
		$htmlMessage .= "<p>SHA256: <strong>" . $verdict->sha256 . "</strong></p>";
		$htmlMessage .= "<p>Size: <strong>" . NumberHumanizer::binarySuffix($size, 'de') . "</strong></p>";
		
		$plainMessage = "User $owner tried to upload an infected file to $path:\n";
		$plainMessage .= "Verdict: " . $verdict->verdict->value . "\n";
		$plainMessage .= "Detection: " . $verdict->detection . "\n";
		$plainMessage .= "Filetype: " . $verdict->fileType . "\n";
		$plainMessage .= "Mimetype: " . $verdict->mimeType . "\n";
		$plainMessage .= "SHA256: " . $verdict->sha256 . "\n";
		$plainMessage .= "Size: " . NumberHumanizer::binarySuffix($size, 'de') . "\n";

		$msg = $this->mailer->createMessage();
		$msg->setSubject("Infected file uploaded");
		$msg->setHtmlBody($htmlMessage);
		$msg->setPlainBody($plainMessage);
		$receiver = $this->getNotifyMails();
		$msg->setTo($receiver);
		
		$this->mailer->send($msg);
		$this->logger->debug("Mail sent to " . implode(", ", $receiver));
	}

	/**
	 * @param array $maliciousFiles
	 * @return void
	 * @throws Exception
	 */
	public function notifyWeeklySummary(array $maliciousFiles): void {
		$msg = $this->mailer->createMessage();
		$msg->setSubject("Summary: Malicious files in your Nextcloud instance");
		$msg->setHtmlBody($this->createSummaryHtml($maliciousFiles));
		$msg->setPlainBody($this->createSummaryPlain($maliciousFiles));
		$receiver = $this->getNotifyMails();
		$msg->setTo($receiver);
		
		$this->mailer->send($msg);
		$this->logger->debug("Mail sent to " . implode(", ", $receiver));
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
		return explode(",", $notifyMails);
	}

	/**
	 * @param array $maliciousFiles
	 * @return string
	 */
	private function createSummaryHtml(array $maliciousFiles): string {
		$htmlMessage = "<p>This is your weekly summary of the malicious files found in your Nextcloud instance:</p>";
		$htmlMessage .= "<p>Found " . count($maliciousFiles) . " malicious files:</p>";
		$htmlMessage .= "<table>";
		$htmlMessage .= "<tr>";
		$htmlMessage .= "<td> <strong>Name</strong> </td>";
		$htmlMessage .= "<td> <strong>Path</strong> </td>";
		$htmlMessage .= "<td> <strong>Owner</strong> </td>";
		$htmlMessage .= "<td> <strong>Upload time</strong> </td>";
		$htmlMessage .= "<td> <strong>Mimetype</strong> </td>";
		$htmlMessage .= "<td> <strong>Size</strong> </td>";
		$htmlMessage .= "</tr>";
		foreach ($maliciousFiles as $file) {
			if ($file instanceof File) {
				try {
					$size = $file->getSize();
				} catch (InvalidPathException|NotFoundException) {
					$size = 0;
				}
				$uploadTime = $file->getCreationTime() ?: $file->getUploadTime();
				if ($uploadTime === 0) {
					$uploadTime = "Unknown";
				}
				$htmlMessage .= "<tr>";
				$htmlMessage .= "<td>" . $file->getName() . "</td>";
				$htmlMessage .= "<td>" . $file->getInternalPath() . "</td>";
				$htmlMessage .= "<td>" . $file->getOwner()->getDisplayName() . "</td>";
				$htmlMessage .= "<td>" . $uploadTime . "</td>";
				$htmlMessage .= "<td>" . $file->getMimeType() . "</td>";
				if ($size !== 0) {
					$htmlMessage .= "<td>" . NumberHumanizer::binarySuffix($size, 'de') . "</td>";
				} else {
					$htmlMessage .= "<td>Unknown</td>";
				}
				$htmlMessage .= "</tr>";
			}
		}
		$htmlMessage .= "</table>";
		return $htmlMessage;
	}

	/**
	 * @param array $maliciousFiles
	 * @return string
	 */
	private function createSummaryPlain(array $maliciousFiles): string {
		$plainMessage = "This is your weekly summary of the malicious files found in your Nextcloud instance:\n";
		$plainMessage .= "Found " . count($maliciousFiles) . " malicious files:\n";
		foreach ($maliciousFiles as $file) {
			if ($file instanceof File) {
				$plainMessage .= "\n";
				try {
					$size = $file->getSize();
				} catch (InvalidPathException|NotFoundException) {
					$size = 0;
				}
				$uploadTime = $file->getCreationTime() ?: $file->getUploadTime();
				if ($uploadTime === 0) {
					$uploadTime = "Unknown";
				}
				$plainMessage .= "Name: " . $file->getName() . "\n";
				$plainMessage .= "Path: " . $file->getPath() . "\n";
				$plainMessage .= "Owner: " . $file->getOwner()->getDisplayName() . "\n";
				$plainMessage .= "Time: " . $uploadTime . "\n";
				$plainMessage .= "Mimetype: " . $file->getMimeType() . "\n";
				if ($size !== 0) {
					$plainMessage .= "Size: " . NumberHumanizer::binarySuffix($size, 'de') . "\n";
				} else {
					$plainMessage .= "Size: Unknown\n";
				}
			}
		}
		return $plainMessage;
	}
}
