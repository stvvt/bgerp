<?php



/**
 * Мениджър на записите в баланс
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceDetails extends core_Detail
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'acc_Wrapper, Accounts=acc_Accounts, Lists=acc_Lists, plg_StyleNumbers, plg_AlignDecimals';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "accountNum, accountId, baseQuantity, baseAmount, 
                        debitQuantity, debitAmount,    creditQuantity, creditAmount, 
                        blQuantity, blAmount";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'balanceId';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    /**
     * Временен акумулатор при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    private $balance;
    
    
    /**
     * Временен акумолатор за извлечената история за перата
     * 
     * @var array
     */
    private $history;
    
    
    /**
     * Брой записи от историята на страница
     */
    var $listHistoryItemsPerPage = 30;
    
    
    /**
     *
     * Стратегии на сметките - използва се при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    private $strategies;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('balanceId', 'key(mvc=acc_Balances)', 'caption=Баланс');
        $this->FLD('accountId', 'key(mvc=acc_Accounts,title=title)', 'caption=Сметка->име,column=none');
        $this->EXT('accountNum', 'acc_Accounts', 'externalName=num,externalKey=accountId', 'caption=Сметка->#');
        $this->FLD('ent1Id', 'key(mvc=acc_Items,title=numTitleLink)', 'caption=Сметка->перо 1');
        $this->FLD('ent2Id', 'key(mvc=acc_Items,title=numTitleLink)', 'caption=Сметка->перо 2');
        $this->FLD('ent3Id', 'key(mvc=acc_Items,title=numTitleLink)', 'caption=Сметка->перо 3');
        $this->FLD('baseQuantity', 'double', 'caption=База->Количество');
        $this->FLD('baseAmount', 'double(decimals=2)', 'caption=База->Сума');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->Количество');
        $this->FLD('debitAmount', 'double(decimals=2)', 'caption=Дебит->Сума');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->Количество');
        $this->FLD('creditAmount', 'double(decimals=2)', 'caption=Кредит->Сума');
        $this->FLD('blQuantity', 'double', 'caption=Салдо->Количество');
        $this->FLD('blAmount', 'double(decimals=2)', 'caption=Салдо->Сума');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($mvc->isDetailed()) {
            // Детайлизиран баланс на конкретна аналитична сметка
            $mvc->prepareDetailedBalance($data);
        } else {
            // Обобщен баланс на синтетичните сметки
            $mvc->prepareOverviewBalance($data);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        if ($mvc->isDetailed() && $groupingForm = $mvc->getGroupingForm($data->masterId)) {
            $groupBy = array();
            
            if (!empty($groupBy)) {
                $mvc->doGrouping($data, $groupBy);
            }
        }
    }
    
    
    /**
     * Групира записите на баланс по зададен признак
     *
     * @param StdClass $data
     * @param array $by масив от признаци - $by[N]: признак за групиране по N-тата аналитичност
     * N = 1,2,3
     */
    private function doGrouping($data, $by)
    {
        $groupedRecs = array();
        $groupedIdx = array();
        $listRec = array();
        $registers = array();
        
        // Извличаме записите за номенклатурите, по които имаме групиране
        foreach (array_keys($by) as $i) {
            $listRec[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
        }
        
        foreach ($data->recs as $rec) {
            $f = array(1=>null, 2=>null, 3=>null);
            
            foreach ($by as $i=>$featureId) {
                $f[$i] = $this->Lists->getGroupOf(
                    $listRec[$i],
                    $rec->{"ent{$i}Id"},
                    $featureId
                );
            }
            
            $r = &$groupedIdx[$f[1]][$f[2]][$f[3]];
            
            if (!isset($r)) {
                $r->grouping1 = $f[1];
                $r->grouping2 = $f[2];
                $r->grouping3 = $f[3];
                $groupedRecs[] = &$r;
            }
            
            $r->baseQuantity += $rec->baseQuantity;
            $r->baseAmount += $rec->baseAmount;
            $r->debitQuantity += $rec->debitQuantity;
            $r->debitAmount += $rec->debitAmount;
            $r->creditQuantity += $rec->creditQuantity;
            $r->creditAmount += $rec->creditAmount;
            $r->blQuantity += $rec->blQuantity;
            $r->blAmount += $rec->blAmount;
        }
        
        $data->recs = $groupedRecs;
        
        // Конвертираме групираните записи към вербални стойности
        $data->rows = array();
        
        foreach ($data->recs as $rec) {
            $data->rows[] = $this->recToVerbal($rec, $data->listFields);
        }
    }
    
    
    /**
     * Подготовка за обобщен баланс на синтетичните сметки
     *
     * @param StdClass $data
     */
    private function prepareOverviewBalance($data)
    {
        $data->query->where('#ent1Id IS NULL AND #ent2Id IS NULL AND #ent3Id IS NULL');
        $data->query->orderBy('#accountNum', 'ASC');
        
        $data->listFields = array(
            'accountNum' => 'Сметка->#',
            'accountId' => 'Сметка->Име',
            'debitAmount' => 'Обороти->Дебит',
            'creditAmount' => 'Обороти->Кредит',
            'baseAmount' => 'Салдо->Начално',
            'blAmount' => 'Салдо->Крайно',
        );
    }
    
    
    /**
     * Подготовка за детайлизиран баланс на конкретна аналитична сметка,
     * евентуално групиран по зададени признаци
     *
     * @param StdClass $data
     */
    private function prepareDetailedBalance($data)
    { 
        // Кода по-надолу има смисъл само за детайлизиран баланс, очаква да има фиксирана
        // сметка.
        expect($this->Master->accountRec);
        
        $data->query->where("#accountId = {$this->Master->accountRec->id}");
        $data->query->where('#ent1Id IS NOT NULL OR #ent2Id IS NOT NULL OR #ent3Id IS NOT NULL');
        
        $groupingForm = $this->getGroupingForm($data->masterId);
        
        // Извличаме записите за номенклатурите, по които е разбита сметката
        $listRecs = array();
        $registers = array();
        
        foreach (range(1, 3) as $i) {
            if ($this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
            }
        }
        
        $data->listFields = array();
        $data->listFields['history'] = ' ';
        
        /**
         * Указва дали редом с паричните стойности да се покажат и колони с количества.
         *
         * Количествата има смисъл да се виждат само за сметки, на които поне една от
         * аналитичностите е измерима.
         *
         * @var boolean true - показват се и количества, false - не се показват
         */
        $bShowQuantities = FALSE;
        
        foreach ($listRecs as $i=>$listRec) {
            $bShowQuantities = $bShowQuantities || ($listRec->isDimensional == 'yes');
            
            if ($groupingForm && $groupingForm->rec->{"grouping{$i}"}) {
                //
                // Групиране по признак на текущата номенклатура
                //
                if ($groupingForm->rec->{"filter{$i}"}) {
                    //
                    // Има зададен филтър - не правим групиране, а само филтриране на
                    // обектите за които зададения признак има зададената във филтъра стойност
                    //
                    $data->listFields["ent{$i}Id"] = $listRec->name;
                    $itemIds = $this->Lists->getItemsByGroup(
                        $listRec,
                        $groupingForm->rec->{"grouping{$i}"},
                        $groupingForm->rec->{"filter{$i}"}
                    );
                    array_unshift($itemIds, -1);
                    $data->query->where("#ent{$i}Id IN (" . implode(',', $itemIds) . ")");
                } else {
                    $this->FNC("grouping{$i}", 'varchar', 'caption=' . $listRec->name);
                    $data->listFields["grouping{$i}"] = $listRec->name;
                }
            } else {
                $data->listFields["ent{$i}Id"] = $listRec->name;
                
                if (!$flag) {
                    // Не можем да използваме следните редове повече от веднъж, това е проблем
                    $flag = TRUE;
                    $data->query->EXT("ent{$i}Num", 'acc_Items', "externalName=num,externalKey=ent{$i}Id");
                    $data->query->orderBy("#ent{$i}Num", 'ASC');
                }
            }
        }
        
        if ($bShowQuantities) {
            $data->listFields += array(
                'baseQuantity' => 'Начално салдо->ДК->Количество',
                'baseAmount' => 'Начално салдо->ДК->Сума',
                'debitQuantity' => 'Обороти->Дебит->Количество',
                'debitAmount' => 'Обороти->Дебит->Сума',
                'creditQuantity' => 'Обороти->Кредит->Количество',
                'creditAmount' => 'Обороти->Кредит->Сума',
                'blQuantity' => 'Крайно салдо->ДК->Количество',
                'blAmount' => 'Крайно салдо->ДК->Сума',
            );
        } else {
            $data->listFields += array(
                'baseAmount' => 'Салдо->Начално',
                'debitAmount' => 'Обороти->Дебит',
                'creditAmount' => 'Обороти->Кредит',
                'blAmount' => 'Салдо->Крайно',
            );
        }
    }
    
    
    /**
     * Лека промяна в детайл-layout-а: лентата с инструменти е над основната таблица, вместо под нея.
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterRenderDetailLayout($mvc, &$res, $data)
    {
        $res = new ET("
            [#ListTable#]
            [#ListSummary#]
            [#ListToolbar#]
        ");
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    static function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if ($mvc->isDetailed()) {
            if ($form = $mvc->getGroupingForm($data->masterId)) {
                $tpl->append($form->renderHtml() . '<br />');
            }
        }
    }
    
    
    /**
     * Създаване и подготовка на формата за групиране.
     *
     * @param int $balanceId ИД на баланса, в контекста на който се случва това
     */
    private function getGroupingForm($balanceId)
    {
        
        /**
         * Помощна променлива за кеширане на веднъж създадената форма
         */
        static $form;
        
        if (isset($form)) {
            return $form;
        }
        
        $form = FALSE;
        
        expect($this->Master->accountRec);
        
        $listRecs = array();
        $registers = array();
        
        foreach (range(1, 3) as $i) {
            if ($this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
                
                if (empty($registers[$i]->features)) {
                    unset($listRecs[$i], $registers[$i]);
                }
            }
        }
        
        if (empty($listRecs)) {
            // Нито един регистър не предлага признаци за групиране
            return $form;
        }
        
        $form = cls::get('core_Form');
        
        $form->method = 'GET';
        $form->title = 'Групиране & Филтриране';
        
        $form->FLD("accId", 'int', 'silent,input=hidden');
        $form->input("accId", true);
        
        foreach ($listRecs as $i=>$listRec) {
            $register = $registers[$i];
            
            expect(!empty($register->features));
            
            $grouping = Request::get("grouping{$i}");
            $filter = Request::get("filter{$i}");
            
            $enum = $backUrl = array();
            
            $isGrouped = !empty($grouping);
            $isFiltered = $isGrouped && !empty($filter);
            
            if ($isFiltered) {
                $enum[] = $grouping . '=' .
                $register->features[$grouping]->title;
                $backUrl += array(
                    "grouping{$i}" => $grouping,
                );
            } else {
                foreach ($register->features as $featureId=>$featureObj) {
                    $enum[] = $featureId . '=' . $featureObj->title;
                }
            }
            
            if ($isGrouped) {
                $backUrl += array(
                    "accId" => $form->rec->accId,
                );
            }
            
            if (!empty($enum)) {
                array_unshift($enum, '');
                $form->FLD("grouping{$i}", 'enum(' . implode(',', $enum) . ')',
                    "silent,caption={$listRec->name} по,width=300px"
                );
            }
            
            if ($isFiltered) {
                $form->FLD("filter{$i}", "enum(,{$filter}=" . $register->features[$grouping]->titleOf($filter) . ")", 'silent',
                    array('caption'=>$register->features[$grouping]->title));
            }
        }
        
        $form->input(null, true);
        
        $form->toolbar->addSbBtn('Обнови', '', '', "id=btnGroup,class=btn-group");
        $form->toolbar->addBtn('Назад', array(
                $this->Master,
                'single',
                $balanceId,
            ) + $backUrl,
            'id=btnBack');
        
        return $form;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($row->accountId && strlen($row->accountNum) >= 3) {
            $accRec = $mvc->Accounts->fetch($rec->accountId, 'groupId1,groupId2,groupId3');
            
            if ($accRec->groupId1 || $accRec->groupId2 || $accRec->groupId3) {
                $row->accountId = ht::createLink($row->accountId,
                    array($mvc->master, 'single', $rec->balanceId, 'accId'=>$rec->accountId));
            }
        }
        
        $row->ROW_ATTR['class'] .= ' level-' . strlen($rec->accountNum);
        
        // Бутон за детайлизиран преглед на историята
        $histImg = ht::createElement('img', array('src' => sbf('img/16/view.png', '')));
        $row->history = ht::createLink($histImg, array('acc_BalanceDetails', 'History', $rec->id), NULL, 'title=Подробен преглед');
        $row->history = "<span style='margin:0 4px'>{$row->history}</span>";
        
        if (!$mvc->isDetailed()) {
            return;
        }
        
        $groupingRec = $mvc->getGroupingForm($rec->balanceId)->rec;
        
        foreach (range(1, 3) as $i) {
            if (property_exists($row, "grouping{$i}")) {
                if (!empty($row->{"grouping{$i}"})) {
                    if ($this->Master->accountRec->{"groupId{$i}"}) {
                        $listRec = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
                        $featureId = $groupingRec->{"grouping{$i}"};
                        $featureObj = $register->features[$featureId];
                        $row->{"grouping{$i}"} =
                        ht::createLink(
                            $featureObj->titleOf($row->{"grouping{$i}"}),
                            array_merge(
                                getCurrentUrl(),
                                array("filter{$i}" => $rec->{"grouping{$i}"})
                            )
                        );
                    }
                } else {
                    $row->{"grouping{$i}"} = '<i>Други</i>';
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function isDetailed()
    {
        return !empty($this->Master->accountRec);
    }
    

    /**
     * Записва баланса в таблицата
     */
    function saveBalance($balanceId)
    {
		if(count($this->balance)) {
			foreach ($this->balance as $accId => $l0) {
				foreach ($l0 as $ent1 => $l1) {
					foreach ($l1 as $ent2 => $l2) {
						foreach ($l2 as $ent3 => $rec) {
							$rec['balanceId'] = $balanceId;
							$this->save((object)$rec);
						}
					}
				}
			}
		}
        
        unset($this->balance, $this->strategies);
    }
    
    
    /**
     * Зарежда в сингълтона баланса с посоченото id
     */
    function loadBalance($balanceId, $accs = NULL, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {  
        $query = $this->getQuery();
        
        static::filterQuery($query, $balanceId, $accs, $itemsAll, $items1, $items2, $items3);
        $query->where('#blQuantity != 0 OR #blAmount != 0');
        
        while ($rec = $query->fetch()) { 
            $accId = $rec->accountId;
            $ent1Id = !empty($rec->ent1Id) ? $rec->ent1Id : null;
            $ent2Id = !empty($rec->ent2Id) ? $rec->ent2Id : null;
            $ent3Id = !empty($rec->ent3Id) ? $rec->ent3Id : null;
            
            if ($strategy = $this->getStrategyFor($accId, $ent1Id, $ent2Id, $ent3Id)) {
               
            	// "Захранваме" обекта стратегия с количество и сума
                $strategy->feed($rec->blQuantity, $rec->blAmount);
            }
            
            $b = &$this->balance[$accId][$ent1Id][$ent2Id][$ent3Id];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            $b['baseQuantity'] += $rec->blQuantity;
            $b['baseAmount'] += $rec->blAmount;
            $b['blQuantity'] += $rec->blQuantity;
            $b['blAmount'] += $rec->blAmount;
        }
    }
    
    
    /**
     * Изчислява стойността на счетоводен баланс за зададен период от време.
     *
     * @param string $from дата в MySQL формат
     * @param string $to дата в MySQL формат
     */
    function calcBalanceForPeriod($from, $to)
    {
        $JournalDetails = &cls::get('acc_JournalDetails');
        
        $query = $JournalDetails->getQuery();
        acc_JournalDetails::filterQuery($query, $from, $to);
        
        while ($rec = $query->fetch()) {
            $this->calcAmount($rec);
            $this->addEntry($rec, 'debit');
            $this->addEntry($rec, 'credit');
        }
        
    }
    
    
    /**
     * Попълва с адекватна стойност с полето $rec->amount, в случай, че то е празно.
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     */
    private function calcAmount($rec)
    {
        $debitStrategy = $creditStrategy = NULL;
        
        // Намираме стратегиите на дебит и кредит с/ките (ако има)
        $debitStrategy = $this->getStrategyFor(
            $rec->debitAccId,
            $rec->debitItem1,
            $rec->debitItem2,
            $rec->debitItem3
        );
    	
        $creditStrategy = $this->getStrategyFor(
            $rec->creditAccId,
            $rec->creditItem1,
            $rec->creditItem2,
            $rec->creditItem3
        );
        
        if ($creditStrategy) {
            // Кредитната сметка има стратегия.
            // Ако е активна, извличаме цена от стратегията
            // Ако е пасивна - "захранваме" стратегията с данни;
            // (точно обратното на дебитната сметка)
            
        	
            switch ($this->Accounts->getType($rec->creditAccId)) {
                case 'active' :
                	if ($amount = $creditStrategy->consume($rec->creditQuantity)) {
                        $rec->amount = $amount;
                    }
                    break;
                case 'passive' :
                    $creditStrategy->feed($rec->creditQuantity, $rec->amount);
                    break;
            }
        }
        
        if ($debitStrategy) {
            // Дебитната сметка има стратегия.
            // Ако е активна, "захранваме" стратегията с данни;
            // Ако е пасивна - извличаме цена от стратегията
            
            switch ($this->Accounts->getType($rec->debitAccId)) {
                case 'active' :
                    $debitStrategy->feed($rec->debitQuantity, $rec->amount);
                    break;
                case 'passive' :
                    if ($amount = $debitStrategy->consume($rec->debitQuantity)) {
                        $rec->amount = $amount;
                    }
                    break;
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function &getStrategyFor($accountId, $ent1Id, $ent2Id, $ent3Id)
    {
        $e1 = !empty($ent1Id) ? $ent1Id : null;
        $e2 = !empty($ent2Id) ? $ent2Id : null;
        $e3 = !empty($ent3Id) ? $ent3Id : null;
        
        $strategy = NULL;
        
        if (isset($this->strategies[$accountId][$e1][$e2][$e3])) {
            // Имаме вече създаден обект-стратегия
            $strategy = $this->strategies[$accountId][$e1][$e2][$e3];
        } elseif (isset($this->strategies[$accountId]) &&
            $this->strategies[$accountId] === false) {
            // Тази сметка вече е била "питана" за стратегия (дебитна или кредитна) и
            // резултатът е бил отрицателен. За това си спестяваме ново питане - гарантирано е, 
            // че отговорът отново ще бъде същият.
            $strategy = FALSE;
        } elseif ($strategy = $this->Accounts->createStrategyObject($accountId)) {
            // Има стратегия - записваме инстанцията й.
            $this->strategies[$accountId][$e1][$e2][$e3] = &$strategy;
        } else {
            // Няма стратегия. И това не зависи от перата. За да спестим бъдещи извиквания,
            // записваме false
            $this->strategies[$accountId] = FALSE;
        }
        
        return $strategy;
    }
    
    
    /**
     * Добавя дебитната или кредитната част на ред от транзакция (@see acc_JournalDetails)
     * в баланса
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     * @param string $type 'debit' или 'credit'
     */
    private function addEntry($rec, $type)
    {
        expect(in_array($type, array('debit', 'credit')));
        
        $quantityField = "{$type}Quantity";

        $sign = ($type == 'debit') ? 1 : -1;
        
        $accId = $rec->{"{$type}AccId"};
        
        $ent1Id = !empty($rec->{"{$type}Item1"}) ? $rec->{"{$type}Item1"} : NULL;
        $ent2Id = !empty($rec->{"{$type}Item2"}) ? $rec->{"{$type}Item2"} : NULL;
        $ent3Id = !empty($rec->{"{$type}Item3"}) ? $rec->{"{$type}Item3"} : NULL;
         
        if ($ent1Id != NULL || $ent2Id != NULL || $ent3Id != NULL) {
            
            $b = &$this->balance[$accId][$ent1Id][$ent2Id][$ent3Id];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            
            $this->inc($b[$quantityField], $rec->{$quantityField});
            $this->inc($b["{$type}Amount"], $rec->amount);
 
            $this->inc($b['blQuantity'], $rec->{$quantityField} * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
            
            // Ако е посочено за кои пера да се помнят записите
            if($this->historyFor && $accId == $this->historyFor['accId'] && $ent1Id == $this->historyFor['item1'] && $ent2Id == $this->historyFor['item2'] && $ent3Id == $this->historyFor['item3']){
            	$this->history[$rec->id] = array('id'            => $rec->id, 
            							         'docType'       => $rec->docType, 
            					                 'docId'         => $rec->docId,
            					                 "{$type}Amount" => $rec->amount,
            					                 $quantityField  => $rec->{$quantityField},
            					                 'blQuantity'    => $rec->{$quantityField} * $sign,
            					                 'blAmount'      => $rec->amount * $sign,
            					                 'reason'        => $rec->reason,
            					                 'valior'		 => $rec->valior);
            }
        }
       
        for ($accNum = $this->Accounts->getNumById($accId); !empty($accNum); $accNum = substr($accNum, 0, -1)) {
            if (!($accId = $this->Accounts->getIdByNum($accNum))) {
                continue;
            }
            
            $b = &$this->balance[$accId][null][null][null];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = NULL;
            $b['ent2Id'] = NULL;
            $b['ent3Id'] = NULL;
            
            $this->inc($b[$quantityField], $rec->{$quantityField});
            $this->inc($b["{$type}Amount"], $rec->amount);
            $this->inc($b['blQuantity'], $rec->{$quantityField} * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
        }
    }
    
    
    /**
     * Ако вторият аргумент е с празна (empty()) стойност - не прави нищо. В противен случай
     * увеличава стойността на първия аргумент със стойността на втория.
     *
     * Ако $v = '', $add = '', то след $v += $add, $v ще има стойност нула. Целта на този метод
     * е стойността на $v да остане непроменена (празна) в такива случаи.
     *
     * @param number $v
     * @param mixed $add
     */
    private function inc(&$v, $add)
    {
        if (!empty($add)) {
            $v += $add;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява ръчното манипулиране на записи
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if (!in_array($action, array('list', 'read'))) {
            $requiredRoles = 'no_one';
        }
    }


    /**
     * Компресира диапазона на id-tata
     */
    function cron_CompressIds()
    {
     //   set @id:=0;
     //   update mytable
     //   set id = (@id := @id + 1)
     //   order by id;
    }
    
    
    /**
     * Филтрира заявка към модела за показване на определени данни
     * 
     * @param core_Query $query - Заявка към модела
     * @param mixed $accs       - списък от систем ид-та на сметките
     * @param mixed $itemsAll   - списък от пера, за които може да са на произволна позиция
     * @param mixed $items1     - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2     - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3     - списък с пера, от които поне един може да е на трета позиция
     * @return array            - масив със всички извлечени записи
     */
	public static function filterQuery(core_Query &$query, $id, $accs = NULL, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {
    	expect($query->mvc instanceof acc_BalanceDetails);
    	
    	// Трябва да има поне една зададена сметка
    	$accounts = arr::make($accs);
    	
    	if(count($accounts) >= 1){
	    	foreach ($accounts as $sysId){
		    	$query->orWhere("#accountNum = {$sysId}");
		    }
    	}
    	
	    // ... само детайлите от последния баланс
	    $query->where("#balanceId = {$id}");
	    
	    // Перата които може да са на произволна позиция
    	$itemsAll = arr::make($itemsAll);
    	
    	if(count($itemsAll)){
    		foreach ($itemsAll as $itemId){
    			
    			// Трябва да инт число
    			expect(ctype_digit($itemId));
    			
    			// .. и перото да участва на произволна позиция
		    	$query->where("#ent1Id = {$itemId}");
		    	$query->orWhere("#ent2Id = {$itemId}");
		    	$query->orWhere("#ent3Id = {$itemId}");
    		}
    	}
    	
    	// Проверка на останалите параметри от 1 до 3
    	foreach (range(1, 3) as $i){
    		$var = ${"items{$i}"};
    		
    		// Ако е NULL продалжаваме
    		if(!$var) continue;
    		$varArr = arr::make($var);
    		
    		// За перата се изисква поне едно от тях да е на текущата позиция
    		$j = 0;
    		foreach($varArr as $itemId){
    			$or = ($j == 0) ? FALSE : TRUE;
    			$query->where("#ent{$i}Id = {$itemId}", $or);
    			$j++;
    		}
    	}
    }
    
    
    /**
     * Екшън за показване историята на перата
     */
    public function act_History()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($balanceRec = $this->Master->fetch($rec->balanceId));
    	$this->title = 'История на баланса';
    	
    	requireRole('ceo,acc');
    	
    	// Подготвяне на данните
    	$data = new stdClass();
    	$data->rec = $rec;
    	$data->id = $id;
    	$data->balanceRec = $balanceRec;
    	$data->fromDate = $balanceRec->fromDate;
    	$data->toDate = $balanceRec->toDate;
    	
    	// Подготовка на историята
    	$this->prepareHistory($data);
    	
    	// Рендиране на историята
    	$tpl = $this->renderHistory($data);
    	$tpl = $this->renderWrapping($tpl);
    	
    	// Връщаме шаблона
    	return $tpl;
    }
    
    
	/**
     * Изчислява стойността на счетоводен баланс за зададен период от време
     * за зададените сметки
     *
     * @param mixed $accs   - списък от систем ид-та на сметките
     * @param mixed $items1 - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2 - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3 - списък с пера, от които поне един може да е на трета позиция
     */
    function prepareDetailedBalanceForPeriod($from, $to, $accs = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL, $history = FALSE, $pager)
    {
        $JournalDetails = &cls::get('acc_JournalDetails');
        
        $query = $JournalDetails->getQuery();
        $cloneQuery = clone $query;
        $cloneQuery->show('id');
        
        // Филтриране на заявката да показва само записите от журнал за тази сметка
        acc_JournalDetails::filterQuery($query, $from, $to, $accs);
        
        // Филтриране на копието, за показване на записите за тези пера
        acc_JournalDetails::filterQuery($cloneQuery, $from, $to, $accs, $items1, $items2, $items3); 
        
        // Добавяне на странициране
        if($pager){
        	$pager->setLimit($cloneQuery);
        	
        	// Кои записи трябва да се показват
        	$displayedEntries = $cloneQuery->fetchAll();
        }
       
        /*
         * Изчисляване на сумите според стратегиите ако има, за да е всичко точно
         * са ни нужни нефилтрираните записи
         */
        while ($rec = $query->fetch()) {
            @$this->calcAmount($rec);
            $this->addEntry($rec, 'debit');
            $this->addEntry($rec, 'credit');
        }
        
        // Ако има записи, които трябва да се помнят, се проверява за всеки от тях
        // Дали присъства на страницата, ако не го махаме
        if(count($this->history) && count($displayedEntries)){
        	foreach ($this->history as $id => $rec){
        		if(!array_key_exists($id, $displayedEntries)){
        			unset($this->history[$id]);
        		}
        	}
        }
        
        if(count($this->history)){
        	 // Обръщаме историята в низходящ ред по дата, след изчисленията
        	$this->history = array_reverse($this->history);
        }
    }
    
    
    /**
     * Подготовка на историята за перара
     * 
     * @param stdClass $data
     */
    private function prepareHistory(&$data)
    {
    	$rec = &$data->rec;
    	$balanceRec = $data->balanceRec;
    	
    	// Подготвяне на данните на записа
    	$Date = cls::get('type_Date');
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	// Подготовка на филтъра
    	$this->prepareHistoryFilter($data);
    	
    	// Подготовка на вербалното представяне
    	$row = new stdClass();
    	$row->fromDate = $Date->toVerbal($data->fromDate);
    	$row->toDate = $Date->toVerbal($data->toDate);
    	$row->today = $Date->toVerbal(dt::now());
    	$row->accountId = acc_Accounts::getTitleById($rec->accountId);
    	$row->blAmount = $Double->toVerbal($rec->blAmount);
    	$row->blQuantity = $Double->toVerbal($rec->blQuantity);
    	$row->baseAmount = $Double->toVerbal($rec->baseAmount);
    	$row->baseQuantity = $Double->toVerbal($rec->baseQuantity);
    	
    	// Вербалните имена на избраните пера
    	foreach(range(1, 3) as $i){
    		$row->{"ent{$i}Id"} = acc_Items::getTitleById($rec->{"ent{$i}Id"});
    	}
    	
    	$data->row = $row;
    	
    	// Подготовка на пейджъра
    	$this->listItemsPerPage = $this->listHistoryItemsPerPage;
    	$this->prepareListPager($data);
    	
    	// Намиране на най-стария баланс можеш да послужи за основа на този
    	$balanceBefore = $this->Master->getBalanceBefore($data->fromDate);
    	
    	if($balanceBefore){
    		// Зареждаме баланса за посочения период с посочените сметки
    		$this->loadBalance($balanceBefore->id, $rec->accountNum, NULL, $rec->ent1Id, $rec->ent2Id, $rec->ent3Id);
    	}
    	
    	// Запомняне за кои пера ще показваме историята
    	$this->historyFor = array('accId' => $rec->accountId, 'item1' => $rec->ent1Id, 'item2' => $rec->ent2Id, 'item3' => $rec->ent3Id);
    	
    	// Извличане на всички записи към избрания период за посочените пера
    	$this->prepareDetailedBalanceForPeriod($data->fromDate, $data->toDate, $rec->accountNum, $rec->ent1Id, $rec->ent2Id, $rec->ent3Id, TRUE, $data->pager);
    	
    	// Нулевия ред е винаги началното салдо
    	$zeroRec = (object)array('docType' => NULL, 'docId' =>"Начално салдо: {$row->fromDate}", 'creditAmount' => NULL,'creditQuantity' => NULL,'debitAmount' => NULL,'debitQuantity' => NULL, 'blAmount' => "<b>" . $row->baseAmount . "</b>", 'blQuantity' => "<b>" . $row->baseQuantity . "<b>");
    	
    	// Събраното в $history са нужните ни записи
    	$data->recs = $this->history;
    	
    	// За всеки запис, обръщаме го във вербален вид
    	if(count($data->recs)){
    		$blQuantity = $blAmount = 0;
    		foreach ($data->recs as $jRec){
    			$blQuantity += $jRec['blQuantity'];
    			$blAmount += $jRec['blAmount'];
    			$data->rows[] = $this->getVerbalHistoryRow($jRec, $Double);
    		}
    	}
    	
    	// Добавяне на нулевия ред към историята
    	if(count($data->rows)){
    		array_unshift($data->rows, $zeroRec);
    	} else {
    		$data->rows = array($zeroRec);
    	}
    	
    	$row->blAmount2 = $Double->toVerbal($blAmount + $rec->baseAmount);
    	$row->blQuantity2 = $Double->toVerbal($blQuantity + $rec->baseQuantity);
    }
    
    
    /**
     * Подготвя филтъра на историята на перата
     */
    private function prepareHistoryFilter(&$data)
    {
    	$this->prepareListFilter($data);
    	$filter = &$data->listFilter;
    	$filter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$filter->view = 'horizontal';
    	$filter->FNC('from', 'date', 'caption=От,input,width=10em');
    	$filter->FNC('to', 'date', 'caption=До,input,width=10em');
    	$filter->showFields = 'from,to';
    	
    	$optionsFrom = array();
    	$optionsFrom[''] = ' ';
    	$optionsTo = $optionsFrom;
    	
    	// За начална и крайна дата, слагаме по пдоразбиране, датите на периодите
    	// за коиот има изчислени оборотни ведомости
    	$balanceQuery = acc_Balances::getQuery();
    	while($bRec = $balanceQuery->fetch()){
    		$bRow = acc_Balances::recToVerbal($bRec, 'periodId,id,fromDate,toDate,-single');
    		$optionsFrom[$bRec->fromDate] = $bRow->periodId . " ({$bRow->fromDate})";
    		$optionsTo[$bRec->toDate] = $bRow->periodId . " ({$bRow->toDate})";
    	}
    	
    	$filter->setSuggestions('from', $optionsFrom);
    	$filter->setSuggestions('to', $optionsTo);
    	
    	// Активиране на филтъра
        $filter->input();
        
        // Ако има изпратени данни
        if($filter->rec){
        	if($filter->rec->from){
        		$data->fromDate = $filter->rec->from;
        	}
        	
        	if($filter->rec->to){
        		$data->toDate = $filter->rec->to;
        	}
        }
    }
    
    
    /**
     * Подготовка на вербалното представяне на един ред от историята
     * 
     * @param stdClass $rec
     * @param type_Double $Double
     */
    private function getVerbalHistoryRow($rec, $Double)
    {
    	$arr['reason'] = $rec['reason'];
    	$arr['valior'] = $rec['valior'];
    	
    	// Ако има отрицателна сума показва се в червено
    	foreach (array('debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blQuantity', 'blAmount') as $fld){
    		$arr[$fld] = $Double->toVerbal($rec[$fld]);
    		if($rec[$fld] < 0){
    			$arr[$fld] = "<span style='color:red'>{$arr[$fld]}</span>";
    		}	
    	}
		
    	try{
    		$arr['docId'] = cls::get($rec['docType'])->getLink($rec['docId']);
    	} catch(Exception $e){
    		$arr['docId'] = tr("Проблем при показването");
    	}
    	
    	return (object)$arr;
    }
    
    
    /**
     * Рендиране на историята
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    private function renderHistory(&$data)
    {
    	$tpl = getTplFromFile('acc/tpl/SingleLayoutBalanceHistory.shtml');
    	$tpl->placeObject($data->row);
    	
    	$table = cls::get('core_TableView');
    	$data->listFields = array(
                'docId'          => 'Документ',
    			'valior'         => 'Вальор',
    			'reason'		 => 'Основание',
                'debitQuantity'  => 'Дебит->Количество',
                'debitAmount'    => 'Дебит->Сума',
                'creditQuantity' => 'Кредит->Количество',
                'creditAmount'   => 'Кредит->Сума',
                'blQuantity'     => 'Остатък->Количество',
                'blAmount'       => 'Остатък->Сума',
            );
        
        $data->rows[0]->ROW_ATTR = array('style' => 'background-color:#eee');
        
        $details = $table->get($data->rows, $data->listFields);
        foreach (array('blQuantity', 'blAmount') as $fld){
        	if($data->rec->$fld < 0){
        		$data->row->$fld = "<span style='color:red'>{$data->row->$fld}</span>";
        	}
        }
        
        $lastRow = new ET("<tr style='background-color:#eee;'><td colspan='7' style='text-align:right;padding-right:10px'>Крайно салдо: [#today#]</td><td><b>[#blQuantity#]</b></td><td><b>[#blAmount#]</b></td></tr>");
        if(haveRole('debug')){
        	$lastRow->append(new ET("<tr><td colspan='7' style='text-align:right;padding-right:10px'>Пресметнато: </td><td><b>[#blQuantity2#]</b></td><td><b>[#blAmount2#]</b></td></tr>"));
        }
        $lastRow->placeObject($data->row);
        
        $details->append($lastRow, 'ROW_AFTER');
        $tpl->replace($details, 'DETAILS');
        $tpl->append($this->renderListFilter($data), 'listFilter');
        if($data->pager){
        	$tpl->append($this->renderListPager($data));
        }
        
    	return $tpl; 
    }
}