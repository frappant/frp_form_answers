<?php

declare(strict_types=1);

/***
 *
 * This file is part of the "Form Answer Saver" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2022 !Frappant <support@frappant.ch>
 *
 ***/

namespace Frappant\FrpFormAnswers\Event;

use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Collects additional Formvalues to be added in SaveFormToDatabaseFinisher.
 */
final class ManipulateFormValuesEvent
{
    /**
     * @var array
     */
    protected array $values;

    /**
     * @var FormRuntime $formRuntime
     */
    protected FormRuntime $formRuntime;

    public function __construct(array $values, FormRuntime $formRuntime) {
        $this->values = $values;
        $this->formRuntime = $formRuntime;
    }

    /**
     * @param array $value
     * @return void
     */
    public function addValue(array $addedValues): void
    {

        /**
         * contents of $this->values
         * array( array( field_identifier => array( 'value' => string, 'conf' => array( 'label' => string, 'inputType' => string ) ) ) )
         */

        // Example Content for test purposes
        /*$addedValues = [ 'text-57' => [
            'value' => 'hello world',
            'conf' => [
                'label' => 'TEST FROM EVENT',
                'inputType' => 'Text'
            ]
        ]];*/

        $this->values = array_merge($this->values, $addedValues);
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return FormRuntime
     */
    public function getFormRuntime(): FormRuntime
    {
        return $this->formRuntime;
    }

}
