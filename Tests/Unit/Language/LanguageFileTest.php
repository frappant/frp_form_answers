<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\Tests\Unit\Language;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The translated files have to stay in step with the ones they translate:
 * a label added on one side only is invisible until someone switches the
 * backend language.
 */
class LanguageFileTest extends UnitTestCase
{
    private const LANGUAGE_PATH = __DIR__ . '/../../../Resources/Private/Language/';

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function translatedFileDataProvider(): array
    {
        $pairs = [];

        foreach (glob(self::LANGUAGE_PATH . '*.xlf') ?: [] as $path) {
            $name = basename($path);

            // The translations are the ones carrying a language prefix
            if (!preg_match('/^([a-z]{2}(?:_[A-Z]{2})?)\.(.+\.xlf)$/', $name, $matches)) {
                continue;
            }

            $pairs[$name] = [$matches[2], $name];
        }

        return $pairs;
    }

    #[Test]
    #[DataProvider('translatedFileDataProvider')]
    public function aTranslationCarriesTheSameLabelsAsTheFileItTranslates(string $source, string $translation): void
    {
        $sourceUnits = $this->readUnits($source);
        $translatedUnits = $this->readUnits($translation);

        self::assertSame(
            array_keys($sourceUnits),
            array_keys($translatedUnits),
            sprintf('%s and %s do not carry the same labels in the same order.', $source, $translation),
        );

        foreach ($sourceUnits as $id => $unit) {
            self::assertSame($unit['source'], $translatedUnits[$id]['source'], sprintf('The source of "%s" differs in %s.', $id, $translation));
            self::assertNotSame('', $translatedUnits[$id]['target'], sprintf('"%s" is not translated in %s.', $id, $translation));
            self::assertSame(
                substr_count($unit['source'], '%s'),
                substr_count($translatedUnits[$id]['target'], '%s'),
                sprintf('"%s" in %s does not take the same arguments as its source.', $id, $translation),
            );
        }
    }

    #[Test]
    #[DataProvider('translatedFileDataProvider')]
    public function noLabelIsDeclaredTwice(string $source, string $translation): void
    {
        foreach ([$source, $translation] as $file) {
            $ids = array_map(
                static fn(\SimpleXMLElement $unit): string => (string)$unit['id'],
                iterator_to_array($this->body($file)->{'trans-unit'}),
            );

            self::assertSame(array_unique($ids), $ids, sprintf('%s declares a label twice.', $file));
        }
    }

    /**
     * @return array<string, array{source: string, target: string}>
     */
    private function readUnits(string $file): array
    {
        $units = [];
        foreach ($this->body($file)->{'trans-unit'} as $unit) {
            $units[(string)$unit['id']] = [
                'source' => (string)$unit->source,
                'target' => (string)($unit->target ?? ''),
            ];
        }

        return $units;
    }

    private function body(string $file): \SimpleXMLElement
    {
        $xml = simplexml_load_file(self::LANGUAGE_PATH . $file);
        self::assertNotFalse($xml, sprintf('%s is not well formed.', $file));

        return $xml->file->body;
    }
}
