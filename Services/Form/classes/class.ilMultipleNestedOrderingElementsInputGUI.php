<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Form/classes/class.ilIdentifiedMultiValuesInputGUI.php';
require_once 'Modules/Test/classes/inc.AssessmentConstants.php';
/**
 *
 * @author Nadia Ahmad <nahmad@databay.de>
 * @version $Id: $
 * @ingroup	ServicesForm
 */

abstract class ilMultipleNestedOrderingElementsInputGUI extends ilIdentifiedMultiValuesInputGUI
{
	const DEFAULT_INSTANCE_ID = 'default';
	
	protected $instanceId = self::DEFAULT_INSTANCE_ID;
	
	protected $interactionEnabled = true;
	
	protected $stylingDisabled = false;
	
	protected $listTpl = null;
	
	public function __construct($a_title = '', $a_postvar = '')
	{
		parent::__construct($a_title, $a_postvar);
	}

	public function setInstanceId($instanceId)
	{
		$this->instanceId = $instanceId;
	}

	public function getInstanceId()
	{
		return $this->instanceId;
	}
	
	public function setInteractionEnabled($interactionEnabled)
	{
		$this->interactionEnabled = $interactionEnabled;
	}
	
	public function isInteractionEnabled()
	{
		return $this->interactionEnabled;
	}
	
	public function isStylingDisabled()
	{
		return $this->stylingDisabled;
	}
	
	public function setStylingDisabled($stylingDisabled)
	{
		$this->stylingDisabled = $stylingDisabled;
	}
	
	protected function isStylingEnabled()
	{
		return !$this->isStylingDisabled();
	}
	
	/**
	 * @return ilTemplate
	 */
	protected function getGlobalTpl()
	{
		return isset($GLOBALS['DIC']) ? $GLOBALS['DIC']['tpl'] : $GLOBALS['tpl'];
	}
	
	/**
	 * @return ilTemplate
	 */
	public function getListTpl()
	{
		return $this->listTpl;
	}
	
	/**
	 * @param ilTemplate $listTpl
	 */
	public function setListTpl($listTpl)
	{
		$this->listTpl = $listTpl;
	}
	
	protected function initListTemplate()
	{
		$this->setListTpl(
			new ilTemplate('tpl.prop_nested_ordering_list.html', true, true, 'Services/Form')
		);
	}
	
	protected function fetchListHtml()
	{
		return $this->getListTpl()->get();
	}
	
	protected function renderListContainer()
	{
		$this->getListTpl()->setCurrentBlock('list_container');
		$this->getListTpl()->setVariable('INSTANCE_ID', $this->getInstanceId());
		$this->getListTpl()->parseCurrentBlock();
	}
	
	protected function renderListSnippet()
	{
		$this->getListTpl()->setCurrentBlock('list_snippet');
		$this->getListTpl()->parseCurrentBlock();
	}

	protected function renderListItem($value, $identifier, $position)
	{
		$this->getListTpl()->setCurrentBlock('item_value');
		$this->getListTpl()->setVariable('LIST_ITEM_VALUE', $this->getItemHtml($value, $identifier, $position));
		$this->getListTpl()->parseCurrentBlock();
		$this->renderListSnippet();
	}

	abstract protected function getItemHtml($value, $identifier, $position);
	
	protected function renderBeginListItem($identifier)
	{
		$this->getListTpl()->setCurrentBlock('begin_list_item');
		$this->getListTpl()->setVariable('LIST_ITEM_ID', $identifier);
		$this->getListTpl()->parseCurrentBlock();
		$this->renderListSnippet();
	}
	
	protected function renderEndListItem()
	{
		$this->getListTpl()->setCurrentBlock('end_list_item');
		$this->getListTpl()->touchBlock('end_list_item');
		$this->getListTpl()->parseCurrentBlock();
		$this->renderListSnippet();
	}
	
	protected function renderBeginSubList()
	{
		$this->getListTpl()->setCurrentBlock('begin_sublist');
		$this->getListTpl()->touchBlock('begin_sublist');
		$this->getListTpl()->parseCurrentBlock();
		$this->renderListSnippet();
	}
	
	protected function renderEndSubList()
	{
		$this->getListTpl()->setCurrentBlock('end_sublist');
		$this->getListTpl()->touchBlock('end_sublist');
		$this->getListTpl()->parseCurrentBlock();
		$this->renderListSnippet();
	}
	
	/**
	 * @param array $elementValues
	 * @param integer $elementCounter
	 * @return integer $currentDepth
	 */
	abstract protected function getCurrentDepth($elementValues, $elementCounter);
	
	/**
	 * @param array $elementValues
	 * @param integer $elementCounter
	 * @return integer $nextDepth
	 */
	abstract protected function getNextDepth($elementValues, $elementCounter);
	
