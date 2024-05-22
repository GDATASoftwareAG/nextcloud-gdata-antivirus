<?php

declare(strict_types=1);

namespace OCA\GDataVaas\AppInfo;

use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
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
}
