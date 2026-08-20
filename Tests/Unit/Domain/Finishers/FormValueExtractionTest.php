<?php

namespace Frappant\FrpFormAnswers\Tests\Unit\Domain\Finishers;

use Frappant\FrpFormAnswers\Domain\Finishers\SaveFormToDatabaseFinisher;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * How submitted values are read out of the form runtime.
 */
class FormValueExtractionTest extends UnitTestCase
{
    private SaveFormToDatabaseFinisher $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new SaveFormToDatabaseFinisher(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FormEntryRepository::class),
            new FormEntry(),
            $this->createMock(PersistenceManager::class),
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: mixed}>
     */
    public static function submittedValueDataProvider(): array
    {
        return [
            'plain identifier' => [
                ['name' => 'Ada'],
                'name',
                'Ada',
            ],
            'identifier with a dot is read as a path' => [
                ['container' => [0 => ['street' => 'Bahnhofstrasse']]],
                'container.0.street',
                'Bahnhofstrasse',
            ],
            'missing identifier yields null' => [
                ['name' => 'Ada'],
                'missing',
                null,
            ],
            'missing path segment yields null' => [
                ['container' => [0 => ['street' => 'Bahnhofstrasse']]],
                'container.1.street',
                null,
            ],
            'value that is itself an array is kept' => [
                ['choices' => ['a', 'b']],
                'choices',
                ['a', 'b'],
            ],
            'empty identifier yields null instead of throwing' => [
                ['name' => 'Ada'],
                '',
                null,
            ],
            'identifier stored flat wins over path traversal' => [
                ['container.0.street' => 'flat value', 'container' => [0 => ['street' => 'path value']]],
                'container.0.street',
                'flat value',
            ],
            'identifier containing a quote is not parsed as csv' => [
                ['say "hi"' => 'quoted'],
                'say "hi"',
                'quoted',
            ],
            'null value is preserved' => [
                ['name' => null],
                'name',
                null,
            ],
        ];
    }

    #[Test]
    #[DataProvider('submittedValueDataProvider')]
    public function submittedValuesAreReadByPropertyPath(array $submitted, string $identifier, mixed $expected): void
    {
        self::assertSame($expected, $this->callGetSubmittedValue($submitted, $identifier));
    }

    #[Test]
    public function singleUploadIsReducedToItsFileName(): void
    {
        self::assertSame('letter.pdf', $this->callGetUploadedFileNames($this->createFileReference('letter.pdf')));
    }

    #[Test]
    public function multipleUploadsAreReducedToAListOfFileNames(): void
    {
        $storage = new ObjectStorage();
        $storage->attach($this->createFileReference('first.pdf'));
        $storage->attach($this->createFileReference('second.pdf'));

        self::assertSame(['first.pdf', 'second.pdf'], $this->callGetUploadedFileNames($storage));
    }

    #[Test]
    public function emptyUploadYieldsNull(): void
    {
        self::assertNull($this->callGetUploadedFileNames(null));
    }

    #[Test]
    public function emptyMultipleUploadYieldsAnEmptyList(): void
    {
        self::assertSame([], $this->callGetUploadedFileNames(new ObjectStorage()));
    }

    #[Test]
    public function everyElementOfEveryPageIsCollected(): void
    {
        $storage = new ObjectStorage();
        $storage->attach($this->createFileReference('first.pdf'));
        $storage->attach($this->createFileReference('second.pdf'));

        $elements = [
            $this->createElement('fullname', 'Text', 'Full name'),
            $this->createElement('container.0.street', 'Text', 'Street'),
            $this->createElement('attachment', 'FileUpload', 'Attachment'),
            $this->createElement('trap', 'Honeypot', 'Honeypot'),
        ];

        $submitted = [
            'fullname' => 'Ada',
            'container' => [0 => ['street' => 'Bahnhofstrasse']],
            'attachment' => $storage,
            'trap' => 'should not be stored',
        ];

        $values = $this->callGetFormValues($elements, $submitted);

        self::assertSame(['fullname', 'container.0.street', 'attachment'], array_keys($values));
        self::assertSame('Ada', $values['fullname']['value']);
        self::assertSame('Bahnhofstrasse', $values['container.0.street']['value']);
        self::assertSame(['first.pdf', 'second.pdf'], $values['attachment']['value']);
        self::assertSame('Attachment', $values['attachment']['conf']['label']);
        self::assertSame('FileUpload', $values['attachment']['conf']['inputType']);
    }

    #[Test]
    public function elementsWithoutASubmittedValueKeepTheValueKey(): void
    {
        $values = $this->callGetFormValues(
            [$this->createElement('optional', 'Text', 'Optional')],
            [],
        );

        self::assertArrayHasKey('value', $values['optional']);
        self::assertNull($values['optional']['value']);
    }

    private function createElement(string $identifier, string $type, string $label): FormElementInterface
    {
        $element = $this->createMock(FormElementInterface::class);
        $element->method('getIdentifier')->willReturn($identifier);
        $element->method('getType')->willReturn($type);
        $element->method('getLabel')->willReturn($label);

        return $element;
    }

    /**
     * @param list<FormElementInterface> $elements
     * @param array<string, mixed> $submitted
     * @return array<string, array{value: mixed, conf: array{label: mixed, inputType: string}}>
     */
    private function callGetFormValues(array $elements, array $submitted): array
    {
        $page = $this->createMock(Page::class);
        $page->method('getElementsRecursively')->willReturn($elements);

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getPages')->willReturn([$page]);

        $finisherContext = $this->createMock(FinisherContext::class);
        $finisherContext->method('getFormValues')->willReturn($submitted);
        $finisherContext->method('getFormRuntime')->willReturn($formRuntime);

        $contextProperty = new \ReflectionProperty(AbstractFinisher::class, 'finisherContext');
        $contextProperty->setValue($this->subject, $finisherContext);

        $method = new \ReflectionMethod(SaveFormToDatabaseFinisher::class, 'getFormValues');

        return $method->invoke($this->subject);
    }

    private function createFileReference(string $name): FileReference
    {
        $originalResource = $this->createMock(CoreFileReference::class);
        $originalResource->method('getName')->willReturn($name);

        $fileReference = $this->createMock(FileReference::class);
        $fileReference->method('getOriginalResource')->willReturn($originalResource);

        return $fileReference;
    }

    /**
     * @param array<string, mixed> $submitted
     */
    private function callGetSubmittedValue(array $submitted, string $identifier): mixed
    {
        $method = new \ReflectionMethod(SaveFormToDatabaseFinisher::class, 'getSubmittedValue');

        return $method->invoke($this->subject, $submitted, $identifier);
    }

    private function callGetUploadedFileNames(mixed $upload): string|array|null
    {
        $method = new \ReflectionMethod(SaveFormToDatabaseFinisher::class, 'getUploadedFileNames');

        return $method->invoke($this->subject, $upload);
    }
}
