<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Menu;

use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;
use Ibexa\AdminUi\Menu\MainMenuBuilder;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class MainMenuSubscriber implements EventSubscriberInterface
{
    public const ITEM = 'main__admin__migration_ui';

    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConfigureMenuEvent::MAIN_MENU => ['onMainMenuConfigure', 0]];
    }

    public function onMainMenuConfigure(ConfigureMenuEvent $event): void
    {
        if (!$this->authorizationChecker->isGranted(new Attribute('migration_ui', 'view'))) {
            return;
        }

        $admin = $event->getMenu()->getChild(MainMenuBuilder::ITEM_ADMIN);
        $admin?->addChild(self::ITEM, [
            'label' => 'menu.migrations',
            'route' => 'code_rhapsodie_migration_ui',
            'extras' => ['translation_domain' => 'ibexa_migration_ui', 'orderNumber' => 95],
        ]);
    }
}
