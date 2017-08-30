<?php
namespace Frappant\FrpFormAnswers\Tests\Unit\Domain\Model;

/**
 * Test case.
 *
 * @author !frappant <support@frappant.ch>
 */
class FormEntryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Frappant\FrpFormAnswers\Domain\Model\FormEntry
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \Frappant\FrpFormAnswers\Domain\Model\FormEntry();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function setAnswersGeneratesHash()
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

        $this->assertSame($hash1, $hash2);
        // self::assertAttributeEquals(
        //     'Conceived at T3CON10',
        //     'answers',
        //     $this->subject
        // );
    }

    /**
     * @test
     */
    public function getFieldHashReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getFieldHash()
        );
    }

    /**
     * @test
     */
    public function getFormReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getForm()
        );
    }

    /**
     * @test
     */
    public function setFormForStringSetsForm()
    {
        $this->subject->setForm('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'form',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getExportedReturnsInitialValueForBool()
    {
        self::assertSame(
            false,
            $this->subject->getExported()
        );
    }

    /**
     * @test
     */
    public function setExportedForBoolSetsExported()
    {
        $this->subject->setExported(true);

        self::assertAttributeEquals(
            true,
            'exported',
            $this->subject
        );
    }
}
