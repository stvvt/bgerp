<?php
/**
 * Мениджър на бонуси
 *
 * @category   BGERP
 * @package    trz
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Бонуси
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class trz_Bonuses extends core_Manager
{
	/**
	 *  @todo Чака за документация...
	 */
	var $title = 'Бонуси';


	/**
	 *  @todo Чака за документация...
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
					trz_Wrapper';


	/**
	 * Права
	 */
	var $canRead = 'admin,trz';


	/**
	 *  @todo Чака за документация...
	 */
	var $canEdit = 'admin,trz';


	/**
	 *  @todo Чака за документация...
	 */
	var $canAdd = 'admin,trz';


	/**
	 *  @todo Чака за документация...
	 */
	var $canView = 'admin,trz';


	/**
	 *  @todo Чака за документация...
	 */
	var $canDelete = 'admin,trz';


	/**
	 *  @todo Чака за документация...
	 */
	var $listFields = 'tools=Пулт';


	/**
	 *  @todo Чака за документация...
	 */
	var $rowToolsField = 'tools';
	

	function description()
	{
	}
}