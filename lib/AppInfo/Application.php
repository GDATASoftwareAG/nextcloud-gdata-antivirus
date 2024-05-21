<?php

declare(strict_types=1);

namespace OCA\GDataVaas\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Util;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Application extends App
{
    public const APP_ID = 'gdatavaas';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        parent::__construct(self::APP_ID);

        $container = $this->getContainer();
        $eventDispatcher = $container->get(IEventDispatcher::class);
        $eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
            Util::addScript(self::APP_ID, 'gdatavaas-files-action');
        });

        // TODO
        Util::connectHook('OC_Filesystem', 'preSetup', $this, 'setupWrapper');

        $this->register();
    }

    /**
     * Load the composer autoloader if it exists
     * @return void
     */
    public function register(): void
    {
        $composerAutoloadFile = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($composerAutoloadFile)) {
            require_once $composerAutoloadFile;
        }
    }

    /**
     * 	 * Add wrapper for local storages
     */
    public function setupWrapper(): void {
        Filesystem::addStorageWrapper(
            'oc_avir',
            function (string $mountPoint, IStorage $storage) {
                if ($storage->instanceOfStorage(Jail::class)) {
                    // No reason to wrap jails again
                    return $storage;
                }

                $container = $this->getContainer();
                $scannerFactory = $container->query(ScannerFactory::class);
                $l10n = $container->get(IL10N::class);
                $logger = $container->get(LoggerInterface::class);
                $activityManager = $container->get(IManager::class);
                $eventDispatcher = $container->get(IEventDispatcher::class);
                $appManager = $container->get(IAppManager::class);
                return new AvirWrapper([
                    'storage' => $storage,
                    'scannerFactory' => $scannerFactory,
                    'l10n' => $l10n,
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
}