	protected function renderMainList()
	{
		$this->initListTemplate();
		$this->renderBeginSubList();
		
		$values = array_values($this->getMultiValues());
		$keys = array_keys($this->getMultiValues());
		
		$previous_depth = 0;
		$i = 0;
		
		foreach($values as $counter => $value)
		{
			$identifier = $keys[$counter];
			
			$current_depth 	= $this->getCurrentDepth($values, $counter);
			$next_depth 	= $this->getNextDepth($values, $counter);
			
			if($previous_depth == $current_depth)
			{
				// pcn = Previous, Current, Next -> Depth
				// pcn:  000, 001, 110, 220 
				if($current_depth == $next_depth)
				{
					// (1) pcn: 000
					//						echo"(1)";
					$this->renderBeginListItem($identifier);
					$this->renderListItem($value, $identifier, $counter);
					$this->renderEndListItem();
				}
				else if($current_depth > $next_depth)
				{
					if($previous_depth == $next_depth)
					{
						// wenn prev = cur ist und cur > next, wie soll prev = next sein !?
						
						// (8) pcn: 110 
						//							echo"(8)";
						$this->renderBeginListItem($identifier);
						$this->renderListItem($value, $identifier, $counter);
						$this->renderEndListItem();
						$this->renderEndSubList();
						$this->renderEndListItem();
					}
					else if($previous_depth > $next_depth)
					{
						// (12) pcn: 220 
						//							echo"(12)";
						$this->renderBeginListItem($identifier);
						$this->renderListItem($value, $identifier, $counter);
						
						for($openlists = $next_depth; $openlists < $current_depth; $openlists++)
						{
							$this->renderEndListItem();
							$this->renderEndSubList();
							$this->renderEndListItem();
						}
					}
				}
				else if($current_depth < $next_depth)
				{
					// (2) pcn: 001
					//						echo"(2)";
					$this->renderBeginListItem($identifier);
					$this->renderListItem($value, $identifier, $counter);
					$this->renderBeginSubList();
				}
			}
			else if($previous_depth > $current_depth)
			{
				if($current_depth == $next_depth)
				{
					// (6) pcn: 100  
					//						echo"(6)";
					$this->renderBeginListItem($identifier);
					$this->renderListItem($value, $identifier, $counter);
					$this->renderEndListItem();
				}
				else if($current_depth > $next_depth)
				{
					// (11) pcn: 210
					//						echo"(11)";
					$this->renderBeginListItem($identifier);
					$this->renderListItem($value, $identifier, $counter);
					$this->renderEndListItem();
					$this->renderEndSubList();
				}
				else if($current_depth < $next_depth)
				{
					if($previous_depth == $next_depth)
					{
						// (7) pcn: 101
						//							echo"(7)";
						$this->renderBeginListItem($identifier);
						$this->renderListItem($value, $identifier, $counter);
						$this->renderBeginSubList();
					}
					else if($previous_depth > $next_depth)
					{
						// (10) pcn: 201 
						//							echo"(10)";
						$this->renderBeginListItem($identifier);
						$this->renderListItem($value, $identifier, $counter);
						for($openlists = $next_depth; $openlists < $current_depth; $openlists++)
						{
							$this->renderEndSubList();
						}
						$this->renderBeginSubList();
					}
				}
			}
			else if($previous_depth < $current_depth)
			{
				if($current_depth == $next_depth)
				{
					// (4) pcn: 011  
					//						echo"(4)";
					$this->renderBeginListItem($identifier);
					$this->renderListItem($value, $identifier, $counter);
					$this->renderEndListItem();
				}
				else if($current_depth > $next_depth)
				{
					if($previous_depth == $next_depth)
					{
						// (3) pcn: 010, 
						//							echo"(3)";
						$this->renderBeginListItem($identifier);
						$this->renderListItem($value, $identifier, $counter);
						$this->renderEndListItem();
						$this->renderEndSubList();
						$this->renderEndListItem();
						
					}
					else if($previous_depth > $next_depth)
					{
						// (9) pcn: 120 
						//							echo"(9)";
						$this->renderListItem($value, $identifier, $counter);
						for($openlists = $next_depth; $openlists < $current_depth; $openlists++)
						{
							$this->renderBeginListItem($identifier);
							$this->renderEndListItem();
							$this->renderEndSubList();
						}
					}
				}
				else if($current_depth < $next_depth)
				{
					// (5) pcn: 012 
					//						echo"(5)";
					$this->renderBeginListItem($identifier);
					$this->renderListItem($value, $identifier, $counter);
					$this->renderBeginSubList();
				}
			}
			
			$previous_depth = $current_depth;
			
			$i++;
		}
		
		$this->renderEndSubList();
		$this->renderListContainer();
		
		return $this->fetchListHtml();
	}
	
	protected function renderJsInit()
	{
		$jsTpl = new ilTemplate('tpl.prop_nested_ordering_js.html', true, true, 'Services/Form');
		
		$jsTpl->setCurrentBlock('nested_ordering_init');
		$jsTpl->setVariable('INSTANCE_ID', $this->getInstanceId());
		$jsTpl->parseCurrentBlock();
		
		return $jsTpl->get();
	}
	
	public function render()
	{
		if( $this->isStylingEnabled() )
		{
			$this->getGlobalTpl()->addCss('Services/Form/css/nested_ordering.css');
		}
		
		if( $this->isInteractionEnabled() )
		{
			require_once 'Services/jQuery/classes/class.iljQueryUtil.php';
			
			iljQueryUtil::initjQuery();
			iljQueryUtil::initjQueryUI();
			
			$this->getGlobalTpl()->addJavaScript('Services/jQuery/js/jquery.nestable.js');
			
			return $this->renderMainList() . $this->renderJsInit();
		}
		
		return $this->renderMainList();
	}
	
	public function onCheckInput()
	{
		return true;
	}
	
	public function getHTML()
	{
		return $this->render();
	}
}