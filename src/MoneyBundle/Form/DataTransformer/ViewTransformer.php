<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MoneyBundle\Form\DataTransformer;

use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class ViewTransformer implements DataTransformerInterface
{
    private Currency $currency;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function transform($value)
    {
        if ($value instanceof Money) {
            return $value->getAmount() / 100;
        }

        return 0;
    }

    public function reverseTransform($value): Money
    {
        if (null === $value) {
            $value = 0;
        }

        return new Money(((int) $value * 100), $this->currency);
    }
}
