<?php
/**
 * 2007-2018 PrestaShop.
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
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Adapter\Presenter\Cart;

use Cart;
use Configuration;
use Context;
use Hook;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\PresenterInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;
use Product;
use Symfony\Component\Translation\TranslatorInterface;
use TaxConfiguration;

class CartPresenter implements PresenterInterface
{
    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    /**
     * @var \Link
     */
    private $link;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ImageRetriever
     */
    private $imageRetriever;

    /**
     * @var TaxConfiguration
     */
    private $taxConfiguration;

    public function __construct()
    {
        $context = Context::getContext();
        $this->priceFormatter = new PriceFormatter();
        $this->link = $context->link;
        $this->translator = $context->getTranslator();
        $this->imageRetriever = new ImageRetriever($this->link);
        $this->taxConfiguration = new TaxConfiguration();
    }

    /**
     * @return bool
     */
    private function includeTaxes()
    {
        return $this->taxConfiguration->includeTaxes();
    }

    /**
     * @param array $rawProduct
     *
     * @return ProductLazyArray|ProductListingLazyArray
     */
    public function presentProduct(array $rawProduct)
    {
        $settings = new ProductPresentationSettings();

        $settings->catalog_mode = Configuration::isCatalogMode();
        $settings->include_taxes = $this->includeTaxes();
        $settings->allow_add_variant_to_cart_from_listing = (int) Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY');
        $settings->stock_management_enabled = Configuration::get('PS_STOCK_MANAGEMENT');
        $settings->showPrices = Configuration::showPrices();

        if (isset($rawProduct['attributes']) && is_string($rawProduct['attributes'])) {
            $rawProduct['attributes'] = $this->getAttributesArrayFromString($rawProduct['attributes']);
        }
        $rawProduct['remove_from_cart_url'] = $this->link->getRemoveFromCartURL(
            $rawProduct['id_product'],
            $rawProduct['id_product_attribute']
        );

        $rawProduct['up_quantity_url'] = $this->link->getUpQuantityCartURL(
            $rawProduct['id_product'],
            $rawProduct['id_product_attribute']
        );

        $rawProduct['down_quantity_url'] = $this->link->getDownQuantityCartURL(
            $rawProduct['id_product'],
            $rawProduct['id_product_attribute']
        );

        $rawProduct['update_quantity_url'] = $this->link->getUpdateQuantityCartURL(
            $rawProduct['id_product'],
            $rawProduct['id_product_attribute']
        );

        $resetFields = array(
            'ecotax_rate',
            'specific_prices',
            'customizable',
            'online_only',
            'reduction',
            'reduction_without_tax',
            'new',
            'condition',
            'pack',
        );
        foreach ($resetFields as $field) {
            if (!array_key_exists($field, $rawProduct)) {
                $rawProduct[$field] = '';
            }
        }

        if ($this->includeTaxes()) {
            $rawProduct['price_amount'] = $rawProduct['price_wt'];
            $rawProduct['price'] = $this->priceFormatter->format($rawProduct['price_wt']);
        } else {
            $rawProduct['price_amount'] = $rawProduct['price'];
            $rawProduct['price'] = $rawProduct['price_tax_exc'] = $this->priceFormatter->format($rawProduct['price']);
        }

        if ($rawProduct['price_amount'] && $rawProduct['unit_price_ratio'] > 0) {
            $rawProduct['unit_price'] = $rawProduct['price_amount'] / $rawProduct['unit_price_ratio'];
        }

        $rawProduct['total'] = $this->priceFormatter->format(
            $this->includeTaxes() ?
            $rawProduct['total_wt'] :
            $rawProduct['total']
        );

        $rawProduct['quantity_wanted'] = $rawProduct['cart_quantity'];

        $presenter = new ProductListingPresenter(
            $this->imageRetriever,
            $this->link,
            $this->priceFormatter,
            new ProductColorsRetriever(),
            $this->translator
        );

        return $presenter->present(
            $settings,
            $rawProduct,
            Context::getContext()->language
        );
    }

    /**
     * @param array $products
     * @param Cart $cart
     *
     * @return array
     */
    public function addCustomizedData(array $products, Cart $cart)
    {
        return array_map(function ($product) use ($cart) {
            $customizations = array();

            $data = Product::getAllCustomizedDatas($cart->id, null, true, null, (int) $product['id_customization']);

            if (!$data) {
                $data = array();
            }
            $id_product = (int) $product['id_product'];
            $id_product_attribute = (int) $product['id_product_attribute'];
            if (array_key_exists($id_product, $data)) {
                if (array_key_exists($id_product_attribute, $data[$id_product])) {
                    foreach ($data[$id_product] as $byAddress) {
                        foreach ($byAddress as $byAddressCustomizations) {
                            foreach ($byAddressCustomizations as $customization) {
                                $presentedCustomization = array(
                                    'quantity' => $customization['quantity'],
                                    'fields' => array(),
                                    'id_customization' => null,
                                );

                                foreach ($customization['datas'] as $byType) {
                                    foreach ($byType as $data) {
                                        $field = array();
                                        switch ($data['type']) {
                                            case Product::CUSTOMIZE_FILE:
                                                $field['type'] = 'image';
                                                $field['image'] = $this->imageRetriever->getCustomizationImage(
                                                    $data['value']
                                                );

                                                break;
                                            case Product::CUSTOMIZE_TEXTFIELD:
                                                $field['type'] = 'text';
                                                $field['text'] = $data['value'];

                                                break;
                                            default:
                                                $field['type'] = null;
                                        }
                                        $field['label'] = $data['name'];
                                        $field['id_module'] = $data['id_module'];
                                        $presentedCustomization['id_customization'] = $data['id_customization'];
                                        $presentedCustomization['fields'][] = $field;
                                    }
                                }

                                $product['up_quantity_url'] = $this->link->getUpQuantityCartURL(
                                    $product['id_product'],
                                    $product['id_product_attribute'],
                                    $presentedCustomization['id_customization']
                                );
                                $product['down_quantity_url'] = $this->link->getDownQuantityCartURL(
                                    $product['id_product'],
                                    $product['id_product_attribute'],
                                    $presentedCustomization['id_customization']
                                );
                                $product['remove_from_cart_url'] = $this->link->getRemoveFromCartURL(
                                    $product['id_product'],
                                    $product['id_product_attribute'],
                                    $presentedCustomization['id_customization']
                                );
                                $product['update_quantity_url'] = $this->link->getUpdateQuantityCartURL(
                                    $product['id_product'],
                                    $product['id_product_attribute'],
                                    $presentedCustomization['id_customization']
                                );

                                $presentedCustomization['up_quantity_url'] = $this->link->getUpQuantityCartURL(
                                    $product['id_product'],
                                    $product['id_product_attribute'],
                                    $presentedCustomization['id_customization']
                                );

                                $presentedCustomization['down_quantity_url'] = $this->link->getDownQuantityCartURL(
                                    $product['id_product'],
                                    $product['id_product_attribute'],
                                    $presentedCustomization['id_customization']
                                );

                                $presentedCustomization['remove_from_cart_url'] = $this->link->getRemoveFromCartURL(
                                    $product['id_product'],
                                    $product['id_product_attribute'],
                                    $presentedCustomization['id_customization']
                                );

                                $presentedCustomization['update_quantity_url'] = $product['update_quantity_url'];

                                $customizations[] = $presentedCustomization;
                            }
                        }
                    }
                }
            }

            usort($customizations, function (array $a, array $b) {
                if (
                    $a['quantity'] > $b['quantity']
                    || count($a['fields']) > count($b['fields'])
                    || $a['id_customization'] > $b['id_customization']
                ) {
                    return -1;
                } else {
                    return 1;
                }
            });

            $product['customizations'] = $customizations;

            return $product;
        }, $products);
    }

    /**
     * @param Cart $cart
     * @param bool $shouldSeparateGifts
     *
     * @return CartLazyArray
     *
     * @throws \Exception
     */
    public function present($cart, $shouldSeparateGifts = false)
    {
        if (!$cart instanceof Cart) {
            throw new \Exception('CartPresenter can only present instance of Cart');
        }

        $minimalPurchase = $this->priceFormatter->convertAmount((float) Configuration::get('PS_PURCHASE_MINIMUM'));

        Hook::exec('overrideMinimalPurchasePrice', array(
            'minimalPurchase' => &$minimalPurchase,
        ));

        return new CartLazyArray(
            $cart,
            $this,
            $this->translator,
            $this->priceFormatter,
            $this->link,
            $shouldSeparateGifts,
            $this->includeTaxes(),
            (bool) Configuration::get('PS_TAX_DISPLAY'),
            (float) $minimalPurchase
        );
    }

    /**
     * Receives a string containing a list of attributes affected to the product and returns them as an array.
     *
     * @param string $attributes
     *
     * @return array Converted attributes in an array
     */
    protected function getAttributesArrayFromString($attributes)
    {
        $separator = Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR');
        $pattern = '/(?>(?P<attribute>[^:]+:[^:]+)' . $separator . '+(?!' . $separator . '([^:' . $separator . '])+:))/';
        $attributesArray = array();
        $matches = array();
        if (!preg_match_all($pattern, $attributes . $separator, $matches)) {
            return $attributesArray;
        }

        foreach ($matches['attribute'] as $attribute) {
            list($key, $value) = explode(':', $attribute);
            $attributesArray[trim($key)] = ltrim($value);
        }

        return $attributesArray;
    }
}
