<?php
namespace Frappant\FrpFormAnswers\Domain\Finishers;

use TYPO3\CMS\Form\Domain\Finishers;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

class SaveFormToDatabaseFinisher extends \TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher
{
    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     * @inject
     */
    protected $formEntryRepository = null;

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        // All fields, in array
        $fields = array();
        // Values of all fields, getFormValues() also gives pages,
        // so it will be filled in foreach
        $values = array();
        // All values, with pages
        $valuesWithPages = $this->finisherContext->getFormValues();

        // Goes trough all form-pages - and there trough all PageElements (Questions)
        foreach($this->finisherContext->getFormRuntime()->getPages() AS $key => $page){
            foreach($page->getElementsRecursively() AS $pageElem){

                $fields[] = $pageElem->getIdentifier();
                $values[$pageElem->getIdentifier()]['value'] = $valuesWithPages[$pageElem->getIdentifier()];
                $values[$pageElem->getIdentifier()]['conf']['label'] = $pageElem->getLabel();
                $values[$pageElem->getIdentifier()]['conf']['inputType'] = $pageElem->getType();
            }
        }

        $formEntry = $this->objectManager->get('Frappant\\FrpFormAnswers\\Domain\\Model\\FormEntry');
        $formEntry->setExported(false);
        $formEntry->setAnswers(json_encode($values));

        $formEntry->setForm($this->finisherContext->getFormRuntime()->getIdentifier());
        $formEntry->setPid($GLOBALS['TSFE']->id);



        $this->formEntryRepository->add($formEntry);
    }

    /**
     * Returns the values of the submitted form
     *
     * @return []
     */
    protected function getFormValues(): array
    {
        return $this->finisherContext->getFormValues();
    }

    /**
     * Returns a form element object for a given identifier.
     *
     * @param string $elementIdentifier
     * @return NULL|FormElementInterface
     */
    protected function getElementByIdentifier(string $elementIdentifier)
    {
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }
}
