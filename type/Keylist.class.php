<?php

/**
 * Клас  'type_Keylist' - Списък от ключове към редове от MVC модел
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Keylist extends core_Type {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'text';
    
    
    /**
     *  @todo Чака за документация...
     */
    function toVerbal_($value)
    {
        if(!$value) return NULL;
        

        $vals = explode($value{0}, $value);
        
        foreach($vals as $v) {
            if($v) {
                $res .= ($res?", ":'') . $this->getVerbal($v);
            }
        }

        return $res;
    }
    
    
    /**
     *  Връща вербалната стонкост на k
     */
    function getVerbal($k)
    {
        if(! round($k) > 0) return '';
        
        if($this->params['mvc']) {
            
            $mvc = &cls::get($this->params['mvc']);
            
            if(($part = $this->params['select']) && $part != '*') {
                
                $rec = $mvc->fetch($k);

                $res = $mvc->getVerbal($rec, $part);
               
                return $res;
            } else {
                $value = $mvc->getTitleById($k);
            }
        } elseif($this->params['function']) {
        
        } elseif($this->suggestions) {
            $value = $this->suggestions[$k];
        }
        
        return $value;
    }
    
    
    /**
     * Ако получи списък, вместо keylist, и в същото време
     * има select = конкретно поле от и mvc
     * @todo: да се направи конвертирането
     */
    function toMysql($value, $db)
    {
        return parent::toMysql($value, $db);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
    {
        // Ако няма списък с предложения - установяваме го
        if(!$this->suggestions) {
            if($select = $this->params['select']) {
                $mvc = &cls::get($this->params['mvc']);
                $query = $mvc->getQuery();
                
                if($groupBy = $this->params['groupBy']) {
                    $query->orderBy("#{$groupBy}")
                          ->show($groupBy);  
                }
                
                if($select != "*") {
                    $query->show($select)
                          ->show('id')
                          ->orderBy($select);
                }
               
                // Ако имаме метод, за подготвяне на заявката - задействаме го
                if($onPrepareQuery = $this->params['prepareQuery']) {
                    cls::callFunctArr($onPrepareQuery, array($this, $query));
                }
                
                while($rec = $query->fetch()) {

                    if($groupBy) {
                        if($group != $rec->{$groupBy}) {
                            $this->suggestions[$rec->id . '_group']->title = $mvc->getVerbal($rec, $groupBy);
                            $this->suggestions[$rec->id . '_group']->group = TRUE;
                            $group = $rec->{$groupBy};
                        }
                    }

                    if($select != "*") {
                        $this->suggestions[$rec->id] = $mvc->getVerbal($rec, $select);
                    } else {
                        $this->suggestions[$rec->id] = $mvc->getTitleById($rec->id);
                    }

                }
            }
        }
        
        if(!$value) {
            $values = array();
        } else {
            $values = explode($value{0}, trim($value, $value{0}));
        }
        
        $attr['type'] = 'checkbox';
        
        // Определяме броя на колоните, ако не са зададени.
        $col = $this->params['columns']?$this->params['columns']:
        min(  ($this->params['maxColumns']?$this->params['maxColumns']:4),
        round(sqrt(max(0, count($this->suggestions)+1))));

        $tpl = new ET("\n<table class='keylist'>[#OPT#]\n</table>");
         
        $i = 0; $html = ''; $trOpen = TRUE;
        
        if(count($this->suggestions)) {
            foreach($this->suggestions as $key => $v) {
                
                // Ако имаме група, правим ред и пишем името на групата
                if(is_object($v) && $v->group) {
                    if($trOpen) {
                        while($i > 0) {
                            $html .= "\n    <td></td>";
                            $i++;
                            $i = $i % $col;
                        }
                        $html .= '</tr>';
                    }
                    $html .= "\n<tr><td class='keylist-group' colspan='" . $col . "'>" . $v->title . "</td></tr>";
                    $i = 0;
                } else {
                    $attr['id'] = $name . "_" . $key;
                    $attr['name'] = $name . "[{$key}]";
                    $attr['value'] = $key;
                    
                    if(in_array($key, $values)) {
                        $attr['checked'] = 'checked';
                    } else {
                        unset($attr['checked']);
                    }
                    
                    $cb = ht::createElement('input', $attr);
                    $cb->append("<label  for=\"" . $attr['id'] . "\">{$v}</label>");
                    
                    if($i == 0) {
                        $html .= "\n<tr>";
                        $trOpen = TRUE;
                    }

                    $html .= "\n    <td>" . $cb->getContent() . "</td>";
                    
                    if($i == $col -1) {
                        $html .= "</tr>";
                        $trOpen = FALSE;
                    }
                    
                    $i++;
                    $i = $i % $col;
                }
            }
        } else {
            $html = '<tr><td></td></tr>';
        }
        
        $tpl->append($html, 'OPT');

        return $tpl;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function fromVerbal_($value)
    {
        if(!is_array($value) || !$value) return "";
        
        try {
        	$res = self::fromArray($value);
        } catch (Exception $e) {
        	$this->error = $e->getMessage();
        	$res = false;
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува от масив с индекси ключовете към keylist
     */
    static function fromArray($value)
    {
        if(count($value)) {
            foreach($value as $id => $val)
            {
                if(!ctype_digit(trim($id))) {
                    throw new Exception("Некоректен списък $id ");
                }
                
                $res .= "|" . $id;
            }
            $res = $res . "|";
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува keylist към масив
     */
    static function toArray($klist)
    {
        if(is_array($keylist)) {
            
            return $keylist;
        }
        
        if(empty($klist)) {
            return array();
        }
        
        $kArr = explode('|', $klist);
        
        foreach($kArr as $key) {
            if($key !== '') {
                $resArr[$key] = $key;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Проверява дали ключът присъства в дадения keylist
     */
    static function isIn($key, $list)
    {
        return strpos($list, '|' . $key . '|') !== FALSE;
    }
    
    /**
     * Добавя нов ключ към keylist
     *
     * @param mixed $klist масив ([`key`] => `key`) или стринг (`|key1|key2|...|`)
     * @param int $key ключ за добавяне
     * @return string `|key1|key2| ... |key|`
     */
	static function addKey($klist, $key)
	{
		$klist = self::toArray($klist);
		$klist[$key] = $key;
		$klist = self::fromArray($klist);
		
		return $klist;
	}
	
	
	/**
	 * Премахва ключ от keylist
	 *
     * @param mixed $klist масив ([`key`] => `key`) или стринг (`|key1|key2|...|`)
     * @param int $key ключ за премахване
     * @return string `|key1|key2| ... |key|`
	 */
	static function removeKey($klist, $key)
	{
		$klist = self::toArray($klist);
		if (isset($klist[$key])) {
			unset($klist[$key]);
		}
		$klist = self::fromVerbal($klist);
		
		return $klist;
	}
}