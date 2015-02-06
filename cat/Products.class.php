<?php



/**
 * Регистър на продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cat_Products extends core_Embedder {
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $innerObjectInterface = 'cat_ProductDriverIntf';
	
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf,mp_ResourceSourceIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Артикули в каталога";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew, plg_PrevAndNext, acc_plg_Registry, plg_Rejected, plg_State,
                     cat_Wrapper, plg_Sorting, bgerp_plg_Groups, plg_Printing, Groups=cat_Groups, plg_Select, plg_Search, bgerp_plg_Import';
    
    
    /**
     * Име на полето за групите на продуктите.
     * Използва се за целите на bgerp_plg_Groups
     */
    var $groupField = 'groups';


    /**
     * Име на полето с групите, в които се намира продукт. Използва се от groups_Extendable
     * 
     * @var string
     */
    var $groupsField = 'groups';

    
    /**
     * Детайла, на модела
     */
    var $details = 'Packagings=cat_products_Packagings,Files=cat_products_Files,PriceGroup=price_GroupOfProducts,PriceList=price_ListRules,AccReports=acc_ReportDetails,VatGroups=cat_products_VatGroups,Resources=mp_ObjectResources';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '301,302,304,305,306,309,321';
    
    
    /**
     * Да се показват ли в репортите нулевите редове
     */
    public $balanceRefShowZeroRows = TRUE;
    
    
    /**
     * По кой итнерфейс ще се групират сметките 
     */
    public $balanceRefGroupBy = 'cat_ProductAccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,sales,purchase,store,acc,cat';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Артикул";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/wooden-box.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'name,code,groups,innerClass,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разгледа?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'cat,ceo';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'ceo,cat';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'ceo, cat';
    
    
    /**
     * Кой  може да групира "С избраните"?
     */
    var $canGrouping = 'ceo,cat';

	
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    var $canSingle = 'powerUser';
    
	
    /** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'name, code';
	
	
	/**
	 * Дефолт достъп до новите корици
	 */
	public $defaultAccess = 'public';
	
	
	/**
	 * Шаблон (ET) за заглавие на продукт
	 * 
	 * @var string
	 */
	public $recTitleTpl = '[#name#] ( [#code#] )';
    
    
	/**
	 * Кои полета от мениджъра преди запис да се обновяват със стойностти от драйвера
	 */
	public $fieldsToBeManagedByDriver = 'info, measureId, photo';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,remember=info,width=100%');
		$this->FLD('code', 'varchar(64)', 'caption=Код, mandatory,remember=info,width=15em');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание,input=none,formOrder=4');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting,input=none,formOrder=4');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Фото,input=none,formOrder=4');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Групи,maxColumns=2,remember,formOrder=100');
        $this->FLD('privateFolderId', 'key(mvc=doc_Folders)', 'input=none'); // В коя частна папка да се показва
        $this->FLD('specificationId', 'key(mvc=techno2_SpecificationDoc)', 'input=none'); // Поле за пораждаща спецификация
        
        // Разбивки на свойствата за по-бързо индексиране и търсене
        $this->FLD('canSell', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canBuy', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canConvert', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('fixedAsset', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canManifacture', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('waste', 'enum(yes=Да,no=Не)', 'input=none');
        
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        						canManifacture=Производими,
        						waste=Отпаден)', 'caption=Свойства->Списък,columns=2,formOrder=100000000,mandatory');
        
        $this->setDbIndex('canSell');
        $this->setDbIndex('canBuy');
        $this->setDbIndex('canStore');
        $this->setDbIndex('canConvert');
        $this->setDbIndex('fixedAsset');
        $this->setDbIndex('canManifacture');
        $this->setDbIndex('waste');
        
        $this->setDbUnique('code');
    }
    
    function act_Test()
    {
    	$S = cls::get('cat_Setup');
    	$S->migrateMetas();
    }
    
    
    /**
     * Изпълнява се след подготовка на Едит Формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	// Слагаме полето за драйвър да е 'remember'
    	if($data->form->getField($mvc->innerClassField)){
    		$data->form->setField($mvc->innerClassField, 'remember');
    	}
    	
    	if(isset($data->form->rec->innerClass)){
    		$data->form->setField('innerClass', 'input=hidden');
    	}
    	
    	if(!$data->form->rec->id && ($code = Mode::get('catLastProductCode'))) {
            if ($newCode = str::increment($code)) {
            	
                //Проверяваме дали има такъв запис в системата
                if (!$mvc->fetch("#code = '$newCode'")) {
                    $data->form->rec->code = $newCode;
                }
            }
        }
        
        $data->form->rec->state = 'active';
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
		//Проверяваме за недопустими символи
        if ($form->isSubmitted()){
        	$rec = &$form->rec;
            if (preg_match('/[^0-9a-zа-я\- _]/iu', $rec->code)) {
                $form->setError('code', 'Полето може да съдържа само букви, цифри, тирета, интервали и долна черта!');
            }
           
        	if($rec->code) {
    				
    			// Проверяваме дали има продукт с такъв код (като изключим текущия)
	    		$check = $mvc->checkIfCodeExists($rec->code);
	    		if($check && ($check->productId != $rec->id)
	    			|| ($check->productId == $rec->id && $check->packagingId != $rec->packagingId)) {
	    			$form->setError('code', 'Има вече артикул с такъв код!');
			    }
    		}
    		
    		// Проверяваме дали избраните групи са в противоречие с драйвера
    		$Driver = $mvc->getDriver($rec);
    		$defMetas = $Driver->getDefaultMetas($mvc->defMetas);
    		
    		if(count($defMetas)){
    			$defMetas = arr::make($defMetas, TRUE);
    			$ourMetas = arr::make($rec->meta, TRUE);
    			
    			if(isset($ourMetas['canStore']) && !isset($defMetas['canStore'])){
    				$form->setError('meta', "Не може да създавате услуга, и да я правите складируема");
    			} elseif(isset($defMetas['canStore']) && !isset($ourMetas['canStore'])){
    				$form->setError('meta', "Не може да създавате стока, и да не е складируема");
    			}
    		}
        }
                
        if (!$form->gotErrors()) {
            if(!$form->rec->id && ($code = Request::get('code', 'varchar'))) {
                Mode::setPermanent('catLastProductCode', $code);
            }    
        }
    }
    
    
    /**
     * Преди запис на продукт
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	if(isset($rec->csv_measureId) && strlen($rec->csv_measureId) != 0){
    		$rec->measureId = cat_UoM::fetchField("#name = '{$rec->csv_measureId}'", "id");
    	}
    	
    	if(isset($rec->csv_groups) && strlen($rec->csv_groups) != 0){
    		$rec->groups = cat_Groups::getKeylistBySysIds($rec->csv_groups);
    	}
    	
    	if($rec->id){
    		$oldRec = $mvc->fetch($rec->id);
    		
    		// Старите мета данни
    		$rec->oldGroups = $oldRec->groups;
    		$rec->oldName = $oldRec->name;
    		$rec->oldCode = $oldRec->code;
    	}
    	
    	if(isset($rec->csv_name)){
    		$rec->name = $rec->csv_name;
    		$rec->innerForm = (object)array('measureId' => $rec->measureId);
    	}
    }
    
    
    /**
     * Извлича мета данните на продукт според групите в които участва
     * 
     * @param mixed $groups - групи в които участва
     */
    public static function getMetaData($groups)
    {
    	if($groups){
    		$meta = array();
    		if(!is_array($groups)){
    			 $groups = keylist::toArray($groups);
    		}
		    foreach($groups as $grId){
		    	$grRec = cat_Groups::fetch($grId);
		    	if($grRec->meta){
		    		$arr = explode(",", $grRec->meta);
		    		$meta = array_merge($meta, array_combine($arr, $arr));
		    	}
		    }
		    
		    return implode(',', $meta);
    	}
    	
    	return '';
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)',
            'caption=Подредба,input,silent,remember');

        $data->listFilter->FNC('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)',
            'placeholder=Всички групи,caption=Група,input,silent,remember');
		
        $data->listFilter->FNC('meta1', 'enum(all=Свойства,
        						canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        						canManifacture=Производими,
        						waste=Отпаден)', 'input');
		
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,order,meta1,groupId';
        $data->listFilter->input('order,groupId,search,meta1', 'silent');
        
    	// Подредба
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#name');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
        }
        
        if ($data->listFilter->rec->groupId) {
            $data->query->where("#groups LIKE '%|{$data->listFilter->rec->groupId}|%'");
        }
        
        if ($data->listFilter->rec->meta1 && $data->listFilter->rec->meta1 != 'all') {
        	$data->query->like('meta', "%{$data->listFilter->rec->meta1}%");
        }
    }


    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    public static function getItemRec($objectId)
    {
        $result = NULL;
        $self = cls::get(__CLASS__);
        
        if ($rec = self::fetch($objectId)) {
        	$Driver = $self->getDriver($rec);
        	$pInfo = $Driver->getProductInfo();
        	
        	$result = (object)array(
                'num'      => $rec->code . " a",
                'title'    => $pInfo->productRec->name,
                'uomId'    => $pInfo->productRec->measureId,
                'features' => array()
            );
            
            if($rec->groups){
            	$groups = strip_tags($self->getVerbal($rec, 'groups'));
            	$result->features = $result->features + arr::make($groups, TRUE);
            }
           
            $result->features = array_merge($Driver->getFeatures(), $result->features);
        }
        
        return $result;
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * 
     * @param mixed $properties - комбинация на горе посочените мета 
     * 							  данни или като масив или като стринг
     * @param int $limit 		- колко опции да върне
     * @return array $products - продукти отговарящи на условието, ако не са
     * 							 зададени мета данни връща всички продукти
     */
    public static function getByProperty($properties, $limit = NULL)
    {
    	$products = array();
    	$metaArr = arr::make($properties);
    	$query = self::getQuery();
    	
    	// Само активните артикули
    	$query->where("#state = 'active'");
    	
    	// Ограничаваме намерените записи
    	if(isset($limit)){
    		$query->limit($limit);
    	}
    	
    	// За всяко свойство търсим по полето за бързо търсене
    	if(count($metaArr)){
    		foreach ($metaArr as $meta){
    			$query->where("#{$meta} = 'yes'");
    		}
    	}
    	
    	// Подготвяме опциите
    	while($rec = $query->fetch()){
    		$products[$rec->id] = static::getTitleById($rec->id, FALSE);
    	}
    	
    	return $products;
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * 
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res
     * 	-> productRec - записа на продукта
     * 	-> meta - мета данни за продукта ако има
	 * 	     meta['canSell'] 		- дали може да се продава
	 * 	     meta['canBuy']         - дали може да се купува
	 * 	     meta['canConvert']     - дали може да се влага
	 * 	     meta['canStore']       - дали може да се съхранява
	 * 	     meta['canManifacture'] - дали може да се прозивежда
	 * 	     meta['fixedAsset']     - дали е ДМА
     * 	-> packagingRec - записа на опаковката, ако е зададена
     * 	-> packagings - всички опаковки на продукта, ако не е зададена
     */					
    public static function getProductInfo($productId, $packagingId = NULL)
    {
    	// Ако няма такъв продукт връщаме NULL
    	if(!$productRec = static::fetchRec($productId)) {
    		
    		return NULL;
    	}
    	
    	$self = cls::get(get_called_class());
    	$Driver = $self->getDriver($productId);
    	$res = $Driver->getProductInfo($packagingId);
    	
    	$res->productRec->code = $productRec->code;
    	
    	if($grRec = cat_products_VatGroups::getCurrentGroup($productId)){
    		$res->productRec->vatGroup = $grRec->title;
    	}
    	
    	if($productRec->meta){
    		if($meta = explode(',', $productRec->meta)){
    			foreach($meta as $value){
    				$res->meta[$value] = TRUE;
    			}
    		}
    	} else {
    		$res->meta = FALSE;
    	}
    	
    	$Packagings = cls::get('cat_products_Packagings');
    	if(!$packagingId) {
    		$res->packagings = array();
    		
    	    // Ако не е зададена опаковка намираме всички опаковки
    		$packagings = $Packagings->fetchDetails($productId);
    		
    		// Пре-индексираме масива с опаковки - ключ става id на опаковката 
    		foreach ((array)$packagings as $pack) {
    		    $res->packagings[$pack->packagingId] = $pack;
    		}
    		
    		// Сортираме опаковките, така че основната опаковка да е винаги първа (ако има)
    		uasort($res->packagings, function($a, $b){
                    if($a->isBase == $b->isBase)  return 0;
					return $a->isBase == 'yes' ? -1 : 1;
                });
    		
    	} else {
    		
    		// Ако е зададена опаковка, извличаме само нейния запис
    		$res->packagingRec = $Packagings->fetchPackaging($productId, $packagingId);
    		if(!$res->packagingRec) {
    			
    			// Ако я няма зададената опаковка за този продукт
    			return NULL;
    		}
    	}
    	
    	// Връщаме информацията за продукта
    	return $res;
    }
    
    
    /**
     * След рендиране на вградените данни от драйвера
     */
    public static function on_AfterRenderEmbeddedData($mvc, $res, core_ET &$tpl, core_ET $embededDataTpl, &$data)
    {
    	// Добавяме кода на продукта в шаблона
    	$tpl->replace($data->rec->code, 'code');
    }
    
    
    /**
     * Връща ид на продукта и неговата опаковка по зададен Код/Баркод
     * 
     * @param mixed $code - Код/Баркод на търсения продукт
     * @return stdClass $res - Информация за намерения продукт
     * и неговата опаковка
     */
    public static function getByCode($code)
    {
    	$code = trim($code);
    	expect($code, 'Не е зададен код');
    	$res = new stdClass();
    	
    	// Проверяваме имали опаковка с този код: вътрешен или баркод
    	$Packagings = cls::get('cat_products_Packagings');
    	$catPack = $Packagings->fetchByCode($code);
    	if($catPack) {
    		
    		// Ако има запис намираме ид-та на продукта и опаковката
    		$res->productId = $catPack->productId;
    		$res->packagingId = $catPack->packagingId;
    	} else {
    		
    		// Проверяваме имали продукт с такъв код
    		$query = static::getQuery();
    		$query->where(array("#code = '[#1#]'", $code));
    		if($rec = $query->fetch()) {
    			
    			$res->productId = $rec->id;
    			$res->packagingId = NULL;
    		} else {
    			
    			// Ако няма продукт
    			return FALSE;
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     *  Проверява дали съществува продукт с такъв код, Кода и ЕАН-то на продукта както и тези на опаковките им
     *  трябва да са уникални
     *  
     *  @param string $code - Код/Баркод на продукт
     *  @return boolean int/FALSE - id на продукта с такъв код или
     *  FALSE ако няма такъв продукт
     */
    function checkIfCodeExists($code)
    {
    	if($info = cat_Products::getByCode($code)) {
    		return $info;
    	} else {
    		return FALSE;
    	}
    }
    
    
    /**
     * Връща всички продукти които са в посочените групи/група 
     * зададени, чрез техни systemId-та
     * 
     * @param mixed $group - sysId (стринг) или масив от sysId-та на групи
     * @return array $result - Продукти отговарящи на посочената група/групи
     */
    public static function getByGroup($group)
    {
    	if(!is_array($group)){
    		$group = array($group);
    	}
    	
    	$result = array();
    	$query = static::getQuery();
    	$groupIds = cat_Groups::getKeylistBySysIds($group);
    	$query->likeKeylist('groups', $groupIds, TRUE);
    	
    	while($rec = $query->fetch()){
	    	$result[$rec->id] = static::getTitleById($rec->id);
	    }
	    
	    return $result;
    }
    
    
    /**
     * Връща ДДС на даден продукт
     * 
     * @param int $productId - Ид на продукт
     * @param date $date - Дата към която начисляваме ДДС-то
     * @return double $vat - ДДС-то на продукта:
     * Ако има параметър ДДС за продукта го връщаме, впротивен случай
     * връщаме ДДС-то от периода
     * 		
     */
    public static function getVat($productId, $date = NULL)
    {
    	expect(static::fetch($productId), 'Няма такъв артикул');
    	
    	if(!$date){
    		$date = dt::now();
    	}
    	
    	if($groupRec = cat_products_VatGroups::getCurrentGroup($productId)){
    		
    		return $groupRec->vat;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	
    	return $period->vatRate;
    }
    
    
	/**
     * След всеки запис
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
        if($rec->groups) {
            $mvc->updateGroupsCnt = TRUE;
        }
    }
    
    
	/**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        if($mvc->updateGroupsCnt) {
            $mvc->updateGroupsCnt();
        }
    }
    
    
    /**
     * Ъпдейтване на броя продукти на всички групи
     */
    private function updateGroupsCnt()
    {
    	$groupsCnt = array();
    	$query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            $keyArr = keylist::toArray($rec->groups);
            foreach($keyArr as $groupId) {
                $groupsCnt[$groupId]++;
            }
        }
        
        $groupQuery = cat_Groups::getQuery();
        while($grRec = $groupQuery->fetch()){
        	$grRec->productCnt = (int)$groupsCnt[$grRec->id];
        	cat_Groups::save($grRec);
        }
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
    	$file = "cat/csv/Products.csv";
    	$fields = array( 
	    	0 => "csv_name", 
	    	1 => "code", 
	    	2 => "csv_measureId", 
	    	3 => "csv_groups",
	    	4 => "access",
    		5 => "innerClass",);
    	
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    public function getProducts($customerClass, $customerId, $datetime = NULL, $properties = NULL, $limit = NULL)
    {
    	return static::getByProperty($properties, $limit);
    }
    
    
    /**
     * Връща цената по себестойност на продукта
     * @return double
     */
    public function getSelfValue($productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
    	// Ценоразпис себестойност
    	$listId = price_ListRules::PRICE_LIST_COST;
    	price_ListToCustomers::canonizeTime($date);
    	
    	return price_ListRules::getPrice($listId, $productId, $packagingId, $date);
    }
    
    
	/**
     * Връща масив със всички опаковки, в които може да участва един продукт
     */
    public function getPacks($productId)
    {
    	expect($rec = $this->fetch($productId));
    	
    	$pInfo = self::getProductInfo($productId);
    	
    	$options = array('' => cat_UoM::getTitleById($pInfo->productRec->measureId));
    	
    	$packs = $pInfo->packagings;
    	if(count($packs)){
    		foreach ($packs as $packRec){
    			$options[$packRec->packagingId] = cat_Packagings::getTitleById($packRec->packagingId);
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	$Driver = $this->getDriver($id);
    	$value = $Driver->getParamValue($sysId);
    	
    	return $value;
    }
    
    
    /**
     * Връща теглото на еденица от продукта, ако е в опаковка връща нейното тегло
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
    public function getWeight($productId, $packagingId = NULL)
    {
    	$weight = 0;
    	if($packagingId){
    		$pack = cat_products_Packagings::fetch("#productId = {$productId} AND #packagingId = {$packagingId}");
    		$weight = $pack->netWeight + $pack->tareWeight;
    	}
    	
    	if(!$weight){
    		$weight = $this->getParam($productId, 'transportWeight');
    	}
    	
    	return $weight;
    }
    
    
	/**
     * Връща обема на еденица от продукта, ако е в опаковка връща нейния обем
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
    public function getVolume($productId, $packagingId = NULL)
    {
    	$volume = 0;
    	if($packagingId){
    		$pack = cat_products_Packagings::fetch("#productId = {$productId} AND #packagingId = {$packagingId}");
    		$volume = $pack->sizeWidth * $pack->sizeHeight * $pack->sizeDepth;
    	}
    	
    	if(!$volume){
    		$volume = $this->getParam($productId, 'transportVolume');
    	}
    	
    	return $volume;
    }
    
    
    /**
     * Предефиниране на метода getTitleById
     * 
     * @param int $id - ид на продукт
     * @param boolean $escaped - дали да е ескейпнато
     * @param string(2) $lang - език
     * @return string $title - заглавието на продукта, ако има параметър за име на
     * зададения език, връща него.
     */
    public static function getTitleById($id, $escaped = TRUE, $full = FALSE, $lang = 'bg')
    {
     	// Ако езика е различен от българския
    	if($lang != 'bg'){
     		
    		// Проверяваме имали сетнат параметър "title<LG>" за името на продукта
     		$paramSysId = "title" . strtoupper($lang);
     		$Driver = cls::get(get_called_class())->getDriver($id);
     		$title = $Driver->getParamValue($paramSysId);
     		
     		// ако има се връща
     		if($title) return $title;
     	}
     	
     	// Ако няма зададено заглавие за този език, връща дефолтното
     	return parent::getTitleById($id, $escaped);
    }
    
    
    /**
     * След подготовка на записите в счетоводните справки
     */
    public static function on_AfterPrepareAccReportRecs($mvc, &$data)
    {
    	$recs = &$data->recs;
    	if(empty($recs) || !count($recs)) return;
    	
    	$packInfo = $mvc->getBasePackInfo($data->masterId);
    	$data->packName = $packInfo->name;
    	
    	foreach ($recs as &$dRec){
    		$dRec->blQuantity /= $packInfo->quantity;
    	}
    }
    
    
    /**
     * След подготовка на вербалнтие записи на счетоводните справки
     */
    public static function on_AfterPrepareAccReportRows($mvc, &$data)
    {
    	$rows = &$data->balanceRows;
    	$data->listFields = arr::make("tools=Пулт,ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,packId=Мярка,blQuantity=К-во,blAmount=Сума");
    	$data->reportTableMvc->FLD('packId', 'varchar', 'tdClass=small-field');
    	
    	foreach ($rows as &$arrs){
    		if(count($arrs['rows'])){
    			foreach ($arrs['rows'] as &$row){
    				$row['packId'] = $data->packName;
    			}
    		}
    	}
    }
    
    
    /**
     * Връща информация за основната опаковка на артикула
     * 
     * @param int $id - ид на продукт
     * @return stdClass - обект с информация
     * 				->name     - име на опаковката
     * 				->quantity - к-во на продукта в опаковката
     * 				->classId  - ид на cat_Packagings или cat_UoM
     * 				->id       - на опаковката/мярката
     */
    public function getBasePackInfo($id)
    {
    	$basePack = cat_products_Packagings::fetch("#productId = '{$id}' AND #isBase = 'yes'");
    	$arr = array();
    	
    	if($basePack){
    		$arr['name'] = cat_Packagings::getTitleById($basePack->packagingId);
    		$arr['quantity'] = $basePack->quantity;
    		$arr['classId'] = 'cat_Packagings';
    		$arr['id'] = $basePack->packagingId;
    	} else {
    		$measureId = $this->fetchField($id, 'measureId');
    		$arr['name'] = cat_UoM::getTitleById($measureId);
    		$arr['quantity'] = 1;
    		$arr['classId'] = 'cat_UoM';
    		$arr['id'] = $measureId;
    	}
    		
    	return (object)$arr;
    }
    
    
    /**
     * Връща клас имплементиращ `price_PolicyIntf`, основната ценова политика за този артикул
     */
    public function getPolicy()
    {
    	return cls::get('price_ListToCustomers');
    }
    
    
    /**
     * Можели обекта да се добави като ресурс?
     *
     * @param int $id - ид на обекта
     * @return boolean - TRUE/FALSE
     */
    public function canHaveResource($id)
    {
    	// Всеки артикул може да присъства само веднъж като ресурс
    	if(!mp_ObjectResources::fetch("#classId = '{$this->getClassId()}' AND #objectId = {$id}")){
    		$pInfo = $this->getProductInfo($id);
    		
    		// Може да се добавя ресурс само към Артикули, които са материали, ДА или вложими
    		if(isset($pInfo->meta['canConvert']) || isset($pInfo->meta['fixedAsset'])){
    			
    			return TRUE;
    		}
    	} 
    	
    	return FALSE;
    }
    
    
    /**
     * Връща дефолт информация от източника на ресурса
     *
     * @param int $id - ид на обекта
     * @return stdClass $res  - обект с информация
     * 		o $res->name      - име
     * 		o $res->measureId - име мярка на ресурса (@see cat_UoM)
     * 		o $res->type      -  тип на ресурса (material,labor,equipment)
     */
    public function getResourceSourceInfo($id)
    {
    	$res = new stdClass();
    	$pInfo = $this->getProductInfo($id);
    	
    	$res->name = $pInfo->productRec->name;
    	$res->measureId = $pInfo->productRec->measureId;
    	
    	// Ако артикула е ДМА, ще може да се избират само ресурси - оборудване
    	if(isset($pInfo->meta['fixedAsset'])){
    		$res->type = 'equipment';
    	}
    	 
    	// Ако артикула е материал, ще може да се избират само ресурси - материали
    	if(isset($pInfo->meta['canConvert'])){
    		$res->type = 'material';
    	}
    	
    	$res->type = (empty($res->type)) ? FALSE : $res->type;
    	
    	return $res;
    }
    
    
    /**
     * Заглавие на артикула
     */
    public function getProductTitle($id)
    {
    	return $this->getTitleById($id);
    }
    
    
    /**
     * Дали артикула е стандартен
     *
     * @param mixed $id - ид/запис
     * @return boolean - дали е стандартен или не
     */
    public function isProductStandart($id)
    {
    	return TRUE;
    }
    
    
    /**
     * Връща описанието на артикула
     *
     * @param mixed $id - ид/запис
     * @param enum $documentMvc - класа на документа
     * @return mixed - описанието на артикула
     */
    public function getProductDesc($id, $documentMvc, $time = NULL)
    {
    	$rec = $this->fetchRec($id);
    	
    	$tpl = new ET($this->recTitleTpl);
    	$tpl->replace($this->getVerbal($rec, 'code'), 'code');
    	$tpl->replace($this->getVerbal($rec, 'name'), 'name');
    	
    	return $tpl->getContent();
    }
    
    
    /**
     * Връща последното активно задание за спецификацията
     *
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на заданието или FALSE ако няма
     */
    public static function getLastActiveJob($id)
    {
    	//@TODO временно
    	return FALSE;
    }
    
    
    /**
     * Намира последната активна технологична рецепта за артикула
     *
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на рецептата или FALSE ако няма
     */
    public static function getLastActiveBom($id)
    {
    	//@TODO временно
    	return FALSE;
    }
    
    
    /**
     * Обработка, преди импортиране на запис при начално зареждане
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
    	expect(cls::haveInterface('cat_ProductDriverIntf', $rec->innerClass));
    	$rec->innerClass = cls::get($rec->innerClass)->getClassId();
    	$rec->state = 'active';
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	if($mvc->haveRightFor('add')){
    		 $data->toolbar->addBtn('Нова стока', array($mvc, 'add', 'innerClass' => cat_GeneralProductDriver::getClassId()), 'order=1', 'ef_icon = img/16/shopping.png,title=Създаване на нова стока');
    		 $data->toolbar->addBtn('Нова услуга', array($mvc, 'add', 'innerClass' => cat_GeneralServiceDriver::getClassId()), 'order=1', 'ef_icon = img/16/shopping.png,title=Създаване на нова услуга');
    	}
    }
    
    
    /**
     * Създава спецификация по даден артикул, ако няма такава
     * 
     * @param mixed $productId - ид или запис на артикул
     * @return int $specificationId - ид на новосъздадената спецификация
     */
    public static function createSpecification($productId, $folderId = NULL)
    {
    	expect($rec = static::fetchRec($productId));
    	
    	// Създаваме нова спецификация, ако има проблем, не правим нищо
    	$specificationRec = techno2_SpecificationDoc::createNew($rec->name, $rec->innerClass, $rec->innerForm, $rec->innerState, $folderId);
    	
    	$rec->specificationId = $specificationRec->id;
    	if($specificationRec->isPublic == 'no'){
    		$rec->privateFolderId = $specificationRec->folderId;
    	}
    	
    	static::save($rec, 'privateFolderId,specificationId');
    	
    	return $rec;
    }
    
    
    /**
     * Екшън да се добавя спецификация от артикул в папка
     */
    public function act_addSpecification()
    {
    	techno2_SpecificationDoc::requireRightFor('add');
    	expect($id = Request::get('id', int));
    	expect($rec = $this->fetch($id));
    	expect($rec->state != 'rejected');
    	expect(!$rec->specificationId);
    	
    	$form = cls::get('core_Form');
    	$form->title = 'Създаване на спецификация';
    	$form->FNC('unsortedFolderId', "key(mvc=techno2_SpecificationFolders,select=name,allowEmpty)", 'caption=Папка,mandatory,input');
    	
    	if(!techno2_SpecificationFolders::count("#state = 'active'")){
    		return Redirect(array('techno2_SpecificationFolders', 'list'), NULL, 'Няма налични папки за избор');
    	}
    	
    	$form->input();
    	if($form->isSubmitted()){
    		
    		// Създаваме спецификация във въпросната папка
    		$folderId = techno2_SpecificationFolders::forceCoverAndFolder($form->rec->unsortedFolderId);
    		$pRec = $this->createSpecification($rec, $folderId);
    		
    		// Споделяме потребителя до нишката на папката
    		$cu = core_Users::getCurrent();
    		$sRec = techno2_SpecificationDoc::fetch($pRec->specificationId);
    		doc_ThreadUsers::addShared($sRec->threadId, $sRec->containerId, $cu);
    		
    		// Редирект към новосъздадената спецификация
    		return Redirect(array('techno2_SpecificationDoc', 'single', $pRec->specificationId), 'Създадена е нова спецификация');
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
        
        // Рендиране на обвивката и формата
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * След подготовка на тулбара за еденичния изглед
     */
    protected static function on_AfterPrepareSingleToolbar(core_Mvc $mvc, &$res, $data)
    {
    	if($data->rec->state != 'rejected'){
    		if(isset($data->rec->specificationId)){
    			
    			// Добавяме бутон линк към спецификацията
    			if(techno2_SpecificationDoc::haveRightFor('single', $data->rec->specificationId)){
    				$data->toolbar->addBtn('Спецификация', array('techno2_SpecificationDoc', 'single', $data->rec->specificationId, 'ret_url' => TRUE), NULL, 'ef_icon = img/16/specification.png,title=Преглед на спецификацията');
    			}
    		} else {
    			if(techno2_SpecificationDoc::haveRightFor('add')){
    				$data->toolbar->addBtn('Нова спецификация', array('cat_Products', 'addSpecification', $data->rec->id, 'ret_url' => TRUE), NULL, 'ef_icon = img/16/star_2.png,title=Създаване на нова спецификация');
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'edit' && isset($rec)){
    		
    		// Ако има спецификация не може да се редактира артикула
    		if(isset($rec->specificationId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}