<?php

namespace Frappant\FrpFormAnswers\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/***
 *
 * This file is part of the "Form Answer Saver" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2017 !frappant <support@frappant.ch>
 *
 ***/
/**
 * FormEntry
 */
class FormEntryDemand extends AbstractEntity
{
    /**
     * selectAll
     *
     * @var bool
     */
    protected $selectAll = false;

    /**
     * allPids
     *
     * @var bool
     */
    protected $allPids = false;

    /**
     * exportType
     *
     * @var string
     */
    protected $exportType = '';

    /**
     * uidLabel
     *
     * @var string
     */
    protected $uidLabel = 'uid';

    /**
     * form
     *
     * @var string
     */
    protected $form = '';

    /**
     * formName
     *
     * @var string
     */
    protected $formName = '';

    /**
     * fileName
     *
     * @var string
     */
    protected $fileName = '';

    /**
     * charset
     *
     * @var string
     */
    protected $charset = '';

    /**
     * delimiter
     *
     * @var string
     */
    protected $delimiter = '';

    /**
     * enclosure
     *
     * @var string
     */
    protected $enclosure = '';

    /**
     * uidField
     *
     * @var string
     */
    protected $uidField = '';

    /**
     * Returns the selectAll
     *
     * @return bool $selectAll
     */
    public function getSelectAll()
    {
        return $this->selectAll;
    }

    /**
     * Sets the selectAll
     *
     * @param bool $selectAll
     */
    public function setSelectAll($selectAll): void
    {
        $this->selectAll = $selectAll;
    }

    /**
     * Returns the boolean state of selectAll
     *
     * @return bool
     */
    public function isSelectAll()
    {
        return $this->selectAll;
    }

    /**
     * Returns the allPids
     *
     * @return bool $allPids
     */
    public function getAllPids()
    {
        return $this->allPids;
    }

    /**
     * Sets the allPids
     *
     * @param bool $allPids
     */
    public function setAllPids($allPids): void
    {
        $this->allPids = $allPids;
    }

    /**
     * Returns the boolean state of allPids
     *
     * @return bool
     */
    public function isAllPids()
    {
        return $this->allPids;
    }

    /**
     * Returns the exportType
     *
     * @return string $exportType
     */
    public function getExportType()
    {
        return $this->exportType;
    }

    /**
     * Sets the exportType
     *
     * @param string $exportType
     */
    public function setExportType($exportType): void
    {
        $this->exportType = $exportType;
    }

    /**
     * Returns the uidLabel
     *
     * @return string $uidLabel
     */
    public function getUidLabel()
    {
        return $this->uidLabel;
    }

    /**
     * Sets the uidLabel
     *
     * @param string $uidLabel
     */
    public function setUidLabel($uidLabel): void
    {
        $this->uidLabel = $uidLabel;
    }

    /**
     * Returns the form
     *
     * @return string $form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Sets the form
     *
     * @param string $form
     */
    public function setForm($form): void
    {
        $this->form = $form;
    }

    /**
    * Returns the formName
    *
    * @return string $formName
    */
    public function getFormName()
    {
        return $this->formName;
    }

    /**
     * Sets the formName
     *
     * @param string $formName
     */
    public function setFormName($formName): void
    {
        $this->formName = $formName;
    }

    /**
     * Returns the fileName
     *
     * @return string $fileName
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Sets the fileName
     *
     * @param string $fileName
     */
    public function setFileName($fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * Returns the charset
     *
     * @return string $charset
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Sets the charset
     *
     * @param string $charset
     */
    public function setCharset($charset): void
    {
        $this->charset = $charset;
    }

    /**
    * Returns the delimiter
    *
    * @return string $delimiter
    */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Sets the delimiter
     *
     * @param string $delimiter
     */
    public function setDelimiter($delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Returns the enclosure
     *
     * @return string $enclosure
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * Sets the enclosure
     *
     * @param string $enclosure
     */
    public function setEnclosure($enclosure): void
    {
        $this->enclosure = $enclosure;
    }

    /**
     * Returns the uidField
     *
     * @return string $uidField
     */
    public function getUidField()
    {
        return $this->uidField;
    }

    /**
     * Sets the uidField
     *
     * @param string $uidField
     */
    public function setUidField($uidField): void
    {
        $this->uidField = $uidField;
    }
}
