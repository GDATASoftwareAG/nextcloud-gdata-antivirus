<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace OCA\GDataVaas\AppInfo;

use OCA\GDataVaas\Db\DbFileMapper;
use OCA\GDataVaas\EventListener\FileEventsListener;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\SystemTag\SystemTagObjectMapperWithoutActivityFactory;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\Util;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'gdatavaas';

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct() {
		parent::__construct(self::APP_ID);

		$container = $this->getContainer();
		$eventDispatcher = $container->get(IEventDispatcher::class);
		assert($eventDispatcher instanceof IEventDispatcher);
		$eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			Util::addScript(self::APP_ID, 'gdatavaas-files-action');
		});
	}

	/**
	 * Load the composer autoloader if it exists
	 * @param IRegistrationContext $context
	 * @return void
	 */
	#[\Override]
	public function register(IRegistrationContext $context): void {
		require_once file_exists(__DIR__ . '/../../vendor/scoper-autoload.php')
			? __DIR__ . '/../../vendor/scoper-autoload.php'
			: __DIR__ . '/../../vendor/autoload.php';

		// Manually register TagService so that we can customize the DI used for $silentTagMapper
		$context->registerService(TagService::class, function ($c) {
			$logger = $c->get(LoggerInterface::class);
			$systemTagManager = $c->get(ISystemTagManager::class);
			$standardTagMapper = $c->get(ISystemTagObjectMapper::class);
			$dbConnection = $c->get(IDBConnection::class);
			$silentTagMapper
				= SystemTagObjectMapperWithoutActivityFactory::createSilentSystemTagObjectMapper(
					$dbConnection,
					$systemTagManager
				);
			$dbFileMapper = $c->get(DbFileMapper::class);

			return new TagService($logger, $systemTagManager, $standardTagMapper, $silentTagMapper, $dbFileMapper);
		});

		FileEventsListener::register($context);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
