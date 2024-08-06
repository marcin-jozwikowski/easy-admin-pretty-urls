<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\EventSubscriber\PrettyUrlsRouterSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\EventSubscriber\PrettyUrlsRouterSubscriber
 */
class PrettyUrlsRouterSubscriberTest extends TestCase
{
    private const CRUD_FQCN = 'crudControllerFqcn';
    private const CRUD_ACTION = 'crudAction';
    private const CRUD_MENU_INDEX = 'menuIndex';
    private const CRUD_ENTITY_ID = 'entityId';
    private const CRUD_SUBMENU_INDEX = 'submenuIndex';
    private const MENU_PATH = 'menuPath';

    private MockObject|ParameterBag $attributes;
    private InputBag $query;
    private PrettyUrlsRouterSubscriber $testedClass;
    private RequestEvent|MockObject $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->attributes = $this->createMock(ParameterBag::class);
        $this->query = new InputBag();

        $request = $this->createMock(Request::class);
        $request->attributes = $this->attributes;
        $request->query = $this->query;

        $this->event = $this->createMock(RequestEvent::class);
        $this->event->expects(self::atMost(1))
            ->method('getRequest')
            ->willReturn($request);

        $this->testedClass = new PrettyUrlsRouterSubscriber();
    }

    public function testOnKernelRequestNoMatchingParams(): void
    {
        $this->attributes->expects(self::any())
            ->method('has')
            ->withAnyParameters()
            ->willReturn(false);

        $this->attributes->expects(self::never())
            ->method('remove');

        $this->testedClass->onKernelRequest($this->event);
    }

    public function testOnKernelRequest(): void
    {
        $consecutiveValues = [base64_encode(random_bytes(5)), base64_encode(random_bytes(5))];

        $this->attributes->expects(self::exactly(4))
            ->method('has')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION], [self::CRUD_ENTITY_ID], [self::MENU_PATH])
            ->willReturnOnConsecutiveCalls(true, true, false, false);

        $this->attributes->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION])
            ->willReturnOnConsecutiveCalls($consecutiveValues[0], $consecutiveValues[1]);

        $this->attributes->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION]);

        $this->testedClass->onKernelRequest($this->event);
    }

    public function testOnKernelRequestForAll(): void
    {
        $menuIndex = base64_encode(random_bytes(5));
        $submenuIndex = base64_encode(random_bytes(5));
        $consecutiveValues = [
            base64_encode(random_bytes(5)),
            base64_encode(random_bytes(5)),
            random_int(10, 100),
            $menuIndex.','.$submenuIndex,
        ];

        $this->attributes->expects(self::exactly(4))
            ->method('has')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION], [self::CRUD_ENTITY_ID], [self::MENU_PATH])
            ->willReturnOnConsecutiveCalls(true, true, true, true);

        $this->attributes->expects(self::exactly(4))
            ->method('get')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION], [self::CRUD_ENTITY_ID], [self::MENU_PATH])
            ->willReturnOnConsecutiveCalls($consecutiveValues[0], $consecutiveValues[1], $consecutiveValues[2], $consecutiveValues[3]);

        $this->attributes->expects(self::exactly(4))
            ->method('remove')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION], [self::CRUD_ENTITY_ID], [self::MENU_PATH]);

        $this->testedClass->onKernelRequest($this->event);
    }

    public function testOnKernelRequestForEmptyEntityId(): void
    {
        $menuIndex = base64_encode(random_bytes(5));
        $submenuIndex = base64_encode(random_bytes(5));
        $consecutiveValues = [
            base64_encode(random_bytes(5)),
            base64_encode(random_bytes(5)),
            '',
            $menuIndex.','.$submenuIndex,
        ];

        $this->attributes->expects(self::exactly(4))
            ->method('has')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION], [self::CRUD_ENTITY_ID], [self::MENU_PATH])
            ->willReturnOnConsecutiveCalls(true, true, true, true);

        $this->attributes->expects(self::exactly(4))
            ->method('get')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION], [self::CRUD_ENTITY_ID], [self::MENU_PATH])
            ->willReturnOnConsecutiveCalls($consecutiveValues[0], $consecutiveValues[1], $consecutiveValues[2], $consecutiveValues[3]);

        $this->attributes->expects(self::exactly(4))
            ->method('remove')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION], [self::CRUD_ENTITY_ID], [self::MENU_PATH]);

        $this->testedClass->onKernelRequest($this->event);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = $this->testedClass::getSubscribedEvents();
        self::assertEquals([
            RequestEvent::class => [
                ['onKernelRequest', 1],
            ],
        ], $events);
    }
}
