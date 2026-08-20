<?php

namespace Frappant\FrpFormAnswers\Tests\Unit\Domain\Finishers;

use Frappant\FrpFormAnswers\Domain\Finishers\SaveFormToDatabaseFinisher;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Frappant\FrpFormAnswers\Event\ManipulateFormValuesEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 *
 * @author !frappant <support@frappant.ch>
 */
class SaveFormToDatabaseFinisherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function eventDispatcherCanManipulateFormValuesBeforeInsert(): void
    {
        $valuesFixture = [
            'name' => [
                'value' => '!frappant',
                'conf' => [
                    'label' => 'Name',
                    'inputType' => 'input',
                ],
            ],
        ];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ManipulateFormValuesEvent::class))
            ->willReturnCallback(static function (ManipulateFormValuesEvent $event): ManipulateFormValuesEvent {
                $event->addValue([
                    'test' => [
                        'value' => 'Test',
                        'conf' => [
                            'label' => 'Test',
                            'inputType' => 'Text',
                        ],
                    ],
                ]);

                return $event;
            });

        $formEntry = new FormEntry();
        $formEntryRepository = $this->createMock(FormEntryRepository::class);
        $formEntryRepository->expects(self::once())
            ->method('getLastFormAnswerByIdentifyer')
            ->willReturn(null);
        $formEntryRepository->expects(self::once())
            ->method('add')
            ->with(self::callback(static function (FormEntry $entry): bool {
                return array_key_exists('test', $entry->getAnswers());
            }));

        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager->expects(self::once())->method('persistAll');

        /** @var SaveFormToDatabaseFinisher&MockObject $subject */
        $subject = $this->getMockBuilder(SaveFormToDatabaseFinisher::class)
            ->setConstructorArgs([$eventDispatcher, $formEntryRepository, $formEntry, $persistenceManager])
            ->onlyMethods(['getFormValues'])
            ->getMock();
        $subject->expects(self::once())
            ->method('getFormValues')
            ->willReturn($valuesFixture);

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getIdentifier')->willReturn('test-form');
        $request = $this->createMock(RequestInterface::class);
        $request->method('getAttributes')->willReturn(['routing' => ['pageId' => 1]]);
        $formRuntime->method('getRequest')->willReturn($request);

        $finisherContext = $this->createMock(FinisherContext::class);
        $finisherContext->method('getFormRuntime')->willReturn($formRuntime);

        $subject->execute($finisherContext);
    }
}
