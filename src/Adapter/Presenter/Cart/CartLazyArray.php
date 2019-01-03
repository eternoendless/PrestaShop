<?php

/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Adapter\Presenter\Cart;

use Cart;
use CartRule;
use Link;
use Tools;
use PrestaShop\PrestaShop\Adapter\Presenter\AbstractLazyArray;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use Symfony\Component\Translation\TranslatorInterface;

class CartLazyArray extends AbstractLazyArray
{
    /**
     * @var array
     */
    private $cartData;

    /**
     * @var CartPresenter
     */
    private $cartPresenter;

    /**
     * @var bool
     */
    private $shouldSeparateGifts;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var bool
     */
    private $includeTaxes;

    /**
     * @var bool
     */
    private $displayTaxSeparately;

    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var float
     */
    private $totalExcludingTax;

    /**
     * @var float
     */
    private $totalIncludingTax;

    /**
     * @var float
     */
    private $productsTotalExcludingTags;

    /**
     * @var float
     */
    private $minimalPurchase;

    /**
     * CartLazyArray constructor.
     *
     * @param Cart $cart
     * @param CartPresenter $cartPresenter
     * @param TranslatorInterface $translator
     * @param PriceFormatter $priceFormatter
     * @param Link $link
     * @param bool $shouldSeparateGifts Whether or not to display gifts separately
     * @param bool $includeTaxes Whether or not to display prices including tax
     * @param bool $displayTaxes Whether or not to display tax on a distinct line in the cart
     * @param float $minimalPurchase Minimum amount needed to validate an order (in default currency)
     *
     * @throws \ReflectionException
     */
    public function __construct(
        Cart $cart,
        CartPresenter $cartPresenter,
        TranslatorInterface $translator,
        PriceFormatter $priceFormatter,
        Link $link,
        $shouldSeparateGifts,
        $includeTaxes,
        $displayTaxes,
        $minimalPurchase
    ) {
        $this->minimalPurchase = $minimalPurchase;
        $this->displayTaxSeparately = $displayTaxes;
        $this->cartPresenter = $cartPresenter;
        $this->shouldSeparateGifts = $shouldSeparateGifts;
        $this->translator = $translator;
        $this->cart = $cart;
        $this->includeTaxes = $includeTaxes;
        $this->priceFormatter = $priceFormatter;
        $this->link = $link;

        parent::__construct();
    }

    /**
     * @arrayAccess
     *
     * @return array
     */
    public function getProducts()
    {
        if (!isset($this->cartData['products'])) {
            if ($this->shouldSeparateGifts) {
                $rawProducts = $this->cart->getProductsWithSeparatedGifts();
            } else {
                $rawProducts = $this->cart->getProducts(true);
            }

            $products = array_map(array($this->cartPresenter, 'presentProduct'), $rawProducts);
            $this->cartData['products'] = $this->cartPresenter->addCustomizedData($products, $this->cart);
        }

        return $this->cartData['products'];
    }

    /**
     * @arrayAccess
     *
     * @return array
     */
    public function getTotals()
    {
        if (!isset($this->cartData['totals'])) {
            $totalIncludingTax = $this->getTotalIncludingTax();
            $totalExcludingTax = $this->getTotalExcludingTax();

            $this->cartData['totals'] = array(
                'total' => array(
                    'type' => 'total',
                    'label' => $this->translator->trans('Total', array(), 'Shop.Theme.Checkout'),
                    'amount' => $this->includeTaxes ? $totalIncludingTax : $totalExcludingTax,
                    'value' => $this->priceFormatter->format(
                        $this->includeTaxes ? $totalIncludingTax : $totalExcludingTax
                    ),
                ),
                'total_including_tax' => array(
                    'type' => 'total',
                    'label' => $this->translator->trans('Total (tax incl.)', array(), 'Shop.Theme.Checkout'),
                    'amount' => $totalIncludingTax,
                    'value' => $this->priceFormatter->format($totalIncludingTax),
                ),
                'total_excluding_tax' => array(
                    'type' => 'total',
                    'label' => $this->translator->trans('Total (tax excl.)', array(), 'Shop.Theme.Checkout'),
                    'amount' => $totalExcludingTax,
                    'value' => $this->priceFormatter->format($totalExcludingTax),
                ),
            );
        }

        return $this->cartData['totals'];
    }

