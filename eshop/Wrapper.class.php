<?php



/**
 * Клас 'eshop_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'eshop'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class eshop_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('eshop_Groups', 'Групи', 'ceo,eshop');
        $this->TAB('eshop_Products', 'Продукти', 'ceo,eshop');
    }
}