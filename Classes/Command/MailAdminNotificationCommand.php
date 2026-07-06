<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\Command;

use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

#[AsCommand(
    name: 'f:formanswers:mailNotification',
    description: 'Sends a notification mail with a list of not exported form entries.',
)]
final class MailAdminNotificationCommand extends Command
{
    public function __construct(
        private readonly FormEntryRepository $formEntryRepository,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly MailerInterface $mailer,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'mailto',
                InputArgument::REQUIRED,
                'Email address the mail should be sent to. Multiple addresses can be comma-separated.'
            )
            ->addOption(
                'formname',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of the form to be checked.'
            )
            ->addOption(
                'title',
                null,
                InputOption::VALUE_OPTIONAL,
                'Subject label.'
            );
    }

    private function generateMailBody(iterable $mails): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: [
                'EXT:frp_form_answers/Resources/Private/CommandTask/Templates',
            ],
            partialRootPaths: [
                'EXT:frp_form_answers/Resources/Private/CommandTask/Partials',
            ],
            layoutRootPaths: [
                'EXT:frp_form_answers/Resources/Private/CommandTask/Layouts',
            ],
        );

        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple([
            'mails' => $mails,
        ]);

        return $view->render('FormEntries/InMail');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $mailto = trim((string)$input->getArgument('mailto'));
        $formname = trim((string)($input->getOption('formname') ?? ''));
        $title = trim((string)($input->getOption('title') ?? ''));

        $recipients = array_values(array_filter(
            array_map('trim', explode(',', $mailto)),
            static fn (string $email): bool => $email !== ''
        ));

        if ($recipients === []) {
            throw new \RuntimeException(
                'You need to provide at least one email address.',
                2340297863
            );
        }

        $search = new FormEntryDemand();
        $search->setAllPids(true);

        if ($formname !== '') {
            $io->writeln('Searching for form ' . $formname);
            $search->setFormName($formname);
        } else {
            $io->writeln('Searching for no specific form');
        }

        $fromMail = (string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '');
        $fromName = (string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? '');

        if ($fromMail === '') {
            throw new \RuntimeException(
                '$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromAddress\'] is not set.',
                5763504134
            );
        }

        $io->writeln('Default email address: ' . $fromMail);

        $records = $this->formEntryRepository->findByDemand($search);
        $recordCount = $records->count();

        if ($recordCount === 0) {
            $io->writeln('Nothing to send.');
            return Command::SUCCESS;
        }

        $io->writeln($recordCount . ' entries found');

        $body = $this->generateMailBody($records);
        $subject = $title !== '' ? $title : 'Scheduler mails update ' . date('d/m/y');

        foreach ($recipients as $recipient) {
            $email = (new MailMessage())
                ->subject($subject)
                ->from(new Address($fromMail, $fromName))
                ->to(Address::create($recipient))
                ->html($body);

            $this->mailer->send($email);
        }

        try {
            /** @var FormEntry $row */
            foreach ($records as $row) {
                $io->writeln('Marking entry as exported, uid: ' . $row->getUid());
                $row->setExported(true);
                $this->formEntryRepository->update($row);
            }

            $this->persistenceManager->persistAll();
        } catch (IllegalObjectTypeException | UnknownObjectException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
