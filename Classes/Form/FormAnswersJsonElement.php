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
                // altogether, and an unanswered field has none. A listener on
                // ManipulateFormValuesEvent may put anything in either of the
                // two, so neither is trusted to be a string.
                $label = $this->flatten($fieldValue['conf']['label'] ?? '');
                $value = $this->flatten($fieldValue['value'] ?? '');

                $out .= '<li>' .
                    htmlspecialchars($label !== '' ? $label : (string)$fieldKey) .
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

    /**
     * One line of text for whatever json_decode() gave us: a list of values
     * becomes a comma separated list, and anything that carries no text of its
     * own is left empty instead of ending up as the word "Array".
     */
    private function flatten(mixed $value): string
    {
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $parts[] = $this->flatten($item);
            }

            return implode(',', $parts);
        }

        return is_scalar($value) ? (string)$value : '';
    }
}
