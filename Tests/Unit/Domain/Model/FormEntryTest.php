<?php

namespace Frappant\FrpFormAnswers\Tests\Unit\Domain\Model;

use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 *
 * @author !frappant <support@frappant.ch>
 */
class FormEntryTest extends UnitTestCase
{
    protected FormEntry $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new FormEntry();
    }

    /**
     * @test
     */
    public function setAnswersGeneratesHash(): void
    {
        $answersFixture1 = [
            'field1' => ['value' => 'Answer1', 'conf' => []],
            'field2' => ['value' => 'Answer2', 'conf' => []],
            'field3' => ['value' => 'Answer3', 'conf' => []],
        ];
        $this->subject->setAnswers($answersFixture1);
        $hash1 = $this->subject->getFieldHash();

        $answersFixture2 = [
            'field3' => ['value' => 'Answer3', 'conf' => []],
            'field1' => ['value' => 'Answer1', 'conf' => []],
            'field2' => ['value' => 'Answer2', 'conf' => []],
        ];
        $this->subject->setAnswers($answersFixture2);
        $hash2 = $this->subject->getFieldHash();

        self::assertSame($hash1, $hash2);
    }

    /**
     * @test
     */
    public function getFieldHashReturnsInitialValueForString(): void
    {
        self::assertSame('', $this->subject->getFieldHash());
    }

    /**
     * @test
     */
    public function getFormReturnsInitialValueForString(): void
    {
        self::assertSame('', $this->subject->getForm());
    }

    /**
     * @test
     */
    public function setFormForStringSetsForm(): void
    {
        $this->subject->setForm('Conceived at T3CON10');

        self::assertSame('Conceived at T3CON10', $this->subject->getForm());
    }

    /**
     * @test
     */
    public function getExportedReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getExported());
    }

    /**
     * @test
     */
    public function setExportedForBoolSetsExported(): void
    {
        $this->subject->setExported(true);

        self::assertTrue($this->subject->getExported());
    }
}
