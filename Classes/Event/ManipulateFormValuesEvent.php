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

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Collects additional Formvalues to be added in SaveFormToDatabaseFinisher.
 */
final class ManipulateFormValuesEvent
{
    /**
     * @var array
     */
    protected array $values;

    public function __construct(array $values) {
        $this->values = $values;
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
}