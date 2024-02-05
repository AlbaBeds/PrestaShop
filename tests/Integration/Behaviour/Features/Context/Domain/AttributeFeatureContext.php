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

namespace Tests\Integration\Behaviour\Features\Context\Domain;

use AttributeGroup;
use Behat\Gherkin\Node\TableNode;
use Language;
use PHPUnit\Framework\Assert;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\Command\AddAttributeCommand;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\Command\DeleteAttributeCommand;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\Command\EditAttributeCommand;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\Exception\AttributeConstraintException;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\Exception\AttributeNotFoundException;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\Query\GetAttributeForEditing;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\QueryResult\EditableAttribute;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Attribute\ValueObject\AttributeId;
use PrestaShop\PrestaShop\Core\Domain\AttributeGroup\Exception\AttributeGroupConstraintException;
use Tests\Integration\Behaviour\Features\Context\CommonFeatureContext;
use Tests\Integration\Behaviour\Features\Context\Util\NoExceptionAlthoughExpectedException;

class AttributeFeatureContext extends AbstractDomainFeatureContext
{
    /**
     * @var int default language id from configs
     */
    private $defaultLangId;

    /**
     * @var int default shop id from configs
     */
    private $defaultShopId;

    public function __construct()
    {
        $configuration = CommonFeatureContext::getContainer()->get('prestashop.adapter.legacy.configuration');
        $this->defaultLangId = $configuration->get('PS_LANG_DEFAULT');
        $this->defaultShopId = $configuration->get('PS_SHOP_DEFAULT');
    }

    /**
     * @TODO temporary so tests for attributes can be created, since they need attribute group.
     * Proper attribute group creation and behat implementation are done in PR #31502
     * and should be used from there once it's merged.
     *
     * @When I create attribute group :reference with specified properties:
     */
    public function createAttributeGroup(string $reference, TableNode $node): void
    {
        $properties = $node->getRowsHash();
        $attributeGroup = new AttributeGroup();
        $name = [];
        $publicName = [];
        foreach (Language::getLanguages() as $language) {
            $name[$language['id_lang']] = $properties['name'];
            $publicName[$language['id_lang']] = $properties['public_name'];
        }

        $attributeGroup->public_name = $publicName;
        $attributeGroup->name = $name;
        $attributeGroup->group_type = $properties['type'];
        $attributeGroup->add();

        $this->getSharedStorage()->set($reference, (int) $attributeGroup->id);
    }

    /**
     * @When I create attribute :reference with specified properties:
     */
    public function createAttribute(string $reference, TableNode $node): void
    {
        $properties = $this->localizeByRows($node);
        $attributeGroupId = $this->referenceToId($properties['attribute_group']);
        $attributeId = $this->createAttributeUsingCommand($attributeGroupId, $properties['value'], $properties['color']);

        $this->getSharedStorage()->set($reference, $attributeId->getValue());
    }

    /**
     * @When I edit attribute :reference with specified properties:
     */
    public function editAttribute(string $reference, TableNode $node): void
    {
        $properties = $this->localizeByRows($node);

        $attributeId = $this->referenceToId($reference);
        $attributeGroupId = $this->referenceToId($properties['attribute_group']);
        $this->editAttributeUsingCommand($attributeId, $attributeGroupId, $properties['value'], $properties['color']);
    }

    /**
     * @Then attribute :reference should have the following properties:
     *
     * @param string $reference
     * @param TableNode $tableNode
     */
    public function assertAttributeGroupProperties(string $reference, TableNode $tableNode): void
    {
        $attribute = $this->getAttribute($reference);
        $data = $this->localizeByRows($tableNode);
        $attributeGroupId = $this->referenceToId($data['attribute_group']);
        Assert::assertEquals($data['value'], $attribute->getValue());
        Assert::assertEquals($data['color'], $attribute->getColor());
        Assert::assertEquals($attributeGroupId, $attribute->getAttributeGroupId()->getValue());
    }

    /**
     * @param int $attributeGroupId
     * @param array $localizedValues
     * @param string $color
     *
     * @return AttributeId
     *
     * @throws AttributeConstraintException
     */
    private function createAttributeUsingCommand(
        int $attributeGroupId,
        array $localizedValues,
        string $color
    ): AttributeId {
        $command = new AddAttributeCommand(
            $attributeGroupId,
            $localizedValues,
            $color,
            [$this->defaultShopId]
        );

        return $this->getCommandBus()->handle($command);
    }

    /**
     * @param int $attributeId
     * @param int $attributeGroupId
     * @param array $localizedValue
     * @param string $color
     *
     * @return void
     *
     * @throws AttributeGroupConstraintException
     * @throws AttributeConstraintException
     */
    private function editAttributeUsingCommand(
        int $attributeId,
        int $attributeGroupId,
        array $localizedValue,
        string $color
    ): void {
        $command = new EditAttributeCommand(
            $attributeId,
            $attributeGroupId,
            $localizedValue,
            $color,
            [$this->defaultShopId]
        );

        $this->getCommandBus()->handle($command);
    }

    /**
     * @When I delete attribute :reference
     */
    public function deleteAttribute(string $reference): void
    {
        $attributeId = $this->referenceToId($reference);

        $this->getCommandBus()->handle(new DeleteAttributeCommand($attributeId));
    }

    /**
     * @Then attribute :reference should be deleted
     */
    public function assertAttributeIsDeleted(string $reference): void
    {
        $attributeId = $this->referenceToId($reference);

        try {
            $this->getQueryBus()->handle(new GetAttributeForEditing($attributeId));

            throw new NoExceptionAlthoughExpectedException(sprintf('Attribute %s exists, but it was expected to be deleted', $reference));
        } catch (AttributeNotFoundException $e) {
            $this->getSharedStorage()->clear($reference);
        }
    }

    /**
     * @param string $reference
     *
     * @return EditableAttribute
     */
    private function getAttribute(string $reference): EditableAttribute
    {
        $id = $this->referenceToId($reference);

        return $this->getCommandBus()->handle(new GetAttributeForEditing($id));
    }
}
