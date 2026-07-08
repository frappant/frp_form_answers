<?php
declare(strict_types=1);
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
class FormEntry extends AbstractEntity
{
    /**
     * answers
     *
     * @var string
     */
    protected string $answers = '';

    /**
     * fieldHash
     *
     * @var string
     */
    protected string $fieldHash = '';

    /**
     * form
     *
     * @var string
     */
    protected string $form = '';

    /**
     * exported
     *
     * @var bool
     */
    protected bool $exported = false;

    /**
     * TYPO3 "crdate" is stored as unix timestamp.
     */
    protected int $crdate = 0;

    /**
     * exported
     *
     * @var int
     */
    protected int $submitUid = 0;

    /**
     * Returns the answers
     *
     * @return array<string, mixed>
     */
    public function getAnswers(): array
    {
        $decoded = json_decode($this->answers, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Sets the answers
     *
     * @param array<string, mixed> $answers
     */
    public function setAnswers(array $answers): void
    {
        $this->answers = (string)json_encode($answers);
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
    public function getFieldHash(): string
    {
        return $this->fieldHash;
    }

    /**
     * Sets the fieldHash
     *
     * @param string $fieldHash
     */
    public function setFieldHash(string $fieldHash): void
    {
        $this->fieldHash = md5($fieldHash);
    }

    /**
     * Returns the form
     *
     * @return string $form
     */
    public function getForm(): string
    {
        return $this->form;
    }

    /**
     * Sets the form
     *
     * @param string $form
     */
    public function setForm(string $form): void
    {
        $this->form = $form;
    }

    /**
     * Returns the exported
     *
     * @return bool $exported
     */
    public function getExported(): bool
    {
        return $this->exported;
    }

    /**
     * Sets the exported
     *
     * @param bool $exported
     */
    public function setExported(bool $exported): void
    {
        $this->exported = $exported;
    }

    /**
     * Returns the boolean state of exported
     *
     * @return bool
     */
    public function isExported(): bool
    {
        return $this->exported;
    }

    /**
     * Set creation date
     *
     * @param int $crdate
     */
    public function setCrdate(int $crdate): void
    {
        $this->crdate = $crdate;
    }

    /**
     * Get creation date
     *
     * @return int
     */
    public function getCrdate(): int
    {
        return $this->crdate;
    }

    /**
     * Sets the submitUid
     *
     * @param int $submitUid
     */
    public function setSubmitUid(int $submitUid): void
    {
        $this->submitUid = $submitUid;
    }

    /**
     * Returns the submitUid
     *
     * @return int
     */
    public function getSubmitUid(): int
    {
        return $this->submitUid;
    }
}
