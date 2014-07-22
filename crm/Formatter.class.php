<?php




/**
 * Линкове за телефонни номера и факсове
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_Formatter extends core_Manager
{
   
	
    /**
     * Заглавие
     */
    var $title = "Линкове на телефон и факс";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_State2,
    				 plg_Rejected, plg_Search, plg_Translate';

    
    /**
     * Права
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	
    }

    
    /**
     * Рендиране на телефонен номер
     * 
     * @param drdata_PhoneType $numbers
     * @param string $prefix
     * @param int $countryId
     */
    public static function renderTel($numbers, $prefix = NULL, $countryId = NULL)
    {
    	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        // Иконата на класа
        $icon = sbf("img/16/telephone2.png", '', $isAbsolute);
      		
	    $PhonesVerbal = cls::get('drdata_PhoneType');
	        		
	    // парсирваме всеки телефон
	    $parsTel = $PhonesVerbal->toVerbal($numbers);
        	
        if (Mode::is('screenMode', 'wide') && $prefix != NULL) {
        	        	
        	$res = "<span class = 'linkWithIcon' style = 'background-image:url({$icon})'>" . $prefix. " ". $parsTel . "</span>";
        } elseif (Mode::is('screenMode', 'narrow') && $prefix != NULL) {
        	$res = $prefix. "<span class='communication'>" . " ". $parsTel ."</span>";
        } else {
        	$res = "<span class = 'linkWithIcon' style = 'background-image:url({$icon})'>". $parsTel ."</span>";
        }
 
        return $res;
    }
 

    /**
     * Рендиране на факс номер
     * 
     * @param drdata_PhoneType $numbers
     * @param string $prefix
     * @param int $countryId
     */
    public static function renderFax($numbers, $prefix = NULL, $countryId = NULL)
    {
    	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        // Иконата на класа
        $icon = sbf("img/16/fax2.png", '', $isAbsolute);
        	
    	if (Mode::is('screenMode', 'wide') && $prefix != NULL) {
     	
        	$res = "<span class = 'linkWithIcon' style = 'background-image:url({$icon})'>" . $prefix. " ". $numbers ."</span>";
        	
        } elseif (Mode::is('screenMode', 'narrow') && $prefix != NULL) {
        	$res = $prefix. "<span class='communication'>" . " ". $numbers ."</span>";
        } else {
        	$res = "<span class = 'linkWithIcon' style = 'background-image:url({$icon})'>". $numbers ."</span>";
        }
   
        return $res;
    } 

    
	/**
     * Рендиране на телефонен номер
     * 
     * @param drdata_PhoneType $numbers
     * @param string $prefix
     * @param int $countryId
     */
    public static function renderMob($numbers, $prefix = NULL, $countryId = NULL)
    {
    	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        // Иконата на класа
        $icon = sbf("img/16/mobile2.png", '', $isAbsolute);
      		
	    $PhonesVerbal = cls::get('drdata_PhoneType');
	        		
	    // парсирваме всеки телефон
	    $parsTel = $PhonesVerbal->toVerbal($numbers);
        	
        if (Mode::is('screenMode', 'wide') && $prefix != NULL) {
        	        	
        	$res = "<span class = 'linkWithIcon' style = 'background-image:url({$icon})'>" . $prefix. " ". $parsTel . "</span>";
        } elseif (Mode::is('screenMode', 'narrow') && $prefix != NULL) {
        	$res = $prefix. "<span class='communication'>" . " ". $parsTel ."</span>";
        } else {
        	$res = "<span class = 'linkWithIcon' style = 'background-image:url({$icon})'>". $parsTel ."</span>";
        }
 
        return $res;
    }
}