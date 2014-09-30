<?php 


/**
 * Документ за Смяна на валута
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_ExchangeDocument extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Банкови обмени на валути";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, bank_Wrapper, plg_Printing, acc_plg_Contable,
     	plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search, doc_plg_MultiPrint, bgerp_plg_Blank, doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "tools=Пулт, number=Номер, valior, reason, creditCurrency=Обменени->Валута, creditQuantity=Обменени->Сума, debitCurrency=Получени->Валута, debitQuantity=Получени->Сума, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Банкова обмяна на валута';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money_exchange.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Sv";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'bank, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'bank,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'bank,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'acc, bank, ceo';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'bank/tpl/SingleExchangeDocument.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Финанси";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'reason, peroFrom, peroTo, id';
    
    
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,input,mandatory');
    	$this->FLD('peroFrom', 'key(mvc=bank_OwnAccounts, select=bankAccountId)','input,caption=От->Банк. сметка');
    	$this->FLD('creditPrice', 'double(smartRound,decimals=2)', 'input=none');
    	$this->FLD('creditQuantity', 'double(smartRound,decimals=2)', 'caption=От->Сума');
        $this->FLD('peroTo', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'input,caption=Към->Банк. сметка');
        $this->FLD('debitQuantity', 'double(smartRound,decimals=2)', 'caption=Към->Сума');
       	$this->FLD('debitPrice', 'double(smartRound,decimals=2)', 'input=none');
       	$this->FLD('equals', 'double(smartRound,decimals=2)', 'input=none,caption=Общо,summary=amount');
        $this->FLD('rate', 'double(smartRound,decimals=2)', 'input=none');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
        $this->FLD('sharedUsers', 'userList', 'input=none,caption=Споделяне->Потребители');
    }
	
    
	/**
	 *  Подготовка на филтър формата
	 */
	public static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		bank_OwnAccounts::prepareBankFilter($data, array('peroFrom', 'peroTo'));
	}
	
	
	/**
     *  Добавяме помощник за избиране на сч. операция
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
    	if ($action != 'add') {
            return;
        }
        
        if($folderId = Request::get('folderId')){
	        if($folderId != bank_OwnAccounts::fetchField(bank_OwnAccounts::getCurrent(), 'folderId')){
	        	return Redirect(array('bank_OwnAccounts', 'list'), FALSE, "Документът не може да се създаде в папката на неактивна сметка");
	        }
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$form = &$data->form;
    	$today = dt::verbal2mysql();
    	$cBank = bank_OwnAccounts::getCurrent();
    	$form->rec->folderId = bank_OwnAccounts::forceCoverAndFolder($cBank);
        $form->setDefault('peroFrom', $cBank);
        $form->setDefault('valior', $today);
        $form->setReadOnly('peroFrom');
		$form->setOptions('peroTo', bank_OwnAccounts::getOwnAccounts());
	}
    
    
    /**
     * Проверка след изпращането на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
    		if(!$rec->creditQuantity || !$rec->debitQuantity) {
    			$form->setError("creditQuantity, debitQuantity", "Трябва да са въведени и двете суми !!!");
    			return;
    		} 
    		
    		$creditAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom);
    		$debitAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
    		if($creditAccInfo->currencyId == $debitAccInfo->currencyId) {
		    	$form->setWarning('peroFrom, peroTo', 'Валутите са едни и същи, няма смяна на валута !!!');
		    }
		    
    		// Изчисляваме курса на превалутирането спрямо входните данни
		    $cCode = currency_Currencies::getCodeById($creditAccInfo->currencyId);
		    $dCode = currency_Currencies::getCodeById($debitAccInfo->currencyId);
		    $cRate = currency_CurrencyRates::getRate($rec->valior, $cCode, acc_Periods::getBaseCurrencyCode($rec->valior));
		    $rec->creditPrice = $cRate;
		    $rec->debitPrice = ($rec->creditQuantity * $rec->creditPrice) / $rec->debitQuantity;
		    $rec->rate = round($rec->creditPrice / $rec->debitPrice, 4);
		    	
    		if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $cCode, $dCode)){
		    	$form->setWarning('rate', $msg);
		    }
		    
		    // Каква е равностойноста на обменената сума в основната валута за периода
		    if($dCode == acc_Periods::getBaseCurrencyCode($rec->valior)){
		    	$rec->equals = $rec->creditQuantity * $rec->rate;
		    } else {
		    	$rec->equals = currency_CurrencyRates::convertAmount($rec->debitQuantity, $rec->valior, $dCode, NULL);
		    }
		    
		    $sharedUsers = bank_OwnAccounts::fetchField($rec->peroTo, 'operators');
    		$rec->sharedUsers = keylist::removeKey($sharedUsers, core_Users::getCurrent());
		}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}	
    	
	    $creditAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom);
    	$debitAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
	    $row->creditCurrency = currency_Currencies::getCodeById($creditAccInfo->currencyId);
	    $row->debitCurrency = currency_Currencies::getCodeById($debitAccInfo->currencyId);
    		
	    if($fields['-single']) {
	    	
    		// Показваме заглавието само ако не сме в режим принтиране
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
	    	
	    	$row->peroTo = bank_OwnAccounts::getHyperLink($rec->peroTo, TRUE);
	    	$row->peroFrom = bank_OwnAccounts::getHyperLink($rec->peroFrom, TRUE);
    	}
    }
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Ако избраната валута е в основна валута
     *
     *  	Dt: 503. Разплащателни сметки	         (Банкова сметка, Валута)
     *  	Ct: 503. Разплащателни сметки	         (Банкова сметка, Валута)
     *
     *  Ако е в друга валута различна от основната
     *
     *  	Dt: 503. Разплащателни сметки	         (Банкова сметка, Валута)
     *  	Ct: 481. Разчети по курсови разлики		 (Валута)
     *
     *  	Dt: 481. Разчети по курсови разлики	     (Валута)
     *  	Ct: 503. Разплащателни сметки	         (Банкова сметка, Валута)
     */
    public static function getTransaction($id)
    {
    	// Извличаме записа
        expect($rec = self::fetchRec($id));
        
        $cOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom, 'currencyId');
        $dOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
        
        $toBank = array('503',
                		array('bank_OwnAccounts', $rec->peroTo),
        				array('currency_Currencies', $dOwnAcc->currencyId),
                		'quantity' => $rec->debitQuantity);
        
        $fromBank = array('503',
                		array('bank_OwnAccounts', $rec->peroFrom),
        				array('currency_Currencies', $cOwnAcc->currencyId),
                		'quantity' => $rec->creditQuantity);
        
        if($cOwnAcc->currencyId == acc_Periods::getBaseCurrencyId($rec->valior)){
        	$entry = array('amount' => $rec->debitQuantity * $rec->debitPrice, 'debit' => $toBank, 'credit' => $fromBank);
        	$entry = array($entry);
        } else {
        	$entry = array();
        	$entry[] = array('amount' => $rec->debitQuantity, 
        					 'debit' => $toBank, 
        					 'credit' => array('481', array('currency_Currencies', $cOwnAcc->currencyId), 'quantity' => $rec->creditQuantity));
        	$entry[] = array('amount' => $rec->debitQuantity,
        					'debit' => array('481', array('currency_Currencies', $cOwnAcc->currencyId), 'quantity' => $rec->creditQuantity),
        					'credit' => $fromBank);
        }
        
      	// Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entry
        );
        
        return $result;
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        
        expect($rec->id);
        
        $rec->state = 'closed';
        
        return self::save($rec);
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
       // Може да създаваме документ-а само в дефолт папката му
       if ($folderId == static::getDefaultFolder(NULL, FALSE) || doc_Folders::fetchCoverClassName($folderId) == 'bank_OwnAccounts') {
        	return TRUE;
       } 
        
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
    	$threadRec = doc_Threads::fetch($threadId);
    	if ($threadRec->folderId == static::getDefaultFolder(NULL, FALSE) || doc_Folders::fetchCoverClassName($threadRec->folderId) == 'bank_OwnAccounts') {
        	return TRUE;
       } 
        
       return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
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
     * Връща счетоводното основание за документа
     */
    public function getContoReason($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	return $this->getVerbal($rec, 'reason');
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