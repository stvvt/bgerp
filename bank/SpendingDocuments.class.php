<?php 


/**
 * Разходен банков документ
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_SpendingDocuments extends bank_Document
{
   
   
   /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=bank_transaction_SpendingDocument, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Разходни банкови документи";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Разходен банков документ';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/bank_rem.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Rbd";
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'bank/tpl/SingleCostDocument.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.4|Финанси";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        parent::getFields($this);
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $today = dt::verbal2mysql();
        
        $contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        $contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
        $form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
        
        expect($origin = $mvc->getOrigin($form->rec));
        $form->setOptions('ownAccount', bank_OwnAccounts::getOwnAccounts(FALSE));
        
        $mvc->setDefaultsFromOrigin($origin, $form, $options);
        
        $form->setSuggestions('contragentIban', bank_Accounts::getContragentIbans($form->rec->contragentId, $form->rec->contragentClassId));
        $form->setDefault('valior', $today);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
        $form->setOptions('operationSysId', $options);
        
        if(isset($form->defaultOperation) && array_key_exists($form->defaultOperation, $options)){
            $form->setDefault('operationSysId', $form->defaultOperation);
        }
        
        $cData = cls::get($contragentClassId)->getContragentData($contragentId);
        $form->setReadOnly('contragentName', ($cData->person) ? $cData->person : $cData->company);
        
        $form->setField('ownAccount', 'caption=От->Сметка,after=reason');
        $form->setField('amount', 'caption=От->Заверени,after=reason');
        $form->setField('contragentName', 'caption=Към->Контрагент,after=reason');
        $form->setField('contragentIban', 'caption=Към->Сметка,after=reason');
    }
    
    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
        $options = array();
        
        // Оставяме само тези операции, в които се дебитира основната сметка на документа
        foreach ($operations as $sysId => $op){
            if($op['credit'] == static::$baseAccountSysId){
                $options[$sysId] = $op['title'];
            }
        }
        
        return $options;
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова".
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if($data->rec->state == 'draft') {
            
            // Ако дебитната сметка е за работа с контрагент слагаме бутон за
            // платежно нареждане ако е подочетно лице генерираме нареждане разписка
            if(bank_PaymentOrders::haveRightFor('add') && acc_Lists::getPosition($data->rec->debitAccId, 'crm_ContragentAccRegIntf')) {
                $data->toolbar->addBtn('Платежно нареждане', array('bank_PaymentOrders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png,title=Създаване на ново платежно нареждане');
            } elseif(bank_CashWithdrawOrders::haveRightFor('add') && acc_Lists::getPosition($data->rec->creditAccId, 'crm_PersonAccRegIntf')) {
                $data->toolbar->addBtn('Нареждане разписка', array('bank_CashWithdrawOrders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png,title=Създаване на ново нареждане разписка');
            }
        }
    }
}
