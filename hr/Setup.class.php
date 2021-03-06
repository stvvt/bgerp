<?php

/**
 * Начален номер на фактурите
 */
defIfNot('HR_EC_MIN', '1');


/**
 * Краен номер на фактурите
*/
defIfNot('HR_EC_MAX', '10000');



/**
 * class dma_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DMA
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'hr_EmployeeContracts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Човешки ресурси";

    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
    		'HR_EC_MIN'        => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Долна граница'),
    		'HR_EC_MAX'        => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Горна граница'),
    
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
   		    'hr_Departments',
            'hr_WorkingCycles',
            'hr_WorkingCycleDetails',
            'hr_Shifts',
            'hr_ShiftDetails',
            'hr_Professions',
			'hr_Positions',
            'hr_ContractTypes',
            'hr_EmployeeContracts',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'hr';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.31, 'Персонал', 'HR', 'hr_EmployeeContracts', 'default', "ceo, hr"),
        );
    
    
    
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {  
    	$html = parent::install(); 
    	 
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('humanResources', 'Прикачени файлове в човешки ресурси', NULL, '1GB', 'user', 'hr');
        
        return $html;
    }
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}