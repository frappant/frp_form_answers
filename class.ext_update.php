<?php

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 !frappant <support@frappant.ch>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Update extension script.
 */
class ext_update
{
    /**
     * Array of flash messages (params) array[][status,title,message].
     *
     * @var array
     */
    protected $messageArray = array();

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var \TYPO3\CMS\Core\Messaging\Renderer\FlashMessageRendererInterface
     */
    protected $flashMessageRenderer;

    /**
     *  @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Constructor.
     */
    private function initUpdate()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
        $this->flashMessageRenderer = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve();
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(ObjectManager::class)->get(Dispatcher::class);
    }

    /**
     * Main function, returning the HTML content of the module.
     *
     * @return string HTML
     */
    public function main()
    {
        $this->initUpdate();
        $this->setSubmitUidsToFormEntryUid();

        if (empty($this->messageArray)) {
            $this->messageArray[] = new FlashMessage('Nothing to update!', '', FlashMessage::INFO);
        }

        return $this->generateOutput();
    }

    protected function setSubmitUidsToFormEntryUid()
    {
        $title = 'Set all submit_uids to value of uid.';

        $row = $this->databaseConnection->exec_SELECTgetRows('*', 'tx_frpformanswers_domain_model_formentry', 'submit_uid = 0');
        if ($row) {
            $this->databaseConnection->UPDATEquery('tx_frpformanswers_domain_model_formentry', 'submit_uid = 0', array('submit_uid' => 'uid'));
            $this->databaseConnection->sql_query('UPDATE tx_frpformanswers_domain_model_formentry SET submit_uid = uid WHERE submit_uid = 0;');
            $this->messageArray[] = new FlashMessage('Set all submit_uids to value of uid successfully.', $title, FlashMessage::OK);
        }
    }

    /**
     * @return TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Checks how many rows are found and returns true if there are any
     * (this function is called from the extension manager).
     *
     * @param string $what: what should be updated
     *
     * @return bool
     */
    public function access()
    {
        $this->initUpdate();
        $row = $this->databaseConnection->exec_SELECTcountRows('*', 'tx_frpformanswers_domain_model_formentry', 'submit_uid > 0');
        return ($row === 0);
    }

    /**
     * Generates output by using flash messages.
     *
     * @return string
     */
    protected function generateOutput()
    {
        return $this->flashMessageRenderer->render($this->messageArray);
    }
}
