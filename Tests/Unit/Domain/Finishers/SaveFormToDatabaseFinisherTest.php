<?php
namespace Frappant\FrpFormAnswers\Tests\Unit\Domain\Finishers;

use Frappant\FrpFormAnswers\Domain\Finishers\SaveFormToDatabaseFinisher;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Object\Container\ClassInfoCache;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;

/**
 * Test case.
 *
 * @author !frappant <support@frappant.ch>
 */
class SaveFormToDatabaseFinisherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Frappant\FrpFormAnswers\Domain\Finishers\SaveFormToDatabaseFinisher
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMock(SaveFormToDatabaseFinisher::class, ['getFormValues'], [], '', true, true, true, false, false);

        // Prevent ObjectManager from accessing database cache.
        $classInfoCacheMock = $this->getMock(ClassInfoCache::class);
        GeneralUtility::addInstance(ClassInfoCache::class, $classInfoCacheMock);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function canRegisterSignalSlotDispatcher()
    {
        $finisherContextFixture = $this->getMock(FinisherContext::class, [], [], '', false);
        $valuesFixture = [
            'name' => [
                'value' => '!frappant',
                'conf' => [
                    'label' => 'Name',
                    'inputType' => 'input',
                ],
            ],
        ];

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->inject($this->subject, 'objectManager', $objectManager);

        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = $objectManager->get(Dispatcher::class);
        $this->inject($this->subject, 'signalSlotDispatcher', $signalSlotDispatcher);

        $formEntryRepositoryMock = $this->getMock(FormEntryRepository::class, ['add', 'getLastFormAnswerByIdentifyer'], [], '', false);
        $this->inject($this->subject, 'formEntryRepository', $formEntryRepositoryMock);

        $this->subject
            ->expects($this->once())
            ->method('getFormValues')
            ->will($this->returnValue($valuesFixture));


        $formEntryRepositoryMock
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function ($formEntry) {
                return key_exists('test', $formEntry->getAnswers());
            }));


        $formEntryRepositoryMock
            ->expects($this->once())
            ->method('getLastFormAnswerByIdentifyer')
            ->will($this->returnValue(null));


        $signalSlotDispatcher->connect(
            SaveFormToDatabaseFinisher::class,
            'preInsertSignal',
            function (&$values) {
                $values['test'] = 'Test';
            }
        );

        $this->subject->execute($finisherContextFixture);
    }
}