    /**
     * @arrayAccess
     *
     * @return mixed
     */
    public function getSubtotals()
    {
        $subtotals = [];

        $totalExcludingTax = $this->getTotalExcludingTax();
        $totalIncludingTax = $this->getTotalIncludingTax();
        $totalDiscount = $this->cart->getDiscountSubtotalWithoutGifts();
        $totalCartAmount = $this->cart->getOrderTotal($this->includeTaxes, Cart::ONLY_PRODUCTS);

        $subtotals['products'] = array(
            'type' => 'products',
            'label' => $this->translator->trans('Subtotal', array(), 'Shop.Theme.Checkout'),
            'amount' => $totalCartAmount,
            'value' => $this->priceFormatter->format($totalCartAmount),
        );

        if ($totalDiscount) {
            $subtotals['discounts'] = array(
                'type' => 'discount',
                'label' => $this->translator->trans('Discount', array(), 'Shop.Theme.Checkout'),
                'amount' => $totalDiscount,
                'value' => $this->priceFormatter->format($totalDiscount),
            );
        } else {
            $subtotals['discounts'] = null;
        }

        if ($this->cart->gift) {
            $giftWrappingPrice = (float) $this->cart->getGiftWrappingPrice($this->includeTaxes);

            $subtotals['gift_wrapping'] = array(
                'type' => 'gift_wrapping',
                'label' => $this->translator->trans('Gift wrapping', array(), 'Shop.Theme.Checkout'),
                'amount' => $giftWrappingPrice,
                'value' => ($giftWrappingPrice > 0)
                    ? $this->priceFormatter->convertAndFormat($giftWrappingPrice)
                    : $this->translator->trans('Free', array(), 'Shop.Theme.Checkout'),
            );
        }

        $shippingCost = (!$this->cart->isVirtualCart())
            ? $this->cart->getTotalShippingCost(null, $this->includeTaxes)
            : 0;

        $subtotals['shipping'] = array(
            'type' => 'shipping',
            'label' => $this->translator->trans('Shipping', array(), 'Shop.Theme.Checkout'),
            'amount' => $shippingCost,
            'value' => $shippingCost != 0
                ? $this->priceFormatter->format($shippingCost)
                : $this->translator->trans('Free', array(), 'Shop.Theme.Checkout'),
        );

        $subtotals['tax'] = null;
        if ($this->displayTaxSeparately) {
            $taxAmount = $totalIncludingTax - $totalExcludingTax;
            $subtotals['tax'] = array(
                'type' => 'tax',
                'label' => ($this->includeTaxes)
                    ? $this->translator->trans('Included taxes', array(), 'Shop.Theme.Checkout')
                    : $this->translator->trans('Taxes', array(), 'Shop.Theme.Checkout'),
                'amount' => $taxAmount,
                'value' => $this->priceFormatter->format($taxAmount),
            );
        }

        return $subtotals;
    }

    /**
     * @arrayAccess
     *
     * @return int
     */
    public function getProductsCount()
    {
        $productsCount = array_reduce(
            $this->offsetGet('products'),
            function ($count, $product) {
                return $count + $product['quantity'];
            },
            0
        );

        return $productsCount;
    }

    /**
     * @arrayAccess
     *
     * @return string
     */
    public function getSummaryString()
    {
        // load cached property
        $productsCount = $this->offsetGet('products_count');

        $summaryString = $this->cartData['summary_string'] = $productsCount === 1 ?
            $this->translator->trans('1 item', [], 'Shop.Theme.Checkout') :
            $this->translator->trans('%count% items', ['%count%' => $productsCount], 'Shop.Theme.Checkout');

        return $summaryString;
    }

    /**
     * @arrayAccess
     *
     * @return array
     */
    public function getLabels()
    {
        // TODO: move it to a common parent, since it's copied in OrderPresenter and ProductPresenter
        return [
            'tax_short' => ($this->includeTaxes)
                ? $this->translator->trans('(tax incl.)', [], 'Shop.Theme.Global')
                : $this->translator->trans('(tax excl.)', [], 'Shop.Theme.Global'),
            'tax_long' => ($this->includeTaxes)
                ? $this->translator->trans('(tax included)', [], 'Shop.Theme.Global')
                : $this->translator->trans('(tax excluded)', [], 'Shop.Theme.Global'),
        ];
    }

    /**
     * @arrayAccess
     *
     * @return int
     */
    public function getIdAddressDelivery()
    {
        return $this->cart->id_address_delivery;
    }

    /**
     * @arrayAccess
     *
     * @return int
     */
    public function getIdAddressInvoice()
    {
        return $this->cart->id_address_invoice;
    }

    /**
     * @arrayAccess
     *
     * @return bool
     */
    public function getIsVirtual()
    {
        return $this->cart->isVirtualCart();
    }

