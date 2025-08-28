<?php

// Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Activity;

use InvalidArgumentException;
use OCA\GDataVaas\AppInfo\Application;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;

class Provider implements IProvider {
	public const TYPE_VIRUS_DETECTED = 'virus_detected';

	public const SUBJECT_VIRUS_DETECTED = 'virus_detected';
	public const SUBJECT_VIRUS_DETECTED_UPLOAD = 'virus_detected_upload';
	public const SUBJECT_VIRUS_DETECTED_SCAN = 'virus_detected_scan';

	public const MESSAGE_FILE_DELETED = 'file_deleted';

	/** @var IURLGenerator */
	private IURLGenerator $urlGenerator;

	public function __construct(IURLGenerator $urlGenerator) {
		$this->urlGenerator = $urlGenerator;
	}

	#[\Override]
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent {
		if ($event->getApp() !== Application::APP_ID || $event->getType() !== self::TYPE_VIRUS_DETECTED) {
			throw new InvalidArgumentException();
		}

		$parameters = [];
		$subject = '';

		if ($event->getSubject() === self::SUBJECT_VIRUS_DETECTED) {
			$subject = 'File {file} is infected with {virus}';

			$params = $event->getSubjectParameters();
			$parameters['virus'] = [
				'type' => 'highlight',
				'id' => $params[1],
				'name' => $params[1],
			];

			$parameters['file'] = [
				'type' => 'highlight',
				'id' => $event->getObjectName(),
				'name' => basename($event->getObjectName()),
			];
			$event->setIcon($this->urlGenerator->imagePath('gdatavaas', 'favicon.svg'));

			if ($event->getMessage() === self::MESSAGE_FILE_DELETED) {
				$event->setParsedMessage('The file has been removed');
			}
		} elseif ($event->getSubject() === self::SUBJECT_VIRUS_DETECTED_UPLOAD) {
			$subject = 'File containing {virus} detected';

			$params = $event->getSubjectParameters();
			$parameters['virus'] = [
				'type' => 'highlight',
				'id' => $params[0],
				'name' => $params[0],
			];

			$event->setParsedSubject($subject);
			$event->setRichSubject($subject);
			$event->setIcon($this->urlGenerator->imagePath('gdatavaas', 'favicon.svg'));

			if ($event->getMessage() === self::MESSAGE_FILE_DELETED) {
				$event->setParsedMessage('The file has been removed');
			}
		} elseif ($event->getSubject() === self::SUBJECT_VIRUS_DETECTED_SCAN) {
			$subject = 'File {file} is infected with {virus}';

			$params = $event->getSubjectParameters();
			$parameters['virus'] = [
				'type' => 'highlight',
				'id' => $params[0],
				'name' => $params[0],
			];
			$parameters['file'] = [
				'type' => 'highlight',
				'id' => $event->getObjectName(),
				'name' => $event->getObjectName(),
			];
			$event->setIcon($this->urlGenerator->imagePath('gdatavaas', 'favicon.svg'));

			if ($event->getMessage() === self::MESSAGE_FILE_DELETED) {
				$event->setParsedMessage('The file has been removed');
			}
		}

		$this->setSubjects($event, $subject, $parameters);

		return $event;
	}

	private function setSubjects(IEvent $event, string $subject, array $parameters): void {
		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			if ($parameter['type'] === 'file') {
				$replacements[] = $parameter['path'];
			} else {
				$replacements[] = $parameter['name'];
			}
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}
}
