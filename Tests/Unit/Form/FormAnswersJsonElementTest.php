<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\Tests\Unit\Form;

use Frappant\FrpFormAnswers\Form\FormAnswersJsonElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * How a stored entry is rendered in the record view.
 */
class FormAnswersJsonElementTest extends UnitTestCase
{
    #[Test]
    public function everyAnsweredFieldIsListedWithItsLabel(): void
    {
        $html = $this->render([
            'fullname' => ['value' => 'Ada', 'conf' => ['label' => 'Full name', 'inputType' => 'Text']],
            'attachment' => ['value' => ['a.pdf', 'b.pdf'], 'conf' => ['label' => 'Attachment', 'inputType' => 'FileUpload']],
        ]);

        self::assertSame('<ul><li>Full name - Ada</li><li>Attachment - a.pdf,b.pdf</li></ul>', $html);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function unusualEntryDataProvider(): array
    {
        return [
            'entry saved before the value key was always written' => [
                ['attachment' => ['conf' => ['label' => 'Attachment', 'inputType' => 'FileUpload']]],
                '<ul><li>Attachment - </li></ul>',
            ],
            'field the visitor did not answer' => [
                ['street' => ['value' => null, 'conf' => ['label' => 'Street', 'inputType' => 'Text']]],
                '<ul><li>Street - </li></ul>',
            ],
            'upload without a file' => [
                ['attachment' => ['value' => [], 'conf' => ['label' => 'Attachment', 'inputType' => 'FileUpload']]],
                '<ul><li>Attachment - </li></ul>',
            ],
            'field without a label falls back to its identifier' => [
                ['street' => ['value' => 'Bahnhofstrasse', 'conf' => ['inputType' => 'Text']]],
                '<ul><li>street - Bahnhofstrasse</li></ul>',
            ],
            'label that is not a string falls back to the identifier' => [
                ['street' => ['value' => 'Bahnhofstrasse', 'conf' => ['label' => [], 'inputType' => 'Text']]],
                '<ul><li>street - Bahnhofstrasse</li></ul>',
            ],
            'value nested one level deeper' => [
                ['street' => ['value' => ['a', ['b']], 'conf' => ['label' => 'Street', 'inputType' => 'Text']]],
                '<ul><li>Street - a,b</li></ul>',
            ],
            'answers that are no json object at all' => [
                [],
                '<ul></ul>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('unusualEntryDataProvider')]
    public function anEntryThatCarriesNoUsableTextStillRenders(array $answers, string $expected): void
    {
        self::assertSame($expected, $this->render($answers));
    }

    #[Test]
    public function labelAndValueAreEscaped(): void
    {
        $html = $this->render([
            'x' => ['value' => '<script>', 'conf' => ['label' => 'A & B', 'inputType' => 'Text']],
        ]);

        self::assertSame('<ul><li>A &amp; B - &lt;script&gt;</li></ul>', $html);
    }

    /**
     * @param array<string, mixed> $answers
     */
    private function render(array $answers): string
    {
        $subject = new FormAnswersJsonElement();
        $subject->setData(['databaseRow' => ['answers' => json_encode($answers)]]);

        return $subject->render()['html'];
    }
}