    /**
     * @arrayAccess
     *
     * @return array
     */
    public function getVouchers()
    {
        $cartVouchers = $this->cart->getCartRules();
        $vouchers = array();

        $cartHasTax = is_null($this->cart->id)
            ? false
            : $this->cart->getAverageProductsTaxRate();

        foreach ($cartVouchers as $cartVoucher) {
            $idCartRule = $cartVoucher['id_cart_rule'];
            $vouchers[$idCartRule] = [
                'id_cart_rule' => $idCartRule,
                'name' => $cartVoucher['name'],
                'reduction_percent' => $cartVoucher['reduction_percent'],
                'reduction_currency' => $cartVoucher['reduction_currency'],
            ];

            // Voucher reduction depending of the cart tax rule
            // if $cartHasTax & voucher is tax excluded, set amount voucher to tax included
            if ($cartHasTax && $cartVoucher['reduction_tax'] == '0') {
                $cartVoucher['reduction_amount'] = $cartVoucher['reduction_amount'] * (1 + $cartHasTax / 100);
            }

            $vouchers[$idCartRule]['reduction_amount'] = $cartVoucher['reduction_amount'];

            if (array_key_exists('gift_product', $cartVoucher) && $cartVoucher['gift_product']) {
                $cartVoucher['reduction_amount'] = $cartVoucher['value_real'];
            }

            if (isset($cartVoucher['reduction_percent']) && $cartVoucher['reduction_amount'] == '0.00') {
                $cartVoucher['reduction_formatted'] = $cartVoucher['reduction_percent'] . '%';
            } elseif (isset($cartVoucher['reduction_amount']) && $cartVoucher['reduction_amount'] > 0) {
                $cartVoucher['reduction_formatted'] = $this->priceFormatter->convertAndFormat(
$cartVoucher['reduction_amount']
                );
            }

            $vouchers[$idCartRule]['reduction_formatted'] = '-' . $cartVoucher['reduction_formatted'];
            $vouchers[$idCartRule]['delete_url'] = $this->link->getPageLink(
                'cart',
                true,
                null,
                array(
                    'deleteDiscount' => $idCartRule,
                    'token' => Tools::getToken(false),
                )
            );
        }

        return array(
            'allowed' => (int) CartRule::isFeatureActive(),
            'added' => $vouchers,
        );
    }

    /**
     * @arrayAccess
     *
     * @return array
     */
    public function getDiscounts()
    {
        $discounts = $this->cart->getDiscounts();

        // get cached vouchers
        $vouchers = $this->offsetGet('vouchers');

        $cartRulesIds = array_flip(
            array_map(
                function ($voucher) {
                    return $voucher['id_cart_rule'];
                },
                $vouchers['added']
            )
        );

        $discounts = array_filter(
            $discounts,
            function ($discount) use ($cartRulesIds) {
                return !array_key_exists($discount['id_cart_rule'], $cartRulesIds);
            }
        );

        return $discounts;
    }

    /**
     * @arrayAccess
     *
     * @return float
     */
    public function getMinimalPurchase()
    {
        return $this->priceFormatter->convertAmount($this->minimalPurchase);
    }

    /**
     * @arrayAccess
     *
     * @return string
     */
    public function getMinimalPurchaseRequired()
    {
        $minimalPurchase = $this->getMinimalPurchase();
        $productsTotalExcludingTax = $this->getProductsTotalExcludingTax();

        $minimalPurchaseRequired = ($productsTotalExcludingTax < $minimalPurchase) ?
            $this->translator->trans(
                'A minimum shopping cart total of %amount% (tax excl.) is required to validate your order. Current cart total is %total% (tax excl.).',
                array(
                    '%amount%' => $this->priceFormatter->format($minimalPurchase),
                    '%total%' => $this->priceFormatter->format($productsTotalExcludingTax),
                ),
                'Shop.Theme.Checkout'
            ) :
            '';

        return $minimalPurchaseRequired;
    }

    /**
     * @return float
     *
     * @throws \Exception
     */
    private function getProductsTotalExcludingTax()
    {
        if (!isset($this->productsTotalExcludingTags)) {
            $this->productsTotalExcludingTags = $this->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        }

        return $this->productsTotalExcludingTags;
    }

    /**
     * @return float
     *
     * @throws \Exception
     */
    private function getTotalExcludingTax()
    {
        if (!isset($this->totalExcludingTax)) {
            $this->totalExcludingTax = $this->cart->getOrderTotal(false);
        }

        return $this->totalExcludingTax;
    }

    /**
     * @return float
     *
     * @throws \Exception
     */
    private function getTotalIncludingTax()
    {
        if (!isset($this->totalIncludingTax)) {
            $this->totalIncludingTax = $this->cart->getOrderTotal(true);
        }

        return $this->totalIncludingTax;
    }
}
