<?php

/**
 * Клас 'acc_setup_Lists'
 *
 * @category   Experta Framework
 * @package    acc
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_setup_Lists
{
    function loadData()
    {
        $csvFile = __DIR__ . "/csv/Lists.csv";
        
        $created = $updated = 0;

        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->num            = $csvRow [0];
                $rec->name           = $csvRow [1];
                $rec->regInterfaceId = core_Interfaces::fetchField(array("#name = '[#1#]'", $csvRow [2]), 'id');
                $rec->systemId       = $csvRow [3];
                $rec->state          = 'active';
                $rec->state          = 'active';
                
                if ($rec->id = acc_Lists::fetchField(array("#systemId = '[#1#]'", $rec->systemId), 'id')) {
                    $updated++;
                } elseif ($rec->id = acc_Lists::fetchField(array("#name = '[#1#]'", $rec->name), 'id')) {
                    $updated++;
                } else {
                    $created++;
                }

                acc_Lists::save($rec);                
            }
            
            fclose($handle);

            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";

            $res .= "Създадени {$created} нови номенклатури, обновени {$updated} съществуващи номенклатури.</li>";
        } else {

            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }

        return $res;
    }

}