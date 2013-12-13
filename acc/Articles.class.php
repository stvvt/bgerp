<?php



/**
 * Мениджър на мемориални ордери (преди "счетоводни статии")
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Articles extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'acc_TransactionSourceIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Мемориални Ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, plg_Printing,
                     acc_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, bgerp_plg_Blank, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, reason, valior, totalAmount, tools=Пулт";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_ArticleDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Мемориален ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/blog.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Mo";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'acc,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'acc,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'acc,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'acc,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,acc';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'acc/tpl/SingleArticle.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'reason, valior';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "6.1|Счетоводни";
      
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('reason', 'varchar(128)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Контиран,rejected=Оттеглен)', 'caption=Състояние,input=none');
    }
    
    
    /**
     * Прави заглавие на МО от данните в записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $valior = self::getVerbal($rec, 'valior');
        
        return "{$rec->id}&nbsp;/&nbsp;{$valior}";
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    static function on_AfterPrepareSingleTitle($mvc, &$res, $data)
    {
        $data->title .= " (" . $mvc->getVerbal($data->rec, 'state') . ")";
    }
    
    
    /**
     * След подготовка на сингъла
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $row = &$data->row;
        $rec = &$data->rec;
        
        if ($rec->originId) {
            $doc = doc_Containers::getDocument($rec->originId);
            $row->reason = ht::createLink($row->reason, array($doc->instance, 'single', $doc->that));
        }
    }
    
    
    /**
     * Извиква се при промяна на някой от записите в детайл-модел
     *
     * @param core_Master $mvc
     * @param int $masterId първичен ключ на мастър записа, чиито детайли са се променили
     * @param core_Detail $detailsMvc
     * @param stdClass $detailsRec данните на детайл записа, който е причинил промяната (ако има)
     */
    static function on_AfterDetailsChanged($mvc, &$res, $masterId, $detailsMvc, $detailsRec = NULL)
    {
        $mvc::updateAmount($masterId);
    }
    
    
    /**
     * Преизчислява дебитното и кредитното салдо на статия
     *
     * @param int $id първичен ключ на статия
     */
    private static function updateAmount($id)
    {
        $dQuery = acc_ArticleDetails::getQuery();
        $dQuery->XPR('sumAmount', 'double', 'SUM(#amount)', array('dependFromFields' => 'amount'));
        $dQuery->show('articleId, sumAmount');
        $dQuery->groupBy('articleId');
        
        $result = NULL;
        
        $rec = self::fetch($id);
        if ($r = $dQuery->fetch("#articleId = {$id}")) {
            $rec->totalAmount = $r->sumAmount;
        } else {
        	$rec->totalAmount = 0;
        }
        
        $result = self::save($rec);
        
        return $result;
    }
    
    
    /*******************************************************************************************
     * 
     *     Имплементация на интерфейса `acc_TransactionSourceIntf`
     * 
     ******************************************************************************************/
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function getTransaction($id)
    {
        // Извличане на мастър-записа
        expect($rec = self::fetchRec($id));

        $result = (object)array(
            'reason' => $rec->reason,
            'valior' => $rec->valior,
            'totalAmount' => $rec->totalAmount,
            'entries' => array()
        );
        
        if (!empty($rec->id)) {
            // Извличаме детайл-записите на документа. В случая просто копираме полетата, тъй-като
            // детайл-записите на мемориалните ордери имат същата структура, каквато е и на 
            // детайлите на журнала.
            $query = acc_ArticleDetails::getQuery();
            
            while ($entry = $query->fetch("#articleId = {$rec->id}")) {
                $result->entries[] = array(
                    'amount' => $entry->amount,
                
                    'debit' => array(
                        acc_Accounts::fetchField($entry->debitAccId, 'num'),
                        $entry->debitEnt1, // Перо 1
                        $entry->debitEnt2, // Перо 2
                        $entry->debitEnt3, // Перо 3
                        'quantity' => $entry->debitQuantity,
                    ),
                
                    'credit' => array(
                        acc_Accounts::fetchField($entry->creditAccId, 'num'),
                        $entry->creditEnt1, // Перо 1
                        $entry->creditEnt2, // Перо 2
                        $entry->creditEnt3, // Перо 3
                        'quantity' => $entry->creditQuantity,
                    ),
                );
            }
        }
        
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
        $rec->state = 'active';
        
        return self::save($rec, 'state');
    }


    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        $row->title = tr("Мемориален ордер");

        if($rec->state == 'draft') {
            $row->title .= ' (' . tr("чернова") . ')';
        } else {
            $row->title .= ' (' . $this->getVerbal($rec, 'totalAmount') . ' BGN' . ')';
        }

        $row->subTitle = type_Varchar::escape($rec->reason);
        
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        
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
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
        return cls::haveInterface('doc_ContragentDataIntf', $folderClass) || $folderClass == 'doc_UnsortedFolders';
    }
    
    
    /**
     * Екшън създаващ обратен мемориален ордер на контиран документ
     */
    function act_RevertArticle()
    {
    	expect(haveRole('acc,ceo'));
    	expect($docClassId = Request::get('docType', 'int'));
    	expect($docId = Request::get('docId', 'int'));
    	expect($journlRec = acc_Journal::fetchByDoc($docClassId, $docId));
		
    	expect($result = static::createReverseArticle($journlRec));
    	return Redirect(array(cls::get($docClassId), 'single', $docId), FALSE, "Създаден е успешно обратен Мемориален ордер");
    	
    }
    
    
	/**
     * Създава нов МЕМОРИАЛЕН ОРДЕР-чернова, обратен на зададения документ.
     * 
     * Контирането на този МО би неутрализирало счетоводния ефект, породен от контирането на 
     * оригиналния документ, зададен с <$docClass, $docId>
     * 
     * @param stdClass $journlRec - запис от журнала
     */
    public static function createReverseArticle($journlRec)
    {
        $mvc = cls::get($journlRec->docType);
        
        $articleRec = (object)array(
            'reason'      => tr('Сторниране на') . " " . $journlRec->reason . ' / ' . acc_Journal::recToVerbal($journlRec, 'valior')->valior,
            'valior'      => dt::now(),
            'totalAmount' => $journlRec->totalAmount,
            'state'       => 'draft',
        );
       
        $journalDetailsQuery = acc_JournalDetails::getQuery();
        $entries = $journalDetailsQuery->fetchAll("#journalId = {$journlRec->id}");
        
        if (cls::haveInterface('doc_DocumentIntf', $mvc)) {
            $mvcRec = $mvc->fetch($journlRec->docId);
            
            $articleRec->folderId = $mvcRec->folderId;
            $articleRec->threadId = $mvcRec->threadId;
            $articleRec->originId = $mvcRec->containerId;
        } else {
            $articleRec->folderId = doc_UnsortedFolders::forceCoverAndFolder((object)array('name' => 'Сторно'));
        }
        
        if (!$articleId = static::save($articleRec)) {
            return FALSE;
        }
        
        foreach ($entries as $entry) {
            $articleDetailRec = array(
                'articleId'      => $articleId,
                'debitAccId'     => $entry->debitAccId,
                'debitEnt1'      => $entry->debitItem1,
                'debitEnt2'      => $entry->debitItem2,
                'debitEnt3'      => $entry->debitItem3,
                'debitQuantity'  => -$entry->debitQuantity,
                'debitPrice'     => $entry->debitPrice,
                'creditAccId'    => $entry->creditAccId,
                'creditEnt1'     => $entry->creditItem1,
                'creditEnt2'     => $entry->creditItem2,
                'creditEnt3'     => $entry->creditItem3,
                'creditQuantity' => -$entry->creditQuantity,
                'creditPrice'    => $entry->creditPrice,
                'amount'         => isset($entry->amount) ? -$entry->amount : $entry->amount,
            );
            
            if (!$bSuccess = acc_ArticleDetails::save((object)$articleDetailRec)) {
                break;
            }
        }
        
        if (!$bSuccess) {
            // Възникнала е грешка - изтривасе всичко!
            static::delete($articleId);
            acc_ArticleDetails::delete("#articleId = {$articleId}");
            
            return FALSE;
        }
        
        return array('acc_Articles', $articleId);
    }
}
