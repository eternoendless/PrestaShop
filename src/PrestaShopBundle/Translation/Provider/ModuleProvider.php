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

namespace PrestaShopBundle\Translation\Provider;

use PrestaShop\TranslationToolsBundle\Translation\Helper\DomainHelper;

/**
 * Translation provider for a specific native module (maintained by the core team)
 * Used mainly for the display in the Translations Manager of the Back Office.
 */
class ModuleProvider extends AbstractProvider implements SearchProviderInterface
{
    /**
     * @var string the module name
     */
    private $moduleName;

    /**
     * {@inheritdoc}
     */
    protected function getTranslationDomains()
    {
        return ['^' . preg_quote(DomainHelper::buildModuleBaseDomain($this->moduleName)) . '([A-Z]|$)'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilenameFilters()
    {
        return ['#^' . preg_quote(DomainHelper::buildModuleBaseDomain($this->moduleName)) . '([A-Z]|\.|$)#'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'module';
    }

    /**
     * {@inheritdoc}
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultResourceDirectory()
    {
        return $this->resourceDirectory . DIRECTORY_SEPARATOR . 'default';
    }
}
