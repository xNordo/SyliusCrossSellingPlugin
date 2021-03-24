<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace spec\BitBag\SyliusUpsellingPlugin\PropertyBuilder;

use BitBag\SyliusUpsellingPlugin\PropertyBuilder\RelatedProductsPropertyBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Elastica\Document;
use FOS\ElasticaBundle\Event\TransformEvent;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RelatedProductsPropertyBuilderSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(RelatedProductsPropertyBuilder::class);
    }

    function it_implements_event_subscriber_interface(): void
    {
        $this->shouldHaveType(EventSubscriberInterface::class);
    }

    function it_consumes_event(
        TransformEvent $event,
        OrderInterface $model,
        Document $document,
        OrderItemInterface $orderItem1,
        OrderItemInterface $orderItem2,
        ProductInterface $product1,
        ProductInterface $product2
    ): void {
        $event->getObject()->willReturn($model);
        $event->getDocument()->willReturn($document);

        $orderItems = new ArrayCollection([
            $orderItem1->getWrappedObject(),
            $orderItem2->getWrappedObject()
        ]);

        $orderItem1->getProduct()->willReturn($product1->getWrappedObject());
        $orderItem2->getProduct()->willReturn($product2->getWrappedObject());

        $product1->getId()->willReturn(123);
        $product2->getId()->willReturn(456);

        $model->getState()->willReturn(OrderInterface::STATE_NEW);
        $model->getItems()->willReturn($orderItems);

        $this->consumeEvent($event);

        $document->set(RelatedProductsPropertyBuilder::PROPERTY_PRODUCT_IDS, [123, 456])
            ->shouldHaveBeenCalled();
    }

    function it_ignores_non_order_models(
        TransformEvent $event,
        ProductInterface $model
    ): void {
        $event->getObject()->willReturn($model);

        $this->consumeEvent($event);
        $event->getDocument()->shouldNotHaveBeenCalled();
    }

    function it_ignores_orders_in_state_cart(
        TransformEvent $event,
        OrderInterface $model
    ): void {
        $event->getObject()->willReturn($model);

        $model->getState()->willReturn(OrderInterface::STATE_CART);

        $this->consumeEvent($event);

        $event->getDocument()->shouldNotHaveBeenCalled();
    }
}