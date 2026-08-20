<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\Tests\Unit\EventListener;

use Frappant\FrpFormAnswers\Event\ManipulateFormValuesEvent;
use Frappant\FrpFormAnswers\EventListener\UploadPathEnrichmentEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * What the listener does with the values it is handed.
 */
class UploadPathEnrichmentEventListenerTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: mixed}>
     */
    public static function valuesThatCarryNoFileNameDataProvider(): array
    {
        return [
            'no file was uploaded' => [null],
            'value replaced by an object' => [new \stdClass()],
            'value replaced by a number' => [42],
        ];
    }

    /**
     * An upload element without a file used to be missing from the values
     * altogether. It is written now, so the listener has to cope with a value
     * it cannot prefix instead of running into a type error.
     */
    #[Test]
    #[DataProvider('valuesThatCarryNoFileNameDataProvider')]
    public function aValueThatIsNoFileNameIsLeftUntouched(mixed $value): void
    {
        $values = [
            'attachment' => [
                'value' => $value,
                'conf' => ['label' => 'Attachment', 'inputType' => 'FileUpload'],
            ],
        ];

        $event = $this->dispatch($values);

        self::assertSame($value, $event->getValues()['attachment']['value']);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function dispatch(array $values): ManipulateFormValuesEvent
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([
            'enableUploadPathEnrichment' => '1',
            'savePublicUploadPath' => '0',
        ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration);

        $element = $this->createMock(FormElementInterface::class);
        $element->method('getIdentifier')->willReturn('attachment');
        $element->method('getType')->willReturn('FileUpload');
        $element->method('getProperties')->willReturn(['saveToFileMount' => '1:/user_upload/']);

        $page = $this->createMock(Page::class);
        $page->method('getElementsRecursively')->willReturn([$element]);

        $formDefinition = $this->createMock(FormDefinition::class);
        $formDefinition->method('getPages')->willReturn([$page]);

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getFormDefinition')->willReturn($formDefinition);

        $event = new ManipulateFormValuesEvent($values, $formRuntime);

        (new UploadPathEnrichmentEventListener($this->createMock(ResourceFactory::class)))($event);

        return $event;
    }
}
