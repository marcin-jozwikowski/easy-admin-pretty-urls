<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\EventSubscriber\PrettyUrlsRouterSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class PrettyUrlsRouterSubscriberTest extends TestCase
{
    private const CRUD_FQCN = 'crudControllerFqcn';
    private const CRUD_ACTION = 'crudAction';

    private MockObject|ParameterBag $attributes;
    private MockObject|ParameterBag $query;
    private PrettyUrlsRouterSubscriber $testedClass;
    private RequestEvent|MockObject $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->attributes = $this->createMock(ParameterBag::class);
        $this->query = $this->createMock(ParameterBag::class);

        $request = $this->createMock(Request::class);
        $request->attributes = $this->attributes;
        $request->query = $this->query;

        $this->event = $this->createMock(RequestEvent::class);
        $this->event->expects(self::once())
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

        $this->query->expects(self::never())
            ->method('set');

        $this->attributes->expects(self::never())
            ->method('remove');

        $this->testedClass->onKernelRequest($this->event);
    }

    public function testOnKernelRequest(): void
    {
        $consecutiveValues = [base64_encode(random_bytes(5)), base64_encode(random_bytes(5))];

        $this->attributes->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION])
            ->willReturnOnConsecutiveCalls(true, true);

        $this->attributes->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION])
            ->willReturnOnConsecutiveCalls($consecutiveValues[0], $consecutiveValues[1]);

        $this->query->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                [self::CRUD_FQCN, $consecutiveValues[0]],
                [self::CRUD_ACTION, $consecutiveValues[1]],
            )
            ->willReturn(null);

        $this->attributes->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive([self::CRUD_FQCN], [self::CRUD_ACTION])
            ->willReturn(null);

        $this->testedClass->onKernelRequest($this->event);
    }
}
