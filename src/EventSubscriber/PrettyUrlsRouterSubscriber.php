<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\EventSubscriber;

use EasyCorp\Bundle\EasyAdminBundle\EventListener\AdminRouterSubscriber as EasyAdminRouterSubscriber;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class PrettyUrlsRouterSubscriber implements EventSubscriberInterface
{
    private const REQUEST_ATTRIBUTES_TO_QUERY = [
        PrettyUrlsGenerator::EA_FQCN,
        PrettyUrlsGenerator::EA_ACTION,
    ];

    public static function getSubscribedEvents(): array
    {
        $easyAdminRouterSubscriberEvents = EasyAdminRouterSubscriber::getSubscribedEvents();

        return [
            RequestEvent::class => [
                ['onKernelRequest', $easyAdminRouterSubscriberEvents[RequestEvent::class][0][1] + 1],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        foreach (self::REQUEST_ATTRIBUTES_TO_QUERY as $attributeName) {
            if ($request->attributes->has($attributeName)) {
                $request->query->set($attributeName, $request->attributes->get($attributeName));
                $request->attributes->remove($attributeName);
            }
        }

        if ($request->attributes->has(PrettyUrlsGenerator::MENU_PATH)) {
            [$menuIndex, $submenuIndex] = explode(',', $request->attributes->get(PrettyUrlsGenerator::MENU_PATH));
            $request->query->set(PrettyUrlsGenerator::EA_MENU_INDEX, $menuIndex);
            $request->query->set(PrettyUrlsGenerator::EA_SUBMENU_INDEX, $submenuIndex);
            $request->attributes->remove(PrettyUrlsGenerator::MENU_PATH);
        }
    }
}
