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
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

abstract class AbstractProvider implements ProviderInterface
{
    const DEFAULT_LOCALE = 'en-US';

    /**
     * @var string Path where translation files are found
     */
    protected $resourceDirectory;

    /**
     * @var string Catalogue domain
     */
    protected $domain;

    /**
     * @var LoaderInterface Loader for database translations
     */
    private $databaseLoader;

    /**
     * @param LoaderInterface $databaseLoader
     * @param string $resourceDirectory Path where translations are found
     */
    public function __construct(LoaderInterface $databaseLoader, string $resourceDirectory)
    {
        $this->databaseLoader = $databaseLoader;
        $this->resourceDirectory = $resourceDirectory;
    }

    /**
     * @param string $domain
     *
     * @return static
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCatalogue(string $locale): MessageCatalogueInterface
    {
        $messageCatalogue = $this->getDefaultCatalogue($locale);

        // Merge catalogues

        $xlfCatalogue = $this->getFilesystemCatalogue($locale);
        $messageCatalogue->addCatalogue($xlfCatalogue);
        unset($xlfCatalogue);

        $databaseCatalogue = $this->getUserTranslatedCatalogue($locale);
        $messageCatalogue->addCatalogue($databaseCatalogue);
        unset($databaseCatalogue);

        return $messageCatalogue;
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileNotFoundException
     */
    public function getDefaultCatalogue(string $locale, bool $empty = true): MessageCatalogueInterface
    {
        $defaultCatalogue = new MessageCatalogue($locale);

        foreach ($this->getFilenameFilters() as $filter) {
            $filteredCatalogue = $this->getCatalogueFromPaths(
                [$this->getDefaultResourceDirectory()],
                $locale,
                $filter
            );
            $defaultCatalogue->addCatalogue($filteredCatalogue);
        }

        if ($empty && $locale !== self::DEFAULT_LOCALE) {
            $defaultCatalogue = $this->emptyCatalogue($defaultCatalogue);
        }

        return $defaultCatalogue;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesystemCatalogue(string $locale): MessageCatalogueInterface
    {
        $xlfCatalogue = new MessageCatalogue($locale);

        foreach ($this->getFilenameFilters() as $filter) {
            try {
                $filteredCatalogue = $this->getCatalogueFromPaths(
                    $this->getDirectories($locale),
                    $locale,
                    $filter
                );
                $xlfCatalogue->addCatalogue($filteredCatalogue);
            } catch (FileNotFoundException $e) {
                // there are no translation files, ignore them
            }
        }

        return $xlfCatalogue;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserTranslatedCatalogue(string $locale, string $theme = null): MessageCatalogueInterface
    {
        $databaseCatalogue = new MessageCatalogue($locale);

        foreach ($this->getTranslationDomains() as $translationDomain) {
            $domainCatalogue = $this->getDatabaseLoader()->load(null, $locale, $translationDomain, $theme);

            if ($domainCatalogue instanceof MessageCatalogue) {
                $databaseCatalogue->addCatalogue($domainCatalogue);
            }
        }

        return $databaseCatalogue;
    }

    /**
     * Loads the catalogue from the provided paths
     *
     * @param string|string[] $paths a list of paths when we can look for translations
     * @param string $locale the Symfony (not the PrestaShop one) locale
     * @param string|null $pattern a regular expression
     *
     * @return MessageCatalogueInterface
     *
     * @throws FileNotFoundException
     */
    protected function getCatalogueFromPaths($paths, string $locale, ?string $pattern = null): MessageCatalogueInterface
    {
        return (new TranslationFinder())->getCatalogueFromPaths($paths, $locale, $pattern);
    }

    /**
     * Returns a list of directories to crawl for Xliff files
     *
     * @param string $locale IETF language tag
     *
     * @return string[]
     */
    protected function getDirectories(string $locale): array
    {
        return [$this->getResourceDirectory($locale)];
    }

    /**
     * Returns a list of patterns used to filter a catalogue (including XLF file lookup) by translation domain.
     *
     * Only matching domains will be loaded by this provider.
     * Multiple filters are computed using OR.
     *
     * @return string[]
     */
    protected function getFilenameFilters(): array
    {
        return [];
    }

    /**
     * Returns a list of patterns used to choose which wordings will be imported from database.
     * Patterns from this list will be run against translation domains.
     *
     * @return string[] List of Mysql compatible regexes (no regex delimiter)
     */
    protected function getTranslationDomains(): array
    {
        return [''];
    }

    /**
     * Returns the directory where translation files for the current locale are
     *
     * @param string $locale IETF language tag
     *
     * @return string
     */
    protected function getResourceDirectory(string $locale): string
    {
        return $this->resourceDirectory . DIRECTORY_SEPARATOR . $locale;
    }

    /**
     * Returns the loader for database translations
     *
     * @return LoaderInterface
     */
    protected function getDatabaseLoader(): LoaderInterface
    {
        return $this->databaseLoader;
    }

    /**
     * Empties out the catalogue by removing translations but leaving keys
     *
     * @param MessageCatalogueInterface $messageCatalogue
     *
     * @return MessageCatalogueInterface Empty the catalogue
     */
    protected function emptyCatalogue(MessageCatalogueInterface $messageCatalogue): MessageCatalogueInterface
    {
        foreach ($messageCatalogue->all() as $domain => $messages) {
            foreach (array_keys($messages) as $translationKey) {
                $messageCatalogue->set($translationKey, '', $domain);
            }
        }

        return $messageCatalogue;
    }

    /**
     * Returns the path to the directory where the default (aka not translated) catalogue is
     *
     * Most of the time, it's `app/Resources/translations/default/{locale}`
     *
     * @return string
     */
    abstract protected function getDefaultResourceDirectory(): string;
}
