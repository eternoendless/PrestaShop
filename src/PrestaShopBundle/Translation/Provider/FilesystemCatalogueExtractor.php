<?php
/**
 * 2007-2020 PrestaShop SA and Contributors
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
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

declare(strict_types=1);

namespace PrestaShopBundle\Translation\Provider;

use PrestaShop\PrestaShop\Core\Exception\FileNotFoundException;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

class FilesystemCatalogueExtractor implements ExtractorInterface
{
    /**
     * @var array
     */
    private $filenameFilters;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $resourceDirectory;

    /**
     * @param string $locale
     *
     * @return FilesystemCatalogueExtractor
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param array $filenameFilters
     *
     * @return FilesystemCatalogueExtractor
     */
    public function setFilenameFilters(array $filenameFilters): FilesystemCatalogueExtractor
    {
        $this->filenameFilters = $filenameFilters;

        return $this;
    }

    /**
     * @param string $resourceDirectory
     *
     * @return FilesystemCatalogueExtractor
     */
    public function setResourceDirectory(string $resourceDirectory): FilesystemCatalogueExtractor
    {
        $this->resourceDirectory = $resourceDirectory;

        return $this;
    }

    /**
     * @param bool $empty
     *
     * @return MessageCatalogueInterface
     *
     * @throws FileNotFoundException
     */
    public function extract(): MessageCatalogueInterface
    {
        $catalogue = new MessageCatalogue($this->locale);
        $translationFinder = new TranslationFinder();
        $localeResourceDirectory = $this->resourceDirectory . DIRECTORY_SEPARATOR . $this->locale;

        foreach ($this->filenameFilters as $filter) {
            try {
                $filteredCatalogue = $translationFinder->getCatalogueFromPaths(
                    [$localeResourceDirectory],
                    $this->locale,
                    $filter
                );
                $catalogue->addCatalogue($filteredCatalogue);
            } catch (FileNotFoundException $e) {
                // there are no translation files, ignore them
            }
        }

        return $catalogue;
    }
}
