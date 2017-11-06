<?php
namespace Frappant\FrpFormAnswers\Domain\Model;

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
class FormEntryDemand extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
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
     * @return void
     */
    public function setSelectAll($selectAll)
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
     * @return void
     */
    public function setAllPids($allPids)
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
     * @return void
     */
    public function setExportType($exportType)
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
     * @return void
     */
    public function setUidLabel($uidLabel)
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
     * @return void
     */
    public function setForm($form)
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
     * @return void
     */
    public function setFormName($formName)
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
     * @return void
     */
    public function setFileName($fileName)
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
     * @return void
     */
    public function setCharset($charset)
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
     * @return void
     */
    public function setDelimiter($delimiter)
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
     * @return void
     */
    public function setEnclosure($enclosure)
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
     * @return void
     */
    public function setUidField($uidField)
    {
        $this->uidField = $uidField;
    }
}
