<?php
/***
 *
 * This file is part of the "!f Commands" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 !frappant <support@frappant.ch>
 *
 ***/

/**
 * Commands to be executed by typo3, where the key of the array
 * is the name of the command (to be called as the first argument after typo3).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 *
 * example: bin/typo3 backend:lock
 */
return [
	'f:formanswers:mailNotification' => [
		'class' => \Frappant\FrpFormAnswers\Command\MailAdminNotificationCommand::class,
	],
];
