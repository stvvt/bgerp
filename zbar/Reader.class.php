<?php


/**
 * Клас 'zbar_Reader' - Прочитана на баркодове от файл
 *
 * @category  vendors
 * @package   zbar
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class zbar_Reader
{

    
	/**
     * Връща баркодовете във файла
     * 
     * @param fileHnd - Манупулатор на файла, в който ще се търсят баркодове
     * 
     * @return array $barcodesArr - Масив с типовете и баркодовете във файла
     */
    static function getBarcodesFromFile($fh)
    {
        // Масива с намерените баркодове
        $barcodesArr = array();
        
        // Екстрактваме файла и вземаме пътя до екстрактнатия файл
        $filePath = fileman::extract($fh);
        
        if (!$filePath) return $barcodesArr;
        
        // Изпълняваме командата за намиране на баркодове
        exec("zbarimg " . escapeshellarg($filePath), $allBarcodesArr, $errorCode);
        
        // Изтриване на временния файл
        fileman::deleteTempPath($filePath);
        
        if (($errorCode !== 0) && ($errorCode !== 4)) {
            
            log_System::add('zbar_Reader', "Грешка (№{$errorCode}) при извличане на баркод от URL - '{$filePath}'", NULL, 'debug');
            
            throw new fileman_Exception('Възникна грешка при обработка.');
        }
        
        // Ако има окрит баркод
        if ((is_array($allBarcodesArr)) && count($allBarcodesArr)) {
            
            // Обикаляме намерените баркодове
            foreach ($allBarcodesArr as $key => $barcode) {
                
                // Разделяме типа на баркода от съдържанието му
                $explodeBarcodeArr = explode(':', $barcode, 2);
                
                if (!is_object($barcodesArr[$key])) {
                    $barcodesArr[$key] = new stdClass();
                }
                
                // Записваме намерените резултатис
                $barcodesArr[$key]->type = $explodeBarcodeArr[0];
                $barcodesArr[$key]->code = $explodeBarcodeArr[1];
            }
        }
        
        return $barcodesArr;
    }
}