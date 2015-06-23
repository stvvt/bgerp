<?php



/**
 * Мениджър на отчети от Приходи от продажби по продукти
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
class acc_SaleArticlesReport extends acc_BalanceReportImpl
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Приходи от продажби на Стоки и Продукти ';


    /**
     * Дефолт сметка
     */
    public $accountSysId = '701';


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_Form &$form)
    {

        // Искаме да покажим оборотната ведомост за сметката на касите
        $accId = acc_Accounts::getRecBySystemId($mvc->accountSysId)->id;
        $form->setDefault('accountId', $accId);
        $form->setHidden('accountId');

        // Дефолт периода е текущия ден
        $today = dt::today();

        $form->setDefault('from',date('Y-m-01', strtotime("-1 months", dt::mysql2timestamp(dt::now()))));
        $form->setDefault('to', $today);

        // Задаваме че ще филтрираме по перо
        $form->setDefault('action', 'group');
        $form->setHidden('orderField');
        $form->setHidden('orderBy');
    }


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $form->setHidden('action');

        foreach (range(1, 3) as $i) {

            $form->setHidden("feat{$i}");
            $form->setHidden("grouping{$i}");

        }

        $articlePositionId = acc_Lists::getPosition($mvc->accountSysId, 'cat_ProductAccRegIntf');

        $form->setDefault("feat{$articlePositionId}", "*");
    }


    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {
        $tpl->removeBlock('action');
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {

        unset($data->listFields['baseQuantity']);
        unset($data->listFields['baseAmount']);
        unset($data->listFields['debitQuantity']);
        unset($data->listFields['debitAmount']);
        unset($data->listFields['blQuantity']);
        unset($data->listFields['blAmount']);

        $data->listFields['creditQuantity'] = "Кредит->К-во";
        $data->listFields['creditAmount'] = "Кредит->Сума";

    }


    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
        
        $tpl = $this->getReportLayout();

        $tpl->replace($this->title, 'TITLE');
        $this->prependStaticForm($tpl, 'FORM');

        $tpl->placeObject($data->row);

        // Името на перото да се показва като линк
        if(count($data->rows)){
            $articlePositionId = acc_Lists::getPosition($this->accountSysId, 'cat_ProductAccRegIntf');
            foreach ($data->rows as $id => &$row){
                $articleItem = acc_Items::fetch($data->recs[$id]->{"ent{$articlePositionId}Id"}, 'classId,objectId');
                $row->{"ent{$articlePositionId}Id"} = cls::get($articleItem->classId)->getShortHyperLink($articleItem->objectId);
            }
        }

        $tableMvc = new core_Mvc;
        $tableMvc->FLD('creditAmount', 'int', 'tdClass=accCell');

        $table = cls::get('core_TableView', array('mvc' => $tableMvc));

        $tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');

        $data->summary->colspan = count($data->listFields);

        if($data->bShowQuantities ){
            $data->summary->colspan -= 4;
            if($data->summary->colspan != 0 && count($data->rows)){
                $beforeRow = new core_ET("<tr style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#creditAmount#]</b></td></tr>");
            }
        }

        if($beforeRow){
            $beforeRow->placeObject($data->summary);
            $tpl->append($beforeRow, 'ROW_BEFORE');
        }
        
        if($data->pager){
        	$tpl->append($data->pager->getHtml(), 'PAGER_BOTTOM');
        	$tpl->append($data->pager->getHtml(), 'PAGER_TOP');
        }
        
        return $tpl;
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
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    public function getExportFields ()
    {

        $exportFields['ent1Id']  = "Артикули";
        $exportFields['blAmount']  = "Кредит";

        return $exportFields;
    }
}