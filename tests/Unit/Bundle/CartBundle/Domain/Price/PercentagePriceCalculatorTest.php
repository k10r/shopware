<?php
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

namespace Shopware\Tests\Unit\Bundle\CartBundle\Domain\Price;

use PHPUnit\Framework\TestCase;
use Shopware\Bundle\CartBundle\Domain\Price\PercentagePriceCalculator;
use Shopware\Bundle\CartBundle\Domain\Price\Price;
use Shopware\Bundle\CartBundle\Domain\Price\PriceCalculator;
use Shopware\Bundle\CartBundle\Domain\Price\PriceCollection;
use Shopware\Bundle\CartBundle\Domain\Price\PriceRounding;
use Shopware\Bundle\CartBundle\Domain\Tax\CalculatedTax;
use Shopware\Bundle\CartBundle\Domain\Tax\CalculatedTaxCollection;
use Shopware\Bundle\CartBundle\Domain\Tax\PercentageTaxRule;
use Shopware\Bundle\CartBundle\Domain\Tax\PercentageTaxRuleBuilder;
use Shopware\Bundle\CartBundle\Domain\Tax\PercentageTaxRuleCalculator;
use Shopware\Bundle\CartBundle\Domain\Tax\TaxCalculator;
use Shopware\Bundle\CartBundle\Domain\Tax\TaxRule;
use Shopware\Bundle\CartBundle\Domain\Tax\TaxRuleCalculator;
use Shopware\Bundle\CartBundle\Domain\Tax\TaxRuleCollection;
use Shopware\Tests\Unit\Bundle\CartBundle\Common\Generator;

class PercentagePriceCalculatorTest extends TestCase
{
    /**
     * @dataProvider calculatePercentagePriceOfGrossPricesProvider
     *
     * @param float           $percentage
     * @param Price           $expected
     * @param PriceCollection $prices
     */
    public function testCalculatePercentagePriceOfGrossPrices(
        $percentage,
        Price $expected,
        PriceCollection $prices
    ): void {
        $rounding = new PriceRounding(2);

        $calculator = new PercentagePriceCalculator(
            new PriceRounding(2),
            new PriceCalculator(
                new TaxCalculator(
                    new PriceRounding(2),
                    [
                        new TaxRuleCalculator($rounding),
                        new PercentageTaxRuleCalculator(new TaxRuleCalculator($rounding)),
                    ]
                ),
                $rounding,
                Generator::createGrossPriceDetector()
            ),
            new PercentageTaxRuleBuilder()
        );

        $price = $calculator->calculate(
            $percentage,
            $prices,
            Generator::createContext()
        );
        static::assertEquals($expected, $price);
        static::assertEquals($expected->getCalculatedTaxes(), $price->getCalculatedTaxes());
        static::assertEquals($expected->getTaxRules(), $price->getTaxRules());
        static::assertEquals($expected->getTotalPrice(), $price->getTotalPrice());
        static::assertEquals($expected->getUnitPrice(), $price->getUnitPrice());
        static::assertEquals($expected->getQuantity(), $price->getQuantity());
    }

    public function calculatePercentagePriceOfGrossPricesProvider(): array
    {
        $highTax = new TaxRuleCollection([new TaxRule(19)]);

        return [
            [
                //10% discount
                -10,
                //expected calculated "discount" price
                new Price(
                    -6.0,
                    -6.0,
                    new CalculatedTaxCollection([
                        new CalculatedTax(-0.48, 19, -3.0),
                        new CalculatedTax(-0.20, 7, -3.0),
                    ]),
                    new TaxRuleCollection([
                        new PercentageTaxRule(19, 50),
                        new PercentageTaxRule(7, 50),
                    ])
                ),
                //prices of cart line items
                new PriceCollection([
                    new Price(30.00, 30.00, new CalculatedTaxCollection([new CalculatedTax(4.79, 19, 30.00)]), $highTax),
                    new Price(30.00, 30.00, new CalculatedTaxCollection([new CalculatedTax(1.96, 7, 30.00)]), $highTax),
                ]),
            ],
        ];
    }
}