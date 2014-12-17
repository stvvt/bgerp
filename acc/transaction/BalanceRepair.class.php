<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа mp_ConsumptionNotes
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_BalanceRepair
{
	
	
	/**
	 * Запис на баланса
	 */
	private $balanceRec;
	
	
	/**
	 * Дата
	 */
	private $date;
	
	
	/**
	 * Сума
	 */
	private $amount699 = 0;
	
	
	/**
	 * Сума
	 */
	private $amount799 = 0;
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function getTransaction($id)
	{
		// Извличане на мастър-записа
		expect($rec = $this->class->fetchRec($id));
	
		$this->balanceRec = acc_Balances::fetch($rec->balanceId);
		$pRec = acc_Periods::fetch($this->balanceRec->periodId);
		$this->date = acc_Periods::forceYearItem($rec->valior);
		
		$result = (object)array(
				'reason' => "Счетоводна разлика №{$rec->id}",
				'valior' => $pRec->end,
				'totalAmount' => NULL,
				'entries' => array()
		);
	
		// Ако има ид
		if($rec->id){
			
			// За всяка сметка в детайла
			$dQuery = acc_BalanceRepairDetails::getQuery();
			$dQuery->where("#repairId = {$rec->id}");
			while($dRec = $dQuery->fetch()){
				
				// Взимаме и записите
				$entries = $this->getEntries($dRec, $result->totalAmount);
				if(count($entries)){
					
					// Обединяваме тези записи с общите
					$result->entries = array_merge($result->entries, $entries);
				}
			}
		}

		// Ако има суми в 699 и в 799, приспадаме по-малката за да има само едно прехвърляне в 123
		if($this->amount699 != 0 && $this->amount799 != 0){
			$min = min(array(abs($this->amount699), abs($this->amount799)));
				
			$result->entries[] = array('amount' => abs($min), 'debit'  => array('799'), 'credit' => array('699'), 'reason' => 'Приспадане на грешките от закръгляния');
				
			$this->amount799 += abs($min);
			$this->amount699 -= abs($min);
				
			$result->totalAmount += abs($min);
		}
		
		// Ако има крайно салдо по 699, прехвърляме го в 123
		if($this->amount699 != 0){
			$result->entries[] = array('amount' => abs($this->amount699),
					'debit'  => array('123', $this->date->year),
					'credit' => array('699'),
					'reason' => 'Извънредни разходи от закръгляния');
		
			$result->totalAmount += abs($this->amount699);
		}
		
		// Ако има крайно салдо в 799, прехвърляме го в 123
		if($this->amount799 != 0){
			$result->entries[] = array('amount' => abs($this->amount799),
					'debit' => array('799'),
					'credit'  => array('123', $this->date->year),
						
					'reason' => 'Извънредни приходи от закръгляния');
		
			$result->totalAmount += abs($this->amount799);
		}
		
		return $result;
	}
	
	
	/**
	 * Връща ентритата 
	 */
	private function getEntries($dRec, &$total)
	{
		$entries = array();
		$sysId = acc_Accounts::fetchField($dRec->accountId, 'systemId');
		$bQuery = acc_BalanceDetails::getQuery();
		acc_BalanceDetails::filterQuery($bQuery, $this->balanceRec->id, $sysId);
		$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
		
		// За всеки запис
		while($bRec = $bQuery->fetch()){
			$continue = TRUE;
				
			$blAmount = $blQuantity = NULL;
			
			// Ако крайното салдо и к-во са в допустимите граници
			foreach (array('Quantity', 'Amount') as $fld){
				if(!empty($dRec->{"bl{$fld}"})){
					$var = &${"bl{$fld}"};
					$var = $bRec->{"debit{$fld}"} - $bRec->{"credit{$fld}"};
						
					if($var != 0 && $var > -1 * $dRec->{"bl{$fld}"} && $var <= $dRec->{"bl{$fld}"}){
						$continue = FALSE;
					}
				}
			}
				
			// Ако не са продължаваме
			if($continue) continue;
			
			$ourSideArr = array($sysId, $bRec->ent1Id, $bRec->ent2Id, $bRec->ent3Id);
			
			$entry = array('amount' => abs($blAmount));
			$total += abs($blAmount);
			
			if(!is_null($blQuantity)){
				$ourSideArr['quantity'] = abs($blQuantity);
			}
				
			// Ако салдото е отрицателно отива като приход
			if($blAmount < 0){
				$entry['debit'] = $ourSideArr;
				$entry['credit'] = array('799');
				
				$this->amount799 += $entry['amount'];
			} else {
				
				// Ако салдото е положително отива като разход
				$entry['debit'] = array('699');
				$entry['credit'] = $ourSideArr;
				
				$this->amount699 += $entry['amount'];
			}
			
			$entry['reason'] = 'Разлики от закръгляния';
			$entries[] = $entry;
		}
		
		// Връщаме ентритата
		return $entries;
	}
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function finalizeTransaction($id)
	{
		$rec = $this->class->fetchRec($id);
		$rec->state = 'active';
	
		if($id = $this->class->save($rec)) {
            $this->class->invoke('AfterActivation', array($rec));
        }
        
        return $id;
	}
}