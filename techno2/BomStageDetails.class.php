<?php



/**
 * Мениджър на детайли на детайлите етапи на технологичните рецепти
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_BomStageDetails extends core_Detail
{
    
	
    /**
     * Заглавие
     */
    var $title = "Ресурси на технологичните рецепти";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'bomstageId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, techno2_Wrapper, plg_LastUsedKeys, plg_RowNumbering, plg_AlignDecimals';
    
    
    /**
     * По кое поле да се групират записите
     */
    var $groupByField = 'stageId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Рецепти';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,techno';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,techno';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,techno';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,techno';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('bomstageId', 'key(mvc=techno2_BomStages)', 'column=none,input=hidden,silent');
    	$this->FLD("resourceId", 'key(mvc=mp_Resources,select=title,allowEmpty)', 'caption=Ресурс,mandatory,silent', array('attr' => array('onchange' => 'addCmdRefresh(this.form);this.form.submit();')));
    	$this->FLD("productId", 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Артикул,input=none');
    	$this->FLD("specId", 'key(mvc=techno2_SpecificationDoc, select=title, allowEmpty)', 'caption=Спецификация,input=none');
    	$this->FLD("baseQuantity", 'double', 'caption=Количество->Начално,hint=Начално количество');
    	$this->FLD("propQuantity", 'double', 'caption=Количество->Пропорционално,hint=Пропорционално количество');
    	$this->FLD('toStore', 'key(mvc=store_Stores,select=name,allowEmpty)', 'column=none,input=none,caption=Към->Склад');
    	$this->FLD('toStage', 'key(mvc=mp_Stages,select=name,allowEmpty)', 'column=none,input=none,caption=Към->Етап');
    	$this->FLD('type', 'enum(input=Добавяне,popProduct=Изкарване,popResource=Изкарване2)', 'column=none,input=hidden,silent');
    	
    	$this->setDbUnique('bomstageId,resourceId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$masterRec = $mvc->Master->fetch($form->rec->bomstageId);
    	$act = (empty($form->rec->id)) ? tr('Добавяне') : tr('Редактиране');
    	$mTitle = techno2_BomStages::getRecTitle($form->rec->bomstageId);
    	
    	// Ако детайла е добавен към етап, показваме го в инфото
    	$stage = $mvc->Master->getVerbal($masterRec, 'stage');
    	if($stage != ''){
    		$form->info = "<b>" . tr('Етап') . "</b>: {$stage}";
    	}
    	
    	// Ако добавяме нов изходен ресурс
    	if($form->rec->type == 'popResource'){
    		$form->setField('resourceId', 'input=none');
    		$form->FNC('resource', 'varchar', 'input,mandatory,caption=Ресурс,before=baseQuantity');
    		$form->setField('toStage', 'input,mandatory');
    		$form->setField('baseQuantity', 'input=none');
    		$form->setField('propQuantity', 'mandatory,caption=Пропорц. к-во');
    		
    		$resourceArr = techno2_Boms::makeResourceOptions($masterRec->bomId, TRUE);
    		$form->setSuggestions('resource', $resourceArr);
    		
    		if($form->rec->id){
    			$form->setDefault('resource', mp_Resources::getTitleById($form->rec->resourceId, FALSE));
    		}
    		
    		// Задаваме възможните етапи
    		$stages = techno2_Boms::makeStagesOptions($masterRec->bomId, $masterRec->stage);
    		
    		if(count($stages)){
    			$form->setOptions('toStage', $stages);
    		} else {
    			$form->setReadOnly('toStage');
    		}
    		
    		$form->title = $act . tr(" |на|* ") . tr('изходен ресурс') . tr(' |към|* ') . "|*<b style='color:#ffffcc;'>{$mTitle}</span>";
    	} elseif ($form->rec->type == 'input'){
    		
    		// Ако добавяме нов ресурс
    		$resourceArr = techno2_Boms::makeResourceOptions($masterRec->bomId);
    		$form->setOptions('resourceId', $resourceArr);
    		
    	} elseif($form->rec->type == 'popProduct'){
    		
    		// Ако добавяме изходен артикул
    		$form->setField('baseQuantity', 'mandatory');
    		$form->setField('propQuantity', 'input=none');
    		$form->setField('resourceId', 'input=none');
    		$form->setField('productId', 'input');
    		$form->setField('specId', 'input');
    		$form->setField('toStore', 'input,mandatory');
    		
    		// Оставяме само производимите артикули и спецификации
    		$form->setOptions('productId', cat_Products::getByProperty('canManifacture'));
    		$form->setOptions('specId', techno2_SpecificationDoc::getByProperty('canManifacture'));
    		
    		$form->title = $act . tr(" |на|* ") . tr('изходен артикул') . tr(' |към|* ') . "|*<b style='color:#ffffcc;'>{$mTitle}</span>";
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	 
    	$masterRec = $mvc->Master->fetch($form->rec->bomstageId);
    	
    	// Ако има избран ресурс, добавяме му мярката до полетата за количества
    	if(isset($rec->resourceId)){
    		if($uomId = mp_Resources::fetchField($rec->resourceId, 'measureId')){
    			$uomName = cat_UoM::getShortName($uomId);
    			 
    			$form->setField('baseQuantity', "unit={$uomName}");
    			$form->setField('propQuantity', "unit={$uomName}");
    		}
    	}
    	 
    	// Проверяваме дали е въведено поне едно количество
    	if($form->isSubmitted()){
    		
    		// Не може и двете количества да са празни
    		if(empty($rec->baseQuantity) && empty($rec->propQuantity)){
    			$form->setError('baseQuantity,propQuantity', 'Трябва да е въведено поне едно количество');
    		}
    		
    		// Ако добавяме изходен артикул/ресурс трябва да е посочена дестинация
    		if($form->rec->type != 'input'){
    			if(empty($rec->toStore) && empty($rec->toStage)){
    				$form->setError('toStore,toStage', 'Трябва да има попълнена дестинация');
    			}
    		}
    		
    		// При добавяне на изходен артикул
    		if($rec->type == 'popProduct'){
    			
    			// Трябва да има поне един избран артикул или спецификация на артикул
    			if(empty($rec->productId) && empty($rec->specId)){
    				$form->setError('productId,specId', 'Не е избран изходен артикул');
    			}
    			
    			// Не може да се изберат и артикул и спецификация
    			if(isset($rec->productId) && isset($rec->specId)){
    				$form->setError('productId,specId', 'Трябва да е избран точно един артикул');
    			}
    		}
    		
    		// Ако изкарваме нов ресурс
    		if($rec->type == 'popResource'){
    			$thisStageOrder = mp_Stages::fetchField($masterRec->stage, 'order');
    			$toStageOrder = mp_Stages::fetchField($rec->toStage, 'order');
    			
    			// Трябва изходния етап да е по нов от сегашния
    			if($toStageOrder <= $thisStageOrder){
    				$form->setError('toStage', 'Не може да прехвърлите ресурса към по преден етап');
    			}
    			
    			// Ако изходния ресурс, фигурира като съществуващ ресурс
    			if($mId = mp_Resources::fetchField(array("#title = '[#1#]'", $rec->resource))){
    				$dQuery = techno2_Boms::getDetailQuery($masterRec->bomId);
    				$dQuery->where("#resourceId = {$mId} AND #type='popResource'");
    				if($dQuery->fetch()){
    					$form->setError('resource', 'Ресурса вече е добавен като изходен в някой етап етап');
    				}
    			}
    		}
    		
    		// Ако формата няма грешки
			if(!$form->gotErrors()){
				
				// Ако добавяме изходен ресурс
				if($rec->type == 'popResource'){
					
					// Ако е вече ресурс, взимаме му ид-то
					if($mId = mp_Resources::fetchField(array("#title = '[#1#]'", $rec->resource))){
						$rec->resourceId = $mId;
					} else {
						
						// Ако не е създаваме го като нов ресурс - заготовка
						$rec->resourceId = mp_Resources::save((object)array('title' => $rec->resource, 'type' => 'material', 'bomId' => $masterRec->bomId));
					}	
				}
			}
    		
    	}
    }
    
    
    /**
     * След обръщане на записа във вербален вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	// Ако има ресурс, показваме му мярката
    	if(isset($rec->resourceId)){
    		$row->measureId = cat_UoM::getTitleById(mp_Resources::fetchField($rec->resourceId, 'measureId'));
    	} 
    	
    	// Името и мярката на изходния артикул/спецификация
    	foreach (array('productId' => 'cat_Products', 'specId' => 'techno2_SpecificationDoc') as $fld => $ProductMan){
    		if(isset($rec->$fld)){
    			$mId = $ProductMan::getProductInfo($rec->$fld)->productRec->measureId;
    			$row->measureId = cat_UoM::getTitleById($mId);
    			$row->resourceId = $row->$fld;
    		}
    	}
    	
    	// Правим името линк
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    		$row->resourceId = ht::createLinkRef($row->resourceId, array('mp_Resources', 'single', $rec->resourceId));
    	}
    	
    	$row->ROW_ATTR['class'] = ($rec->type != 'input') ? 'row-removed' : 'row-added';
    	$row->ROW_ATTR['title'] = ($rec->type != 'input') ? tr('Изходен ресурс') : NULL;
    	
    	// На изходните ресурси/артикули показваме и дестинацията им
    	$img = ht::createElement('img', array('src' => sbf('img/16/move.png', ''), 'style' => 'position:relative;top:2px'));
    	if(isset($rec->toStore)){
    		$row->resourceId .= "&nbsp; " . $img . " &nbsp;" . store_Stores::getHyperlink($rec->toStore, TRUE);
    	}
    	
    	if(isset($rec->toStage)){
    		$row->resourceId .= "&nbsp; " . $img . " &nbsp;" . mp_Stages::getTitleById($rec->toStage);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
    		if(empty($rec->bomstageId)){
    			$requiredRoles = 'no_one';
    		} else {
    			$masterBomId = $mvc->Master->fetchField($rec->bomstageId, 'bomId');
    			$masterState = techno2_Boms::fetchField($masterBomId, 'state');
    			
    			if($masterState != 'draft'){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща URL към единичния изглед на мастера
     */
    public function getRetUrl($rec)
    {
    	$bomId = $this->Master->fetchField($rec->bomstageId, 'bomId');
    	$url = array('techno2_Boms', 'single', $bomId);
    
    	return $url;
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	// Рет урл-то не сочи към мастъра само ако е натиснато 'Запис и Нов'
    	if (isset($data->form) && ($data->form->cmd === 'save' || is_null($data->form->cmd))) {
    
    		// Променяма да сочи към single-a
    		$bomId = techno2_BomStages::fetchField($data->form->rec->bomstageId, 'bomId');
    		$data->retUrl = toUrl(array('techno2_Boms', 'single', $bomId));
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->query->orderBy("type");
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows(core_Mvc $mvc, &$data)
    {
    	$rows = &$data->rows;
    	
    	$img = ht::createElement('img', array('src' => sbf('img/16/move.png', ''), 'style' => 'position:relative;top:2px'));
    	$query = techno2_Boms::getDetailQuery($data->masterData->bomId);
    	
    	// Намираме имали ресурси, които са изходни за този етап, и ако има ги добавяме като първи ресурси в списъка
    	$addedRows = array();
    	$query->where("#toStage = {$data->masterData->stage}");
    	while($dRec = $query->fetch()){
    		$fRow = new stdClass();
    		$fRow->resourceId = $mvc->getFieldType('resourceId')->toVerbal($dRec->resourceId);
    		$fRow->propQuantity = $mvc->getFieldType('propQuantity')->toVerbal($dRec->propQuantity);
    		$fRow->ROW_ATTR['class'] = 'row-added';
    		$stage = techno2_BomStages::fetchField($dRec->bomstageId, 'stage');
    		
    		$fRow->resourceId = mp_Stages::getTitleById($stage) . "&nbsp; {$img} &nbsp;" . $fRow->resourceId;
    		
    		$addedRows[] = $fRow;
    	}
    	
    	// Ако има вече записи добавяме ги преди тях, иначе ги присвояваме
    	if(count($addedRows)){
    		if(count($rows)){
    			$rows = $addedRows + $rows;
    		} else {
    			$rows = $addedRows;
    		}
    	}
    	
    	// Така всички ресурси, които са изходни за този етап ще се появят в неговите резултати в началото
    }
}