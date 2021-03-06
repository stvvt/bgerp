<?php


/**
 * Клас 'findeals_AdvanceDeals'
 *
 * Мениджър за финансови сделки
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_AdvanceDeals extends findeals_Deals
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'deals_AdvanceDeals';
	
    
    /**
     * Заглавие
     */
    public $title = 'Служебни аванси';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ad';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_RegisterIntf, doc_DocumentIntf, email_DocumentIntf, deals_DealsAccRegIntf, bgerp_DealIntf, bgerp_DealAggregatorIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, acc_plg_Registry, findeals_Wrapper, plg_Printing, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search, doc_ActivatePlg, plg_Sorting, bgerp_plg_Blank';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,findeals';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,findeals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,findeals';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,findealsMaster';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,findeals';
    
    
    /**
     * Документа продажба може да бъде само начало на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,detailedName,folderId,state,createdOn,createdBy';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Служебен аванс';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/kwallet.png';

    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "4.2|Финанси";
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'findeals/tpl/SingleLayoutDeals.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'detailedName';
    
    
    /**
     * Брой детайли на страница
     */
    public $listDetailsPerPage = 20;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'dealName, accountId, description, folderId, id';
    
    
    /**
     * Позволени операции на последващите платежни документи
     */
    public $allowedPaymentOperations = array(
    		'debitDealCase'      => array('title' => 'Приход по финансова сделка', 'debit' => '501', 'credit' => '*'),
    		'debitDealBank'      => array('title' => 'Приход по финансова сделка', 'debit' => '503', 'credit' => '*'),
    		'creditDealCase'     => array('title' => 'Разход по финансова сделка', 'debit' => '*', 'credit' => '501'),
    		'creditDealBank'     => array('title' => 'Разход по финансова сделка', 'debit' => '*', 'credit' => '503'),
	);
    
    
    /**
     * Сметки с какви интерфейси да се показват за избор
     */
    protected $accountListInterfaces = 'crm_PersonAccRegIntf,deals_DealsAccRegIntf,currency_CurrenciesAccRegIntf';
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * Документи-финансови сделки могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return cls::haveInterface('crm_PersonAccRegIntf', $coverClass);
    }
}