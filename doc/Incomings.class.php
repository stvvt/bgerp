<?php 


/**
 * Входящи документи
 *
 * Създава на документи от файлове.
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Incomings extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Входящи документи';
    
    
    /**
     * 
     */
    var $singleTitle = 'Входящ документ';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, doc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, doc';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     *
     */
    var $canActivate = 'user';
    
    
    /**
     * Кой има права за
     */
    var $canDoc = 'admin, doc, user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, plg_RowTools, doc_DocumentPlg, 
         plg_Printing, plg_Sorting, plg_Search, doc_ActivatePlg, bgerp_plg_Blank';
    
    
    /**
     * Сортиране по подразбиране по низходяща дата
     */
    var $defaultSorting = 'createdOn=down';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutIncomings.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/page_attach.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "D";
    
    
    /**
     * Полето "Заглавие" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, date, total, createdOn, createdBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, type, fileHnd, date, total, keywords';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar', 'caption=Заглавие, width=100%, mandatory');
        $this->FLD('type', 'enum(
        							empty=&nbsp;,
            						invoice=Фактура,
            						payment order=Платежно нареждане,
            						waybill=Товарителница
        						)', 
            'caption=Тип, width=50%'
        ); //TODO може да се реализира да е key към отделен модел за типове на документи
        $this->FLD('fileHnd', 'fileman_FileType(bucket=Documents)', 'caption=Файл, width=50%, mandatory');
        $this->FLD('date', 'date', 'caption=Дата, width=50%');
        $this->FLD('total', 'double(decimals=2)', 'caption=Сума, width=50%');
        $this->FLD('keywords', 'text', 'caption=Описание, width=100%');
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни, input=none');
        
        $this->setDbUnique('dataId');
    } 

    
    /**
     * 
     */
    static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {   
        $tpl->replace(log_Documents::getSharingHistory($data->rec->containerId, $data->rec->threadId), 'shareLog');
    }
    
    
    /**
     * 
     * 
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Манупулатора на файла
        $fileHnd = Request::get('fh');
        
        // Ако създаваме документа от файл
        if (($fileHnd) && (!$data->form->rec->id)) {
            
            // Ескейпваме файл хендлъра
            $fileHnd = $mvc->db->escape($fileHnd);
            
            // Изискаваме да има права за сваляне
            fileman_Files::requireRightFor('download', $fileHnd);
            
            // Попълваме откритите ключови думи
            $data->form->setDefault('keywords', self::getKeywords($fileHnd));    
            
            // Файла да е избран по подразбиране
            $data->form->setDefault('fileHnd', $fileHnd);
        }
        
        // Ако създаваме нов
        if (!$data->form->rec->id) {
            
            // Вземаме от сесията id' то на текущата папка
            $currFolderId = Mode::get('lastfolderId');
            if ($currFolderId) {
                
                // Задаваме id' то на текущата папка
                $data->form->rec->folderId = $currFolderId;
            }
        }
    }
    
    
    /**
     * 
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        // Ако формата е изпратена
        if ($form->isSubmitted()) {
            
            // id от fileman_Data
            $dataId = fileman_Files::fetchByFh($form->rec->fileHnd, 'dataId');
            
            // Проверяваме да няма създаден документ за съответния запис
            if ($dRec = static::fetch("#dataId = '{$dataId}'")) {
                
                // Съобщение за грешка
                $error = "|Има създаден документ за файла|*";
                
                // Ако имаме права за single на документа
                if ($mvc->haveRightFor('single', $dRec)) {
                    
                    // Заглавието на документа
                    $title = static::getVerbal($dRec, 'title');
                    
                    // Създаваме линк към single'a на документа
                    $link = ht::createLink($title, array($mvc, 'single', $dRec->id));    
                    
                    // Добавяме към съобщението за грешка самия линк
                    $error .= ": {$link}";
                }
                
                // Задаваме съобщението за грешка
                $form->setError('fileHnd', $error);    
            }
        }
    }

    
    /**
     * 
     */
    function on_BeforeSave(&$invoker, &$id, &$rec)
    {
        // id от fileman_Data
        $dataId = fileman_Files::fetchByFh($rec->fileHnd, 'dataId');
        $rec->dataId = $dataId;
    }
    
    
    /**
     * 
     */
    function on_BeforeRenderSingle($mvc, $tpl, &$data)
    {
        if ($data->rec->type == 'empty') {
            unset($data->row->type);
        }
    }
    
    
    /**
     * Връща ключовите думи на документа
     * @todo Да се реализира
     * 
     * @return;
     */
    static function getKeywords($fileHnd)
    {
        
        return "test {$fileHnd}";
    }  
    
    
    /**
     * 
     */
    function getDocumentRow($id)
    {
        // Вземаме записите
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        $row->title = $this->getVerbal($rec, 'title');

        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;

        return $row;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Documents', 'Файлове във входящите документи', NULL, '300 MB', 'user', 'user');
    }
}
