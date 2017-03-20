<?php
declare(strict_types=1);
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\CartBundle\Domain\Delivery;

use Shopware\Bundle\CartBundle\Domain\LineItem\DeliverableLineItemInterface;
use Shopware\Bundle\CartBundle\Domain\Price\PriceCollection;
use Shopware\Bundle\StoreFrontBundle\Common\Collection;

class DeliveryCollection extends Collection
{
    /**
     * @var Delivery[]
     */
    protected $elements = [];

    public function add(Delivery $delivery): void
    {
        parent::doAdd($delivery);
    }

    public function remove(string $key): void
    {
        parent::doRemoveByKey($key);
    }

    /**
     * Sorts the delivery collection by earliest delivery date
     */
    public function sort(): void
    {
        usort(
            $this->elements,
            function (Delivery $a, Delivery $b) {
                if ($a->getLocation() !== $b->getLocation()) {
                    return -1;
                }

                return $a->getDeliveryDate()->getEarliest() > $b->getDeliveryDate()->getEarliest();
            }
        );
    }

    public function getDelivery(DeliveryDate $deliveryDate, ShippingLocation $location): ? Delivery
    {
        foreach ($this->elements as $delivery) {
            //find delivery with same data
            //use only single "=", otherwise same object is expected
            if ($delivery->getDeliveryDate() != $deliveryDate) {
                continue;
            }
            if ($delivery->getLocation() != $location) {
                continue;
            }

            return $delivery;
        }

        return null;
    }

    /**
     * @param DeliverableLineItemInterface $item
     *
     * @return bool
     */
    public function contains(DeliverableLineItemInterface $item): bool
    {
        foreach ($this->elements as $delivery) {
            if ($delivery->getPositions()->has($item->getIdentifier())) {
                return true;
            }
        }

        return false;
    }

    public function getShippingCosts(): PriceCollection
    {
        return new PriceCollection(
            $this->map(function (Delivery $delivery) {
                return $delivery->getShippingCosts();
            })
        );
    }
}