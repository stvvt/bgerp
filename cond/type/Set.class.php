<?php


/**
 * Тип за параметър 'Множество'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Множество
 */
class cond_type_Set extends cond_type_Proto
{
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('options', 'text', 'caption=Конкретизиране->Опции,before=default,mandatory');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param cond_type_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(cond_type_Proto $Driver, embed_Manager $Embedder, &$data)
	{
		if(isset($data->form->rec->lastUsedOn)){
			$data->form->setReadOnly('options');
		}
	}
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$options = explode(PHP_EOL, $rec->options);
		foreach ($options as &$opt){
			$opt = trim($opt);
		}
			
		$options = array_combine($options, $options);
		$options = arr::fromArray($options);
			
		$Type = core_Type::getByName("set($options)");
		
		return $Type;
	}
}