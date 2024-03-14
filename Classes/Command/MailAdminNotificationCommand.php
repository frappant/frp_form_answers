<?php

namespace Frappant\FrpFormAnswers\Command;

use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use Symfony\Component\Console\Command\Command;

class MailAdminNotificationCommand extends Command
{

    /**
     * FormEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     */
    protected $formEntryRepository;

    /**
     * Inject FormEntryRepository
     *
     * @param \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository $pageRepository
     */
    public function injectFormEntryRepository(FormEntryRepository $formEntryRepository)
    {
        $this->formEntryRepository = $formEntryRepository;
    }

	/**
	 * Configure the command by defining the name, options and arguments.
	 */
	protected function configure()
	{
		$this
			->setDescription('Sends a notification mail with a list of not exportet form entries.')
			->addArgument(
			'mailto',
				InputArgument::REQUIRED,
			'E-Mail Address the Mail should be sent to.'
			)
			->addOption(
				'formname',
						'',
				InputOption::VALUE_OPTIONAL,
				'Name of the form to be checked.'
			)
			->addOption(
			'title',
				'',
			InputOption::VALUE_OPTIONAL,
			'Subject Label.'
			);
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
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws Exception
	 */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
    	$mailto = $input->getArgument('mailto');
    	$formname = $input->getOption('formname');
	    $title= $input->getOption('title');

        if (empty($mailto)) {
            throw new Exception('You need to provide at least one email address.');
        }

        // $formEntryRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(FormEntryRepository::class);

        $search = GeneralUtility::makeInstance(FormEntryDemand::class);
        $search->setAllPids(true);

        if ($formname) {
        	$output->writeln("Searching for form ".$formname);
            $search->setFormName($formname);
        }else{
	        $output->writeln("Searching for no specific form");
        }

        $frommail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        if (!empty($frommail)) {
        	$output->writeln("Default E-Mail Address: ".$frommail);
            $from = $frommail;
        } else {
            throw new Exception("['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] is not set.");
        }
        $records = $this->formEntryRepository->findByDemand($search);

        if ($records->count()) {
        	$output->writeln($records->count()." entries found");
            /** @var FormEntry $row */
            foreach ($records as $row) {
            	$output->writeln("Sending entry with uid:".$row->getUid());
                $row->setExported(true);
                try {
	                $this->formEntryRepository->update($row);
                } catch (IllegalObjectTypeException $e) {
                	$output->writeln($e->getMessage());
                    return 0;
                } catch (UnknownObjectException $e) {
	                $output->writeln($e->getMessage());
                    return 0;
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
                $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
                $mail
                    ->setSubject($subject)
                    ->setFrom(array($from))
                    ->setTo(array($singlemail))
                    ->setBody($body, 'text/html')
                    ->send();
            }
        } else {
            $output->writeln("Nothing to send.");
        }
        return 1;
    }

}
