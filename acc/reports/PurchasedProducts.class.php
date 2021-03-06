<?php



/**
 * Мениджър на отчети от Закупени продукти
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_reports_PurchasedProducts extends acc_reports_CorespondingImpl
{


	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'acc_PurchasedProductsReport';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Закупени продукти';


    /**
     * Дефолт сметка
     */
    public $baseAccountId = '321';


    /**
     * Кореспондент сметка
     */
    public $corespondentAccountId = '401';


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields ($mvc, core_FieldSet &$form)
    {
     
        // Искаме да покажим оборотната ведомост за сметката на касите
        $baseAccId = acc_Accounts::getRecBySystemId($mvc->baseAccountId)->id;
        $form->setDefault('baseAccountId', $baseAccId);
        $form->setHidden('baseAccountId');
        
        $corespondentAccId = acc_Accounts::getRecBySystemId($mvc->corespondentAccountId)->id;
        $form->setDefault('corespondentAccountId', $corespondentAccId);
        $form->setHidden('corespondentAccountId');
        
        $form->setHidden("orderField");
        $form->setHidden("side");
    }
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {

        $storePositionId = acc_Lists::getPosition($mvc->baseAccountId, 'store_AccRegIntf');
        $form->setHidden("feat{$storePositionId}");
        foreach (range(4, 6) as $i) {
            $form->setHidden("feat{$i}");
        }

        $articlePositionId = acc_Lists::fetchField("#systemId = 'catProducts'",'id');
        $storePositionId = acc_Lists::getPosition($mvc->baseAccountId, 'store_AccRegIntf');
         
        foreach(range(1, 3) as $i) {
            if ($form->rec->{"list{$i}"} == $articlePositionId) {

                $form->setDefault("feat{$i}", "*");
                $form->setField("feat{$i}", 'caption=Артикул');
            }
        }
        
        $form->setDefault("orderField", "blAmount");
        $form->setDefault("side", "all");
       
        $contragentPositionId = acc_Lists::getPosition($mvc->baseAccountId, 'cat_ProductAccRegIntf');
         
        $form->setDefault("feat{$contragentPositionId}", "*");
        $form->setHidden("feat{$contragentPositionId}");
    }
    
    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
        $tpl = getTplFromFile('acc/tpl/PurchaseReportLayout.shtml');
    
        if($this->innerForm->compare == 'no') {
            $tpl->removeBlock('summeryNew');
        }
    
        return $tpl;
    }
    
    
    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {
        //$tpl = $mvc->getReportLayout();
        
        $tpl->removeBlock('debit');
        $tpl->removeBlock('credit');
        $tpl->removeBlock('debitNew');
        $tpl->removeBlock('creditNew');
        $tpl->removeBlock('blName');

        if($mvc->innerForm->compare == 'no') {
            $tpl->removeBlock('summeryNew');
        }
    }
    


    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
        $innerState = &$this->innerState;

        unset($innerState->recs);
    }


    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
        $activateOn = "{$this->innerForm->to} 23:59:59";

        return $activateOn;
    }
    
    
    /**
     * Връща дефолт заглавието на репорта
     */
    public function getReportTitle()
    {
    	$explodeTitle = explode(" » ", $this->title);
    	
    	$title = tr("|{$explodeTitle[1]}|*");
    	 
    	return $title;
    }
    
    
    /**
     * Какви са полетата на таблицата
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {

        $form = $mvc->innerForm;
        $newFields = array();

        $data->listFields['item2'] = 'Контрагенти';
        $data->listFields['item3'] = 'Артикул';
        $data->listFields['blQuantity'] = 'Количество';
        $data->listFields['blAmount'] = 'Сума';
        $data->listFields['delta'] = 'Дял';

        // Кои полета ще се показват
        if($mvc->innerForm->compare != 'no'){
            $fromVerbalOld = dt::mysql2verbal($data->fromOld, 'd.m.Y');
    		$toVerbalOld = dt::mysql2verbal($data->toOld, 'd.m.Y');
    		$prefixOld = (string) $fromVerbalOld . " - " . $toVerbalOld;
    		
    		$fromVerbal = dt::mysql2verbal($form->from, 'd.m.Y');
    		$toVerbal = dt::mysql2verbal($form->to, 'd.m.Y');
    		$prefix = (string) $fromVerbal . " - " . $toVerbal;

    		$fields = arr::make("id=№,item2=Контрагенти,item3=Артикул,blQuantity={$prefix}->Количество,blAmount={$prefix}->Сума,delta={$prefix}->Дял,blQuantityNew={$prefixOld}->Количество,blAmountNew={$prefixOld}->Сума,deltaNew={$prefixOld}->Дял", TRUE);
    		$data->listFields = $fields;
        }
        
        $articlePositionId = acc_Lists::fetchField("#systemId = 'catProducts'",'id');
        foreach(range(1, 3) as $i) {
            if ($form->{"list{$i}"} == $articlePositionId) {
                 if($form->{"feat{$i}"} != "*") {
                     unset($data->listFields['item3']);
                 }
            }
        }
         
        unset($data->listFields['debitQuantity'],$data->listFields['debitAmount'],$data->listFields['creditQuantity'],$data->listFields['creditAmount']);
    }
}