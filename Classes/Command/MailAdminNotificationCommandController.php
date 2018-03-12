<?php

namespace Frappant\FrpFormAnswers\Command;

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;

class MailAdminNotificationCommandController extends CommandController
{
    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     * @inject
     */
    protected $formEntryRepository = null;

    /**
     * @param \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository $formEntryRepository
     */
    public function setFormEntryRepository($formEntryRepository)
    {
        $this->formEntryRepository = $formEntryRepository;
    }

    /**
     * @param $mails
     * @return string
     */
    public function generateMailBody($mails)
    {
        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setFormat('html');
        $templateRootPath = GeneralUtility::getFileAbsFileName(
            'EXT:frp_form_answers/Resources/Private/CommandTask/Templates'
        );
        $partialRootPaths = GeneralUtility::getFileAbsFileName(
            'EXT:frp_form_answers/Resources/Private/CommandTask/Partials'
        );
        $layoutRootPaths = GeneralUtility::getFileAbsFileName(
            'EXT:frp_form_answers/Resources/Private/CommandTask/Layouts'
        );
        $view->setTemplateRootPaths(array($templateRootPath));
        $view->setPartialRootPaths(array($partialRootPaths));
        $view->setLayoutRootPaths(array($layoutRootPaths));
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:frp_form_answers/Resources/Private/CommandTask/Templates/FormEntries/InMail.html'
            )
        );
        $view->assignMultiple(['mails' => $mails]);
        return $view->render();
    }

    /**
     * Email notification about sent forms.
     *
     * @param string $mailto Destination mails separated with ','.
     * @param string $formname Select form. Leave empty for all.
     * @param string $title
     * @throws Exception
     */
    public function mailAdminCommand($mailto, $formname = false, $title = false)
    {
        if (empty($mailto)) {
            throw new Exception('You need to provide at least one email adress.');
        }
        $search = GeneralUtility::makeInstance(\Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand::class);
        $search->setAllPids(true);
        if ($formname) {
            $search->setFormName($formname);
        }

        $frommail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        if (!empty($frommail)) {
            $from = $frommail;
        } else {
            throw new Exception("['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] is not set.");
        }
        $records = $this->formEntryRepository->findByDemand($search);

        if ($records->count()) {
            /** @var FormEntry $row */
            foreach ($records as $row) {
                $row->setExported(true);
                try {
                    $this->formEntryRepository->update($row);
                } catch (IllegalObjectTypeException $e) {
                    return;
                } catch (UnknownObjectException $e) {
                    return;
                }
            }
            $body = $this->generateMailBody($records);
            $date = date("d/m/y");
            if (!empty($title)) {
                $subject = $title;
            } else {
                $subject = "Scheduler mails update " . $date;
            }
            $trim = GeneralUtility::trimExplode(',', $mailto, 1);
            foreach ($trim as $singlemail) {
                $mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
                $mail
                    ->setSubject($subject)
                    ->setFrom(array($from))
                    ->setTo(array($singlemail))
                    ->setBody($body, 'text/html')
                    ->send();
            }
        } else {
            $this->output("Nothing to send.");
        }
    }

}
