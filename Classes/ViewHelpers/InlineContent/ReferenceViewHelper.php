<?php
namespace JonathanHeilmann\JhMagnificpopup\ViewHelpers\InlineContent;

/*
 * This file is part of the JonathanHeilmann\JhMagnificpopup extension under GPLv2 or later.
 * This file is based on the FluidTYPO3/Vhs project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;


/**
 * ViewHelper used to render referenced content elements in Fluid templates
 */
class ReferenceViewHelper extends AbstractInlineContentViewHelper
{

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument(
            'contentUids',
            'string',
            ''
        );
    }

    /**
     * Render method
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if ('BE' === TYPO3_MODE) {
            return '';
        }

        $contentUids = explode(',', $arguments['contentUids']);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        // Get contentPids
        $contentPids = [];
        foreach ($contentUids as $uid) {
            /** @var Statement $statement */
            $statement = $queryBuilder
                ->select('pid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                )
                ->execute();
            if ($pid = $statement->fetchColumn()) {
                $contentPids[] = $pid;
            }

        }
        $contentPids = array_unique($contentPids);

        // Get records
        $records = ReferenceViewHelper::getRecords([
            'uidInList' => $arguments['contentUids'],
            'pidInList' => '-1,' . implode(',', $contentPids),
            'includeRecordsWithoutDefaultTranslation' => !$arguments['hideUntranslated']
        ]);
        if (empty($records)) {
            return '';
        }

        // Reorder records by uid from contentUids
        $sortedRecords = [];
        foreach ($contentUids as $uid) {
            $sortedRecords[$uid] = $records[array_search($uid, array_column($records, 'uid'))];
        }

        // Render records
        $renderedRecords = ReferenceViewHelper::getRenderedRecords($sortedRecords);

        $content = implode(LF, $renderedRecords);
        return $content;
    }
}
