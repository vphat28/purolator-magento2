<?php

namespace Purolator\Shipping\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    const PUROLATOR_GROUP_NAME = 'Purolator Attributes';
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $setup->startSetup();

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(\Magento\Catalog\Model\Product::ENTITY);
        $eavSetup->addAttributeGroup(
            \Magento\Catalog\Model\Product::ENTITY,
            $attributeSetId,
            self::PUROLATOR_GROUP_NAME
        );

        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'harmonizedcode_attribute')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'harmonizedcode_attribute', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'text',
                    'type' => 'text',
                    'label' => 'Harmonized Code',
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }

        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'nafta_attribute')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'nafta_attribute', [
                    'group' => 'Purolator Attributes',
                    'input' => 'select',
                    'type' => 'text',
                    'label' => 'NAFTA Document Indicator',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }
        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'textileindicator_attribute')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'textileindicator_attribute', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'select',
                    'type' => 'text',
                    'label' => 'Textile Indicator',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }
        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'textileman_attribute')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'textileman_attribute', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'text',
                    'type' => 'text',
                    'label' => 'Textile Manufacturer',
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }
        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'fcc_attribute')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'fcc_attribute', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'select',
                    'type' => 'text',
                    'label' => 'FCC Document Indicator',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }
        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'sip_attribute')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'sip_attribute', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'select',
                    'type' => 'text',
                    'label' => 'Sender Is Producer Indicator',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }

        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'item_width')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'item_width', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'text',
                    'type' => 'text',
                    'label' => 'Item Width (cm)',
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }
        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'item_height')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'item_height', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'text',
                    'type' => 'text',
                    'label' => 'Item Height (cm)',
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }

        if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'item_length')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY, 'item_length', [
                    'group' => self::PUROLATOR_GROUP_NAME,
                    'input' => 'text',
                    'type' => 'text',
                    'label' => 'Item Length (cm)',
                    'backend' => '',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'visible_in_advanced_search' => false,
                    'is_html_allowed_on_front' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                ]
            );
        }

        $setup->endSetup();
    }
}
