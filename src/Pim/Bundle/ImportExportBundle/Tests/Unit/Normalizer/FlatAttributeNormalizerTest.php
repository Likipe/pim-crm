<?php

namespace Pim\Bundle\ImportExportBundle\Tests\Unit\Normalizer;

use Pim\Bundle\ImportExportBundle\Normalizer\FlatAttributeNormalizer;
use Pim\Bundle\CatalogBundle\Entity\ProductAttribute;
use Pim\Bundle\CatalogBundle\Entity\AttributeOption;
use Pim\Bundle\CatalogBundle\Entity\AttributeOptionValue;
use Pim\Bundle\CatalogBundle\Entity\Locale;

/**
 * Test class for AttributeNormalizer
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FlatAttributeNormalizerTest extends AttributeNormalizerTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->normalizer = new FlatAttributeNormalizer();
        $this->format = 'csv';
    }

    /**
     * Data provider for testing supportsNormalization method
     * @return array
     */
    public static function getSupportNormalizationData()
    {
        return array(
            array('Pim\Bundle\CatalogBundle\Entity\ProductAttribute', 'csv',  true),
            array('Pim\Bundle\CatalogBundle\Entity\ProductAttribute', 'json', false),
            array('stdClass',                                         'csv',  false),
            array('stdClass',                                         'json', false),
        );
    }

    /**
     * Data provider for testing normalize method
     * @return array
     */
    public static function getNormalizeData()
    {
        return array(
            array(
                array(
                    'type'                   => 'multiselect',
                    'code'                   => 'color',
                    'label'                  => 'default:Color,en:Color,fr:Couleur',
                    'group'                  => 'general',
                    'sort_order'             => '5',
                    'required'               => '0',
                    'unique'                 => '0',
                    'default_options'        => 'en:Red,fr:Rouge',
                    'searchable'             => '1',
                    'localizable'            => '1',
                    'available_locales'      => 'All',
                    'scope'                  => 'Global',
                    'options'                => 'Code:green,en:Green,fr:Vert|Code:red,en:Red,fr:Rouge',
                    'useable_as_grid_column' => '1',
                    'useable_as_grid_filter' => '0',
                )
            ),
            array(
                array(
                    'type'                   => 'text',
                    'code'                   => 'description',
                    'label'                  => 'default:Description,en:Description,fr:Description',
                    'group'                  => 'info',
                    'sort_order'             => '1',
                    'required'               => '1',
                    'unique'                 => '0',
                    'default_value'          => 'No description',
                    'default_options'        => '',
                    'searchable'             => '1',
                    'localizable'            => '1',
                    'available_locales'      => 'en,fr',
                    'scope'                  => 'Channel',
                    'options'                => '',
                    'useable_as_grid_column' => '1',
                    'useable_as_grid_filter' => '1',
                    'max_characters'         => '200',
                    'validation_rule'        => 'regexp',
                    'validation_regexp'      => '^[a-zA-Z0-9 ]*$',
                    'wysiwyg_enabled'        => '1',
                )
            )
        );
    }

    /**
     * Test normalize method
     * @param array $data
     *
     * @dataProvider getNormalizeData
     */
    public function testNormalize(array $data)
    {
        $attribute = $this->createAttribute($data);

        $expectedResult = $data;
        foreach ($this->getOptionalProperties() as $property) {
            if (!array_key_exists($property, $expectedResult)) {
                $expectedResult[$property] = '';
            }
        }

        $this->assertEquals(
            $expectedResult,
            $this->normalizer->normalize($attribute, 'csv')
        );
    }

    /**
     * @param ProductAttribute $attribute
     * @param array            $data
     */
    protected function addLabels($attribute, $data)
    {
        $labels = explode(',', $data['label']);
        foreach ($labels as $label) {
            $label  = explode(':', $label);
            $locale = reset($label);
            $label  = end($label);
            $translation = $attribute->getTranslation($locale);
            $translation->setLabel($label);
        }
    }

    /**
     * @param ProductAttribute $attribute
     * @param array            $data
     */
    protected function addAvailableLocales($attribute, $data)
    {
        if (strtolower($data['available_locales']) !== 'all') {
            $locales = explode(',', $data['available_locales']);
            foreach ($locales as $localeCode) {
                $locale = new Locale();
                $locale->setCode($localeCode);
                $attribute->addAvailableLocale($locale);
            }
        }
    }

    /**
     * Create attribute options
     *
     * @param ProductAttribute $attribute
     * @param array            $data
     */
    protected function addOptions(ProductAttribute $attribute, $data)
    {
        $options = array_filter(explode('|', $data['options']));
        foreach ($options as $option) {
            $attributeOption = new AttributeOption();
            $translations = explode(',', $option);
            foreach ($translations as $translation) {
                $translation = explode(':', $translation);
                $locale      = reset($translation);
                $value       = end($translation);
                if ($locale == 'Code') {
                    $attributeOption->setCode($value);
                } else {
                    $optionValue = new AttributeOptionValue();
                    $optionValue->setLocale($locale);
                    $optionValue->setValue($value);
                    $attributeOption->addOptionValue($optionValue);
                }
            }
            $attribute->addOption($attributeOption);
        }
    }

    /**
     * Add attribute default options
     *
     * @param ProductAttribute $attribute
     * @param array            $data
     */
    protected function addDefaultOptions(ProductAttribute $attribute, $data)
    {
        $defaultOptions = array_filter(explode('|', $data['default_options']));
        foreach ($defaultOptions as $defaultOption) {
            $translations = explode(',', $defaultOption);
            foreach ($translations as $translation) {
                $translation = explode(':', $translation);
                $locale       = reset($translation);
                $value        = end($translation);
                $options = $attribute->getOptions();
                foreach ($options as $option) {
                    $optionValues = $option->getOptionValues();
                    foreach ($optionValues as $optionValue) {
                        if ($optionValue->getLocale() == $locale && $optionValue->getValue() == $value) {
                            $option->setDefault(true);
                            break;
                        }
                    }
                }
            }
        }
    }
}