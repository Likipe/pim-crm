<?php

namespace Pim\Bundle\ImportExportBundle\Tests\Unit\Normalizer;

use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Pim\Bundle\ImportExportBundle\Normalizer\JobInstanceNormalizer;

/**
 * Test related class
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobInstanceNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobInstanceNormalizer
     */
    protected $normalizer;

    /**
     * @var string
     */
    protected $format;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->normalizer = new JobInstanceNormalizer();
        $this->format = 'json';
    }

    /**
     * Data provider for testing supportsNormalization method
     * @return array
     */
    public static function getSupportNormalizationData()
    {
        return array(
            array('Oro\Bundle\BatchBundle\Entity\JobInstance', 'json', true),
            array('Oro\Bundle\BatchBundle\Entity\JobInstance', 'csv', false),
            array('stdClass',                                  'json', false),
            array('stdClass',                                  'csv', false),
        );
    }

    /**
     * Test supportsNormalization method
     * @param mixed   $class
     * @param string  $format
     * @param boolean $isSupported
     *
     * @dataProvider getSupportNormalizationData
     */
    public function testSupportNormalization($class, $format, $isSupported)
    {
        $data = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertSame($isSupported, $this->normalizer->supportsNormalization($data, $format));
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
                    'code'           => 'my_import',
                    'type'           => 'IMPORT',
                    'label'          => 'My import',
                    'connector'      => 'Akeneo',
                    'configuration'  => array()
                ),
            ),
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
        $entity = $this->createJobInstance($data);

        $this->assertEquals(
            $data,
            $this->normalizer->normalize($entity, $this->format)
        );
    }

    /**
     * Create a job instance
     * @param array $data
     *
     * @return JobInstance
     */
    protected function createJobInstance(array $data)
    {
        $job = new JobInstance($data['connector'], $data['type'], 'alias');
        $job->setCode($data['code']);
        $job->setLabel($data['label']);

        return $job;
    }
}
