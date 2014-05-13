<?php

namespace Pim\Bundle\ImportExportBundle\Tests\Unit\Normalizer;

use Pim\Bundle\ImportExportBundle\Normalizer\FlatGroupNormalizer;
use Pim\Bundle\CatalogBundle\Entity\Group;

/**
 * Test related class
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FlatGroupNormalizerTest extends GroupNormalizerTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->normalizer = new FlatGroupNormalizer();
    }

    /**
     * Data provider for testing supportsNormalization method
     * @return array
     */
    public static function getSupportNormalizationData()
    {
        return array(
            array('Pim\Bundle\CatalogBundle\Entity\Group', 'csv',  true),
            array('Pim\Bundle\CatalogBundle\Entity\Group', 'json', false),
            array('stdClass',                              'csv',  false),
            array('stdClass',                              'json', false),
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
                    'code'        => 'my_variant_group',
                    'type'        => 'VARIANT',
                    'label-en_US' => 'My variant group',
                    'label-fr_FR' => 'Mon groupe variant',
                    'attributes'  => 'color,size'
                ),
                array(
                    'code'        => 'my_group',
                    'type'        => 'RELATED',
                    'label-en_US' => 'My group',
                    'label-fr_FR' => 'Mon groupe',
                    'attributes'  => ''
                )
            ),
        );
    }

    /**
     * Test normalize method
     * @param array $expected
     *
     * @dataProvider getNormalizeData
     */
    public function testNormalize(array $expected)
    {
        $group = $this->createGroup($expected);
        $result = $this->normalizer->normalize($group, 'csv');
        $this->assertEquals($expected, $result);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function getLabels($data)
    {
        return array(
            'en_US' => $data['label-en_US'],
            'fr_FR' => $data['label-fr_FR']
        );
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function getAttributes($data)
    {
        $data['attributes']= explode(',', $data['attributes']);

        return parent::getAttributes($data);
    }
}
