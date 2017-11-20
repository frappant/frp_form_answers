<?php
namespace Frappant\FrpFormAnswers\Utility;

class FormAnswersUtility
{

    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     * @inject
     */
    protected $formEntryRepository = null;

    /**
     * pageRepository
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     * @inject
     */
    protected $pageRepository = null;

    /**
     * [prepareFormAnswersArray description]
     * @return [type]       [description]
     */
    public function prepareFormAnswersArray()
    {
        $act_pid = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
        $pageIds = array();

        // Get a List from FormEntries in subpages
        $pageId = ($act_pid > 0 ? $act_pid : $this->pageRepository->getFirstWebpage($act_pid));

        // Get all Pids with a formEntry list
        foreach ($this->formEntryRepository->findAllInPidAndRootline($pageId) as $formEntry) {
            $pageIds[$formEntry->getPid()]['tot'] += 1;

            if (!$formEntry->isExported()) {
                $pageIds[$formEntry->getPid()]['new'] += 1;//$formEntry->getPid();
            }
        }
        unset($pageIds[(int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')]);

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
