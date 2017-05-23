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
    public function getAnswersReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getAnswers()
        );

    }

    /**
     * @test
     */
    public function setAnswersForStringSetsAnswers()
    {
        $this->subject->setAnswers('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'answers',
            $this->subject
        );

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
    public function setFieldHashForStringSetsFieldHash()
    {
        $this->subject->setFieldHash('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'fieldHash',
            $this->subject
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
