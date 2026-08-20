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
