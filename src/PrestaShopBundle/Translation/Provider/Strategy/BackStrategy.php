<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
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
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShopBundle\Translation\Provider\Strategy;

use PrestaShopBundle\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Represents strategy for 'back' type of translation.
 * It must use the BackProvider and required parameters.
 */
class BackStrategy implements StrategyInterface
{
    /**
     * @var string
     */
    private $locale;
    /**
     * @var ProviderInterface
     */
    private $provider;

    /**
     * @param ProviderInterface $provider
     * @param string $locale
     */
    public function __construct(ProviderInterface $provider, string $locale)
    {
        $this->locale = $locale;
        $this->provider = $provider;
    }

    /**
     * @param bool $empty
     *
     * @return MessageCatalogueInterface|null
     */
    public function getDefaultCatalogue(bool $empty = true): ?MessageCatalogueInterface
    {
        return $this->provider->getDefaultCatalogue($this->locale, $empty);
    }

    /**
     * @return MessageCatalogueInterface|null
     */
    public function getFileTranslatedCatalogue(): ?MessageCatalogueInterface
    {
        return $this->provider->getFileTranslatedCatalogue($this->locale);
    }

    /**
     * @param string|null $domain
     *
     * @return MessageCatalogueInterface|null
     */
    public function getUserTranslatedCatalogue(?string $domain = null): ?MessageCatalogueInterface
    {
        return $this->provider->getUserTranslatedCatalogue($this->locale, $domain);
    }
}
