<?php


/**
 * Мениджър на лични карти
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_IdCards extends core_Detail
{
	
	
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'personId';

    
    /**
     * Заглавие
     */
    var $title = 'Лични карти';

    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Лична карта';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'crm_Wrapper,plg_RowTools2';
    
    
    /**
     * Текущ таб
     */
    var $currentTab = 'Лица';
    
    
    /**
     * Кой може да редактира
     */
    var $canEdit = 'powerUser';


    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent');
        $this->FLD('idCardNumber', 'varchar(16)', 'caption=Номер');
        $this->FLD('idCardIssuedOn', 'date', 'caption=Издадена на');
        $this->FLD('idCardExpiredOn', 'date', 'caption=Валидна до');
        $this->FLD('idCardIssuedBy', 'varchar', 'caption=Издадена от');

        $this->setDbUnique('personId');
	}
    
	
	/**
	 * Подготовка за показване в указателя
	 */
    public static function prepareIdCard($data)
    {
        $data->TabCaption = 'ЛК';

        expect($data->masterId);
        
        if(!$data->IdCard) {
            $data->IdCard = new stdClass();
        }

        $data->IdCard->rec = static::fetch("#personId = {$data->masterId}");
        if ($data->IdCard->rec) {
            $data->IdCard->row = static::recToVerbal($data->IdCard->rec);    
        }
        $data->canChange = static::haveRightFor('edit');
    }
    
    
    /**
     * Рендиране на показването в указателя
     */
    public static function renderIdCard($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        
        $tpl->append(tr('Лична карта'), 'title');        

        if ($data->canChange && !Mode::is('printing')) {
            
            $rec = $data->IdCard->rec;

            if ($rec->idCardNumber || $rec->idCardIssuedOn || $rec->idCardExpiredOn || $rec->idCardIssuedBy) {
                $url = array(get_called_class(), 'edit', $rec->id, 'ret_url' => TRUE);
                $idCardTpl = new ET(getFileContent('crm/tpl/IdCard.shtml'));
                $idCardTpl->placeObject($data->IdCard->row);
            } else {
                $idCardTpl = new ET(tr('Няма данни'));
                $url = array(get_called_class(), 'add', 'personId' => $data->masterId, 'ret_url' => TRUE);
            }
            
            if($data->masterMvc->haveRightFor('edit', $data->masterId)){
            	$img = "<img src=" . sbf('img/16/edit.png') . " width='16' height='16'>";
	            $tpl->append(
	                ht::createLink(
	                    $img, $url, FALSE,
	                    'title=Промяна ЛК'
	                ),
	                'title'
	            );
            }
        }
        
        $tpl->append($idCardTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$conf = core_Packs::getConfig('crm');
    	
        $form = $data->form;
        
        // За да гарантираме релацията 1:1
        $form->rec->id = $mvc->fetchField("#personId = {$form->rec->personId}", 'id');

        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }

        $mvrQuery = bglocal_Mvr::getQuery();

        $mvrSug[''] = '';
        
        while($mvrRec = $mvrQuery->fetch()) {
            $mvrName = 'МВР - ';
            $mvrName .= $mvrRec->city;
            $mvrSug[$mvrName] = $mvrName;
        }

        $form->setSuggestions('idCardIssuedBy', $mvrSug);

        $data->form->title = 'Лична карта на |*' .  $mvc->Master->getVerbal($data->masterRec, 'name');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'edit' && isset($rec)){
    		$res = $mvc->getRequiredRoles('add', $rec);
    	}
    }
}