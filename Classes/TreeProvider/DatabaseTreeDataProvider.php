<?php
namespace TYPO3\CMS\Cal\TreeProvider;
/**
 * This file is part of the TYPO3 extension Calendar Base (cal).
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 extension Calendar Base (cal) project - inspiring people to share!
 */

/**
 * TCA tree data provider which considers
 */
class DatabaseTreeDataProvider extends \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider {

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $backendUserAuthentication;

	/**
	 * Required constructor
	 *
	 * @param array $configuration TCA configuration
	 */
	public function __construct (array $configuration) {
		$this->backendUserAuthentication = $GLOBALS['BE_USER'];
	}

	/**
	 * Builds a complete node including children
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode|\TYPO3\CMS\Backend\Tree\TreeNode $basicNode
	 * @param NULL|\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode $parent
	 * @param integer $level
	 * @param bool $restriction
	 * @return \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode node
	 */
	protected function buildRepresentationForNode (\TYPO3\CMS\Backend\Tree\TreeNode $basicNode, \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode $parent = NULL, $level = 0, $restriction = FALSE) {
		/**@param $node \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode */
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance ('TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\DatabaseTreeNode');
		$row = array();
		if ($basicNode->getId () == 0) {
			$node->setSelected (FALSE);
			$node->setExpanded (TRUE);
			$node->setLabel ($GLOBALS['LANG']->sL ($GLOBALS['TCA'][$this->tableName]['ctrl']['title']));
		} else {
			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL ($this->tableName, $basicNode->getId (), '*', '', FALSE);

			if ($this->getLabelField () !== '') {
				$node->setLabel($row[$this->getLabelField()]);
			} else {
				$node->setLabel ($basicNode->getId ());
			}
			$node->setSelected (\TYPO3\CMS\Core\Utility\GeneralUtility::inList ($this->getSelectedList (), $basicNode->getId ()));
			$node->setExpanded ($this->isExpanded ($basicNode));
			$node->setLabel ($node->getLabel ());
		}

		$node->setId ($basicNode->getId ());

		// Break to force single category activation
		if ($parent != NULL && $level != 0 && $this->isSingleCategoryAclActivated() && !$this->isCategoryAllowed ($node)) {
			return NULL;
		}
		$node->setSelectable (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList ($this->getNonSelectableLevelList (), $level) && !in_array ($basicNode->getId (), $this->getItemUnselectableList ()));
		$node->setSortValue ($this->nodeSortValues[$basicNode->getId ()]);
		$node->setIcon (\TYPO3\CMS\Backend\Utility\IconUtility::mapRecordTypeToSpriteIconClass ($this->tableName, $row));
		$node->setParentNode ($parent);
		if ($basicNode->hasChildNodes ()) {
			$node->setHasChildren (TRUE);
			/** @var \TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection $childNodes */
			$childNodes = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance ('TYPO3\\CMS\\Backend\\Tree\\SortedTreeNodeCollection');
			$foundSomeChild = FALSE;
			foreach ($basicNode->getChildNodes () as $child) {
				// Change in custom TreeDataProvider by adding the if clause
				if ($restriction || $this->isCategoryAllowed ($child)) {
					$returnedChild = $this->buildRepresentationForNode ($child, $node, $level + 1, TRUE);

					if (!is_null ($returnedChild)) {
						$foundSomeChild = TRUE;
						$childNodes->append ($returnedChild);
					} else {
						$node->setParentNode (NULL);
						$node->setHasChildren (FALSE);
					}
				}
				// Change in custom TreeDataProvider end
			}

			if ($foundSomeChild) {
				$node->setChildNodes ($childNodes);
			}
		}
		return $node;
	}

	/**
	 * Check if given category is allowed by the access rights
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $child
	 * @return bool
	 */
	protected function isCategoryAllowed ($child) {
		$mounts = $this->backendUserAuthentication->getCategoryMountPoints();
		if (empty($mounts)) {
			return TRUE;
		}

		return in_array($child->getId(), $mounts);
	}

	/**
	 *
	 * @return bool
	 */
	protected function isSingleCategoryAclActivated() {
		return FALSE;
	}

}
