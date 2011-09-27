<?php


/**
 * Клас 'plg_State' - Поддръжка на поле 'state' за състояние на ред
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_State extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        if (!$invoker->fields['state']) {
            $invoker->FLD('state',
            'enum(draft=Чернова,pending=Чакащо,active=Активирано,' .
            'opened=Отворено,waiting=Чакащо,closed=Приключено,hidden=Скрито,rejected=Оттеглено,' .
            'stopped=Спряно,wakeup=Събудено,free=Освободено)',
            'caption=Състояние,column=none');
        }
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, $fields = NULL)
    {
        if (!$rec->state) {
            $rec->state = 'draft';
        }
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$invoker, &$row, &$rec)
    {
        $row->ROW_ATTR = " class='state-{$rec->state}' ";
    }


    
    /**
     * Поставя класа за състоянието на единичния изглед
     */
    function on_AfterRenderSingleTitle($mvc, $res, $data)
    {
        $res = new ET("<div style='padding:5px;' class='state-{$data->rec->state}'>[#1#]</div>", $res);
    }
}