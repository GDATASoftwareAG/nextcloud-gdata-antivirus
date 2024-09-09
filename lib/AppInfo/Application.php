<?php

declare(strict_types=1);

namespace OCA\GDataVaas\AppInfo;

use OC\Files\Filesystem;
use OCA\GDataVaas\AvirWrapper;
use OCA\GDataVaas\CacheEntryListener;
use OCA\GDataVaas\Db\DbFileMapper;
use OCA\GDataVaas\Service\MailService;
use OCA\GDataVaas\Service\TagService;
use OCA\GDataVaas\Service\VerdictService;
use OCA\GDataVaas\SystemTag\SystemTagObjectMapperWithoutActivityFactory;
use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IHomeStorage;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
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
		$eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			Util::addScript(self::APP_ID, 'gdatavaas-files-action');
		});
	}

	/**
	 * Load the composer autoloader if it exists
	 * @return void
	 */
	public function register(IRegistrationContext $context): void {
		$composerAutoloadFile = __DIR__ . '/../../vendor/autoload.php';
		if (file_exists($composerAutoloadFile)) {
			require_once $composerAutoloadFile;
		}
		
		// Manually register TagService so that we can customize the DI used for $silentTagMapper
		$context->registerService(TagService::class, function ($c) {
			$logger = $c->get(LoggerInterface::class);
			$systemTagManager = $c->get(ISystemTagManager::class);
			$standardTagMapper = $c->get(ISystemTagObjectMapper::class);
			$dbConnection = $c->get(IDBConnection::class);
			$silentTagMapper = SystemTagObjectMapperWithoutActivityFactory::createSilentSystemTagObjectMapper($dbConnection, $systemTagManager);
			$dbFileMapper = $c->get(DbFileMapper::class);
			
			return new TagService($logger, $systemTagManager, $standardTagMapper, $silentTagMapper, $dbFileMapper);
		}, true);

		CacheEntryListener::register($context);

		// Util::connection is deprecated, but required ATM by FileSystem::addStorageWrapper
		Util::connectHook('OC_Filesystem', 'preSetup', $this, 'setupWrapper');
	}

	/**
	 * 	 * Add wrapper for local storages
	 */
	public function setupWrapper(): void {
		Filesystem::addStorageWrapper(
			'oc_gdata_vaas',
			function (string $mountPoint, IStorage $storage) {
				/*
				if ($storage->instanceOfStorage(Jail::class)) {
					// No reason to wrap jails again
					return $storage;
				}
				*/

				$container = $this->getContainer();
				$verdictService = $container->get(VerdictService::class);
				$mailService = $container->get(MailService::class);
				$appConfig = $container->get(IConfig::class);
				// $l10n = $container->get(IL10N::class);
				$logger = $container->get(LoggerInterface::class);
				$activityManager = $container->get(IManager::class);
				$eventDispatcher = $container->get(IEventDispatcher::class);
				$appManager = $container->get(IAppManager::class);
				return new AvirWrapper([
					'storage' => $storage,
					'verdictService' => $verdictService,
					'mailService' => $mailService,
					'appConfig' => $appConfig,
					//'l10n' => $l10n,
					'logger' => $logger,
					'activityManager' => $activityManager,
					'isHomeStorage' => $storage->instanceOfStorage(IHomeStorage::class),
					'eventDispatcher' => $eventDispatcher,
					'trashEnabled' => $appManager->isEnabledForUser('files_trashbin'),
				]);
			},
			1
		);
	}

	public function boot(IBootContext $context): void {
	}
}
