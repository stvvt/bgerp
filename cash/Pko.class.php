<?php



/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Pko extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_Pko, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Дали сумата е във валута (различна от основната)
     *
     * @see acc_plg_DocumentSummary
     */
    public $amountIsInNotInBaseCurrency = TRUE;
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Приходни касови ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, cash_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, doc_SequencerPlg,acc_plg_DocumentSummary,
                     plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, doc_plg_HidePrices,
                     bgerp_DealIntf, doc_EmailCreatePlg, cond_plg_DefaultValues';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amount';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "tools=Пулт, valior, title=Документ, reason, folderId, currencyId=Валута, amount, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, cash';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, cash';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Приходен касов ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Pko";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cash, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cash, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'cash, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    public $canRevert = 'cash, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'cash/tpl/Pko.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, valior, contragentName, reason, id';
    
    
    /**
     * Параметри за принтиране
     */
    public $printParams = array( array('Оригинал'), array('Копие')); 

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'depositor'      => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Основна сч. сметка
     */
    public static $baseAccountSysId = '501';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,mandatory');
    	
    	// Платена сума във валута, определена от полето `currencyId`
    	$this->FLD('amountDeal', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,summary=amount');
    	$this->FLD('dealCurrencyId', 'key(mvc=currency_Currencies, select=code)', 'input=hidden');
    	
    	$this->FLD('reason', 'richtext(rows=2)', 'caption=Основание,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
    	$this->FLD('number', 'int', 'caption=Номер');
    	$this->FLD('peroCase', 'key(mvc=cash_Cases, select=name)', 'caption=Каса');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Вносител,mandatory');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('contragentAdress', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPlace', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPcode', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentCountry', 'varchar(255)', 'input=hidden');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,removeAndRefreshForm=amount,silent');
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Валута->Заверени,summary=amount,input=hidden');
    	$this->FLD('rate', 'double(decimals=5)', 'caption=Валута->Курс,input=none');
    	$this->FLD('notes', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторниран, closed=Контиран)', 
            'caption=Статус, input=none'
        );
    	$this->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
    	
        // Поставяне на уникален индекс
    	$this->setDbUnique('number');
    }
	
	
	/**
	 *  Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		cash_Cases::prepareCaseFilter($data, array('peroCase'));
	}
	
	
    /**
     *  Обработка на формата за редакция и добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = $data->form->rec->folderId;
    	$form = &$data->form;
    	
    	$contragentId = doc_Folders::fetchCoverId($folderId);
        $contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	$form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
    	
        expect($origin = $mvc->getOrigin($form->rec));
        $dealInfo = $origin->getAggregateDealInfo();
        $pOperations = $dealInfo->get('allowedPaymentOperations');
        
        $options = self::getOperations($pOperations);
        expect(count($options));
        
        // Използваме помощната функция за намиране името на контрагента
    	$form->setDefault('reason', "Към документ #{$origin->getHandle()}");
    	if($dealInfo->get('dealType') != findeals_Deals::AGGREGATOR_TYPE){
    		 		
    		$amount = ($dealInfo->get('amount') - $dealInfo->get('amountPaid')) / $dealInfo->get('rate');
    		if($amount <= 0) {
    		 	$amount = 0;
    	}
    		 		 
    	$defaultOperation = $dealInfo->get('defaultCaseOperation');
    	if($defaultOperation == 'customer2caseAdvance'){
    		 	$amount = $dealInfo->get('agreedDownpayment') / $dealInfo->get('rate');
    		 }
    	}
    		 	
	    if($caseId = $dealInfo->get('caseId')){
	    		 	
	    	// Ако потребителя има права, логва се тихо
	    	cash_Cases::selectCurrent($caseId);
	    }
    		 	
	    $cId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
	    $form->setDefault('dealCurrencyId', $cId);
	    $form->setDefault('currencyId', $cId);
    	
	    $form->setField('amountDeal', array('unit' => "{$dealInfo->get('currency')}, платени (погасени) по сделката"));
	    
	    if($dealInfo->get('dealType') == sales_Sales::AGGREGATOR_TYPE){
    		$dAmount = currency_Currencies::round($amount, $dealInfo->get('currency'));
    		if($dAmount != 0){
    		 	$form->setDefault('amountDeal',  $dAmount);
    		 }
    	}
    	
    	if($form->rec->currencyId != $form->rec->dealCurrencyId){
    		$form->setField('amount', 'input');
    	}
    	
    	// Поставяме стойности по подразбиране
    	$form->setDefault('valior', dt::today());
        
        if($contragentClassId == crm_Companies::getClassId()){
    		$form->setSuggestions('depositor', crm_Companies::getPersonOptions($contragentId, FALSE));
    	}
        
    	$form->setOptions('operationSysId', $options);
    	if(isset($defaultOperation) && array_key_exists($defaultOperation, $options)){
    		$form->setDefault('operationSysId', $defaultOperation);	
        }
        
    	$form->setDefault('peroCase', cash_Cases::getCurrent());
    	$cData = cls::get($contragentClassId)->getContragentData($contragentId);
    	$form->setReadOnly('contragentName', ($cData->person) ? $cData->person : $cData->company);
    }

    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции в които се дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['debit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	
    	if($form->rec->currencyId != $form->rec->dealCurrencyId){
    		$form->setField('amount', 'mandatory');
    	}
    	
    	if ($form->isSubmitted()){
    		$origin = $mvc->getOrigin($form->rec);
    		$dealInfo = $origin->getAggregateDealInfo();
    		
    		$operation = $dealInfo->allowedPaymentOperations[$rec->operationSysId];
    		$debitAcc = empty($operation['reverse']) ? $operation['debit'] : $operation['credit'];
    		$creditAcc = empty($operation['reverse']) ? $operation['credit'] : $operation['debit'];
    		$rec->debitAccount = $debitAcc;
    		$rec->creditAccount = $creditAcc;
    		$rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
    		
    		$contragentData = doc_Folders::getContragentData($rec->folderId);
	    	$rec->contragentCountry = $contragentData->country;
	    	$rec->contragentPcode = $contragentData->pCode;
	    	$rec->contragentPlace = $contragentData->place;
	    	$rec->contragentAdress = $contragentData->address;
	    	
	    	if($form->rec->currencyId == $form->rec->dealCurrencyId){
	    		$rec->amount = $rec->amountDeal;
	    	}
	    }
    	
	    acc_Periods::checkDocumentDate($form, 'valior');
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	
    	if($fields['-single']){
    		
    		$contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
    		$row->contragentAddress = $contragent->getFullAdress();
    		if($rec->dealCurrencyId != $rec->currencyId){
    			$baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
    			
    			if($rec->dealCurrencyId == $baseCurrencyId){
    				$rate = $rec->amountDeal / $rec->amount;
    				$rateFromCurrencyId = $rec->dealCurrencyId;
    				$rateToCurrencyId = $rec->currencyId;
    			} else {
    				$rate = $rec->amount / $rec->amountDeal;
    				$rateFromCurrencyId = $rec->currencyId;
    				$rateToCurrencyId = $rec->dealCurrencyId;
    			}
    			
    			$row->rate = cls::get('type_Double', array('params' => array('decimals' => 5)))->toVerbal($rate);
    			$row->rateFromCurrencyId = currency_Currencies::getCodeById($rateFromCurrencyId);
    			$row->rateToCurrencyId = currency_Currencies::getCodeById($rateToCurrencyId);
    		} else {
    			unset($row->dealCurrencyId);
    			unset($row->amountDeal);
    		}
           
	    	$spellNumber = cls::get('core_SpellNumber');
		    $amountVerbal = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
		    $row->amountVerbal = $amountVerbal;
		    	
    		// Вземаме данните за нашата фирма
        	$ownCompanyData = crm_Companies::fetchOwnCompany();
        	$Companies = cls::get('crm_Companies');
        	$row->organisation = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
        	$row->organisationAddress = $Companies->getFullAdress($ownCompanyData->companyId);
            
    		// Извличаме имената на създателя на документа (касиера)
    		$cashierRec = core_Users::fetch($rec->createdBy);
    		$cashierRow = core_Users::recToVerbal($cashierRec);
	    	$row->cashier = $cashierRow->names;
	    	
	    	$row->peroCase = cash_Cases::getHyperlink($rec->peroCase);
	    }
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('cash/tpl/styles.css', 'CSS');
    }
    
    
   	/*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
 	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->reason;
		
        return $row;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
			
    		// Ако няма позволени операции за документа не може да се създава
    		$operations = $firstDoc->getPaymentOperations();
    		$options = self::getOperations($operations);
    		
    		return count($options) ? TRUE : FALSE;
    	}
		
    	return FALSE;
    }


    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
        $rec = self::fetchRec($id);
    	$aggregator->setIfNot('caseId', $rec->peroCase);
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    public static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия приходен касов ордер") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['btnNewThread'])){
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    	
    	return $self->singleTitle . " №$rec->id";
    }
}
