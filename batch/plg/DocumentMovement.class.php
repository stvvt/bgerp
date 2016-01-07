<?php



/**
 * Клас 'batch_plg_DocumentActions' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_DocumentMovement extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->storeFieldName, 'storeId');
		setIfNot($mvc->batchMovementDocument, 'out');
	}
	
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = NULL)
	{
		if($rec->state == 'active'){
			if(isset($saveFileds)) return;
			batch_Movements::saveMovement($mvc, $rec->id);
		} elseif($rec->state == 'rejected'){
			batch_Movements::removeMovement($mvc, $rec->id);
		}
	}
	
	
	/**
	 * Можели да се активира документа за движение
	 * 
	 * @param core_Master $mvc
	 * @param int $id
	 * @return boolean
	 */
	private static function canActivateMovementDoc(core_Master $mvc, $id)
	{
		$rec = $mvc->fetchRec($id);
		
		$details = array();
		if($mvc instanceof store_ConsignmentProtocols){
			$details['store_ConsignmentProtocolDetailsSend'] = 'store_ConsignmentProtocolDetailsSend';
			$details['store_ConsignmentProtocolDetailsReceived'] = 'store_ConsignmentProtocolDetailsReceived';
		} else {
			$details[$mvc->mainDetail] = $mvc->mainDetail;
		}
		
		foreach ($details as $det){
			$Detail = cls::get($det);
			$qQuery = $Detail->getQuery();
			$qQuery->where("#{$Detail->masterKey} = {$rec->id}");
				
			while($dRec = $qQuery->fetch()){
				if(batch_plg_DocumentMovementDetail::getBatchRecInvalidMessage($Detail, $dRec)){
					return FALSE;
				}
			}
		}

		if($mvc instanceof planning_DirectProductionNote){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(is_object($BatchClass)){
				if(empty($rec->batch)){
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
	
	/**
	 * Извиква се преди изпълняването на екшън
	 *
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 * @param string $action
	 */
	public static function on_BeforeAction($mvc, &$res, $action)
	{
		if(strtolower($action) == 'chooseaction'){
			expect($id = Request::get('id', 'int'));
			
			if(!self::canActivateMovementDoc($mvc, $id)){
				redirect(array($mvc, 'single', $id), FALSE, '|Не може да се контира|*, |докато има несъответствия|*', 'error');
			}
		}
	}
	
	
	/**
	 * Изпълнява се преди контиране на документа
	 */
	public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
	{
		if(!self::canActivateMovementDoc($mvc, $id)){
			redirect(array($mvc, 'single', $id), FALSE, '|Не може да се контира|*, |докато има несъответствия|*', 'error');
		}
	}
}