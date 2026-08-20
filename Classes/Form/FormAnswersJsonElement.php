<?php

namespace Frappant\FrpFormAnswers\Form;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

class FormAnswersJsonElement extends AbstractFormElement
{
    /**
     * @return array<string, mixed>
     */
    public function render(): array
    {
        // Custom TCA properties and other data can be found in $this->data, for example the above
        // parameters are available in $this->data['parameterArray']['fieldConf']['config']['parameters']
        $resultArray = $this->initializeResultArray();
        $fieldValues = json_decode($this->data['databaseRow']['answers'], true);

        $out = '<ul>';

        if (is_array($fieldValues)) {
            foreach ($fieldValues as $fieldKey => $fieldValue) {
                // Entries saved by an older version can miss the value
                // altogether, and an unanswered field has none
                $value = $fieldValue['value'] ?? '';
                $value = is_array($value) ? implode(',', $value) : (string)$value;
                $label = $fieldValue['conf']['label'] ?? '';

                $out .= '<li>' .
                    htmlspecialchars($label !== '' ? $label : $fieldKey) .
                    ' - ' .
                    htmlspecialchars($value) .
                    '</li>'
                ;
            }
        }
        $out .= '</ul>';

        $resultArray['html'] = $out;
        return $resultArray;
    }
}
