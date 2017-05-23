<?php
namespace Frappant\FrpFormAnswers\Tests\Unit\Controller;

/**
 * Test case.
 *
 * @author !frappant <support@frappant.ch>
 */
class FormEntryControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Frappant\FrpFormAnswers\Controller\FormEntryController
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(\Frappant\FrpFormAnswers\Controller\FormEntryController::class)
            ->setMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function listActionFetchesAllFormEntriesFromRepositoryAndAssignsThemToView()
    {

        $allFormEntries = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formEntryRepository = $this->getMockBuilder(\Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository::class)
            ->setMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $formEntryRepository->expects(self::once())->method('findAll')->will(self::returnValue($allFormEntries));
        $this->inject($this->subject, 'formEntryRepository', $formEntryRepository);

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('formEntries', $allFormEntries);
        $this->inject($this->subject, 'view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenFormEntryToView()
    {
        $formEntry = new \Frappant\FrpFormAnswers\Domain\Model\FormEntry();

        $view = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class)->getMock();
        $this->inject($this->subject, 'view', $view);
        $view->expects(self::once())->method('assign')->with('formEntry', $formEntry);

        $this->subject->showAction($formEntry);
    }
}
