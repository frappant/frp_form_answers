<?php
namespace Frappant\FrpFormAnswers\Utility;

use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class FormAnswersUtility
{

    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $formEntryRepository = null;

    /**
     * pageRepository
     *
     * @var \TYPO3\CMS\Core\Domain\Repository\PageRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $pageRepository = null;

    public function __construct(PageRepository $pageRepository, FormEntryRepository $formEntryRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->formEntryRepository = $formEntryRepository;
    }

    /**
     * [prepareFormAnswersArray description]
     * @return [type]       [description]
     */
    public function prepareFormAnswersArray()
    {

        $act_pid = $_GET['id'];
        $pageIds = array();

        // Get a List from FormEntries in subpages
        $startPointPids = ($act_pid > 0 ? [$act_pid] : $GLOBALS['BE_USER']->returnWebmounts());
        // Get all Pids with a formEntry list
        foreach ($startPointPids as $pageId) {
            foreach ($this->formEntryRepository->findAllInPidAndRootline($pageId) as $formEntry) {
                if($pageIds[$formEntry->getPid()][$formEntry->getForm()]['tot']) {
                    $pageIds[$formEntry->getPid()][$formEntry->getForm()]['tot'] += 1;
                } else {
                    $pageIds[$formEntry->getPid()][$formEntry->getForm()]['tot'] = 1;
                }

                if (!$formEntry->isExported()) {
                    if($pageIds[$formEntry->getPid()][$formEntry->getForm()]['new']) {
                        $pageIds[$formEntry->getPid()][$formEntry->getForm()]['new'] += 1;
                    } else {
                        $pageIds[$formEntry->getPid()][$formEntry->getForm()]['new'] = 1;
                    }

                }
            }
        }

        $id = $_GET['id'];
        unset($pageIds[(int)$id]);

        return $pageIds;
    }
    /**
     * Get all names of the saved Forms
     * @return array Formnames
     */
    public function getAllFormNames()
    {
        $allFormAnswers = $this->formEntryRepository->findAll();
        $formNames = [];
        // Get FormNames from this page. We will separate them in the list View
        foreach ($allFormAnswers as $answer) {
            $formNames[$answer->getForm()] = $answer->getForm();
        }
        return array_keys($formNames);
    }

    /**
     * Get all hashes of the saved Forms
     * @return array Formhashes
     */
    public function getAllFormHashes()
    {
        $allFormAnswers = $this->formEntryRepository->findAll();

        $formHashes = [];
        // Get FormNames from this page. We will separate them in the list View
        foreach ($allFormAnswers as $answer) {
            $formHashes[$answer->getFieldHash()] = $answer->getFieldHash();
        }
        return array_keys($formHashes);
    }
}
