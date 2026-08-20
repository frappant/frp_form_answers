<?php

namespace Frappant\FrpFormAnswers\ViewHelpers\Be;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class TableViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('table', 'string', 'Database table name', true);
        $this->registerArgument('formName', 'string', 'Restrict to the entries of this form', false, '');
        $this->registerArgument('columns', 'array', 'Columns to display', true);
        $this->registerArgument('pid', 'int', 'PID', true);
    }

    public function render()
    {

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $table = $this->arguments['table'];
        $formName = (string)$this->arguments['formName'];
        $columns = $this->arguments['columns'];
        // Fluid does not coerce an argument to its declared type, and the page
        // id reaches the template straight from the url
        $pid = (int)$this->arguments['pid'];

        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $queryBuilder = $connection->createQueryBuilder();

        $pencil = $iconFactory->getIcon(
            'actions-view',
            Icon::SIZE_SMALL
        );

        $trash = $iconFactory->getIcon(
            'actions-delete',
            Icon::SIZE_SMALL
        );


        $queryBuilder->select('uid as uid');
        foreach ($columns as $column) {
            $queryBuilder->addSelect($column);
        }
        $queryBuilder->from($table)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            );

        if ($formName !== '') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('form', $queryBuilder->createNamedParameter($formName))
            );
        }

        $entries = $queryBuilder->executeQuery()->fetchAllAssociative();




        if(empty($entries)) {
            $output = '<div class="recordlist mb-5 mt-4 border">';
            $output .= '<div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center multi-record-selection-panel">
                        <div class="col ms-2">
                            <div class="recordlist-heading-title">Entry (0)</div>
                        </div>
                    </div>';
            $output .= '<div class="recordlist-body">';
            $output .= '<div class="alert alert-info">Keine Einträge vorhanden</div>';
            $output .= '</div>';
            $output .= '</div>';
        } else {
            $numberOfEntries = count($entries);
            $output = '<div class="recordlist mb-5 mt-4 border">';
            $output .= '<div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center multi-record-selection-panel">
                        <div class="col ms-2">
                            <div class="recordlist-heading-title">Entry (' . $numberOfEntries .')</div>
                        </div>
                    </div>';
            $output .= '<div class="recordlist-body">';

            $output .= '<table class="table table-striped table-borderless">';

            // Headers
            $output .= '<thead><tr>';
            foreach ($entries[0] as $key => $value) {
                $output .= '<th>' . htmlspecialchars($key) . '</th>';
            }
            $output .= '<th>Actions</th>';
            $output .= '</tr></thead>';



            // Data
            $output .= '<tbody>';
            foreach ($entries as $entry) {

                $backURL = $uriBuilder->buildUriFromRoutePath('/module/page/formanswers', [
                    'id' => $pid,
                ]);


                $editUri = $uriBuilder->buildUriFromRoutePath('/record/edit', [
                    "edit[" . $table . "][" . $entry['uid'] . "]" => "edit",
                    'pid' => $pid,
                    'returnUrl' => $backURL
                ]);


                $deleteUri = $uriBuilder->buildUriFromRoutePath('/module/page/formanswers', [
                    'action' => 'removeEntry',
                    'controller' => 'FormEntry',
                    'uid' => $entry['uid'],
                    'pid' => $pid,
                ]);

                $output .= '<tr>';
                $iterator = 0;
                foreach ($entry as $key => $value) {
                    if($iterator++ == 0) {
                        $output .= '<td><a href="' . $editUri . '">' . htmlspecialchars($value) . '</a></td>';
                    } else {
                        $output .= '<td>' . htmlspecialchars($value) . '</td>';
                    }
                }
                $output .= '<td>';

                $output .= '<a href=" ' . $editUri . '" class="btn btn-default btn-sm">'.$pencil.'</a>';




                $output .= '<a href="' . $deleteUri . '" class="btn btn-default btn-sm">' . $trash . '</a>';
                $output .= '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody>';

            $output .= '</table>';

            $output .= '</div>';
            $output .= '</div>';

        }




        return $output;
    }
}
