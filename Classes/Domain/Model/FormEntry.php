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
class FormEntry extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * answers
     *
     * @var string
     */
    protected $answers = '';

    /**
     * fieldHash
     *
     * @var string
     */
    protected $fieldHash = '';

    /**
     * form
     *
     * @var string
     */
    protected $form = '';

    /**
     * exported
     *
     * @var bool
     */
    protected $exported = false;

    /**
     * @var \DateTime
     */
    protected $crdate;

    /**
     * exported
     *
     * @var int
     */
    protected $submitUid = '';

    /**
     * Returns the answers
     *
     * @return array $answers
     */
    public function getAnswers()
    {
        return json_decode($this->answers, 1);
    }

    /**
     * Sets the answers
     *
     * @param array $answers
     * @return void
     */
    public function setAnswers(array $answers)
    {
        $this->answers = json_encode($answers);
        ksort($answers);

        $fields = "";
        foreach ($answers as $field => $value) {
            $fields .= $field;
        }
        $this->fieldHash = md5($fields);
    }

    /**
     * Returns the fieldHash
     *
     * @return string $fieldHash
     */
    public function getFieldHash()
    {
        return $this->fieldHash;
    }

    /**
     * Sets the fieldHash
     *
     * @param string $fieldHash
     * @return void
     */
    public function setFieldHash($fieldHash)
    {
        $this->fieldHash = md5($fieldHash);
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
     * Returns the exported
     *
     * @return bool $exported
     */
    public function getExported()
    {
        return $this->exported;
    }

    /**
     * Sets the exported
     *
     * @param bool $exported
     * @return void
     */
    public function setExported($exported)
    {
        $this->exported = $exported;
    }

    /**
     * Returns the boolean state of exported
     *
     * @return bool
     */
    public function isExported()
    {
        return $this->exported;
    }

    /**
     * Set creation date
     *
     * @param int $crdate
     */
    public function setCrdate($crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * Get creation date
     *
     * @return int
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * Sets the submitUid
     *
     * @param int $submitUid
     * @return void
     */
    public function setSubmitUid($submitUid)
    {
        $this->submitUid = $submitUid;
    }

    /**
     * Returns the submitUid
     *
     * @return int
     */
    public function getSubmitUid()
    {
        return $this->submitUid;
    }
}
