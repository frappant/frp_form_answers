<?php
namespace Frappant\FrpFormAnswers\ViewHelpers\Be\Link;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 !frappant <support@frappant.ch>
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

/**
 * Renders a link for a new record.
 *
 * /typo3/index.php?route=/record/edit&token=d7b2e14e24824711081ee8731549ca58afac0648&edit[tx_frpredirects_domain_model_redirect][2]=edit&returnUrl=/typo3/index.php?M=web_list&moduleToken=ae0ea6fabda3a2a34a8873319b91f8dc6010bf2f&id=0&imagemode=1
 */
class BeLinkViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
        $this->registerTagAttribute('pageUid', 'int', 'Page Uid');
    }

    public function render()
    {
        $returnUrl = $this->getRequestUri();
        $urlParameters = [
            'returnUrl' => $returnUrl,
            'id' => $this->arguments['pageUid']
        ];
        $uri = $this->getModuleUrl($urlParameters);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

    protected function getRequestUri()
    {
        return GeneralUtility::getIndpEnv('REQUEST_URI');
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function getModuleUrl(array $urlParameters)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return $uriBuilder->buildUriFromRoute('web_FrpFormAnswersFormanswers',$urlParameters);
    }
}
