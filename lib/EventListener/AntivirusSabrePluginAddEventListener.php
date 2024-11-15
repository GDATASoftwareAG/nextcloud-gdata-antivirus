<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

 namespace OCA\GDataVaas\EventListener;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\GDataVaas\Dav\AntivirusPlugin;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;

/** @template-implements IEventListener<SabrePluginAddEvent> */
class AntivirusSabrePluginAddEventListener implements IEventListener {
	public function __construct(
		private ContainerInterface $container,
	) {
	}

	public static function register(IRegistrationContext $context): void {
		$context->registerEventListener(SabrePluginAddEvent::class, self::class);
	}

	public function handle(Event $event): void {
		if (!($event instanceof SabrePluginAddEvent)) {
			return;
		}

		$server = $event->getServer();
		$plugin = $this->container->get(AntivirusPlugin::class);
		$server->addPlugin($plugin);
	}
}