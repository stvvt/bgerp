<?php
/**
 * Помощен клас моделиращ дебитна или кредитна част на ред от счетоводна транзакция
 *
 * Използва се само от acc_journal_Entry.
 * 
 * @author developer
 * @see acc_journal_Entry
 */
class acc_journal_EntrySide
{
    /**
     * @var string
     */
    const DEBIT  = 'debit';
    
    
    /**
     * @var string
     */
    const CREDIT = 'credit';

    
    /**
     *
     * @var acc_journal_Account
     */
    protected $account;


    /**
     *
     * @var array
     */
    protected $items;


    /**
     *
     * @var float
     */
    protected $amount;


    /**
     *
     * @var float
     */
    protected $quantity;


    /**
     *
     * @var float
     */
    protected $price;

    
    /**
     * @var string
     */
    protected $type;


    /**
     * Конструктор
     *
     * @param array|object $data
     * @param string $type debit или credit
     */
    public function __construct($data, $type)
    {
        $this->init($data);
        $this->type = $type;
    }


    /**
     * Инициализира ред на транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     *
     * @param array|stdClass $data
     */
    public function initFromTransactionSource($data)
    {
        $data = (array)$data;
        
        expect($d = $data[$this->type], "Липсва {$this->type} част на транзакция", $data);
        
        $this->amount = $data['amount']; // Сума в основна валута
        
        if (array_key_exists('quantity', $d)) {
            $this->quantity = $d['quantity'];
            unset($d['quantity']);
        }
        
        // SystemID или обект-инстанция на сметката е винаги първия елемент
        $this->account = array_shift($d);
        
        if (!$this->account instanceof acc_journal_Account) {
            $this->account = new acc_journal_Account($this->account);
        }
        
        // Приемаме, че всичко останало в $d е пера.
        expect(count($d) <= 3, "{$this->type}: Макс 3 пера", $data);
        
        foreach ($d as $item) {
            if (empty($item)) {
                continue;
            }
            if (!($item instanceof acc_journal_Item)) {
                $item = new acc_journal_Item($item);
            }
            $this->items[] = $item;
        }
        
        $this->evaluate();
    }
    

    public function init($data)
    {
        $data = (object)$data;

        $this->amount   = isset($data->amount)   ? floatval($data->amount) : NULL;
        $this->quantity = isset($data->quantity) ? floatval($data->quantity) : NULL;
        $this->price    = isset($data->price)    ? floatval($data->price) : NULL;
        $this->account  = $data->account instanceof acc_journal_Account ? $data->account :
                                new acc_journal_Account($data->account);
        
        expect(!isset($this->quantity) || $this->quantity > 0, 'Зададено не-положително количество: ' . $this->quantity);

        $this->items = array();

        if (is_array($data->items)) {
            foreach ($data->items as $item) {
                $this->items[] = $item instanceof acc_journal_Item ? $item :
                                    new acc_journal_Item($item);
            }
        }
        
        // Изчисляване на незададената цена (price), количество (quantity) или сума (amount)
        $this->evaluate();
    }
    
    
    /**
     * Има ли зададена стойност поле на класа
     * 
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        if (!property_exists($this, $name)) {
            return FALSE;
        }
        
        if ($name == 'price') {
            return !is_null($this->getPrice());
        }
    
        return isset($this->{$name});
    }
    

    /**
     * Readonly достъп до полетата на обекта
     * 
     * @param string $name
     * @return mixed
     * @throws core_exception_Expect когато полето не е дефинирано в класа
     */
    public function __get($name)
    {
        expect(property_exists($this, $name), $name);

        if ($name == 'price') {
            return $this->getPrice();
        }
        
        return $this->{$name};
    }
    

    /**
     * Ще приеме ли сметката зададените пера?
     *
     * @see acc_journal_Account::accepts()
     *
     * @return boolean
     */
    public function checkItems()
    {
//         try {
            $this->account->accepts($this->items);
//         } catch (core_exception_Expect $ex) {
//             throw new core_exception_Expect("Грешка в {$this->type}" , $ex->args());
//         }
        
        return TRUE;
    }
    

    /**
     * Изчислява, ако е възможно, незададеното amount/quantity
     * 
     *  amount   = price * quantity, ако са зададени price и quantity
     *  quantity = amount / price, ако са зададени price и amount
     *
     *  В останалите случаи не прави нищо.
     */
    public function evaluate()
    {
        switch (true) {
            case isset($this->amount) && isset($this->quantity) && isset($this->price):
                break;
            case isset($this->quantity) && isset($this->price):
                $this->amount = $this->quantity * $this->price;
                break;
            case isset($this->amount) && isset($this->price):
                $this->quantity = $this->amount / $this->price;
                break;
        }
    }
    
    
    public function forceItems()
    {
        /* @var $item acc_journal_Item */
        foreach ($this->items as $i=>$item) {
            $item->force($this->account->{'groupId' . ($i+1)});
        }
    }
    
    
    /**
     * 
     * @return array
     */
    public function getData()
    {
        $type = $this->type;
        
        $rec = array(
            "{$type}AccId"    => $this->account->id,  // 'key(mvc=acc_Accounts,select=title,remember)',
            "{$type}Item1"    => isset($this->items[0]) ? $this->items[0]->id : NULL, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Item2"    => isset($this->items[1]) ? $this->items[1]->id : NULL, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Item3"    => isset($this->items[2]) ? $this->items[2]->id : NULL, // 'key(mvc=acc_Items,select=titleLink)'
            "{$type}Quantity" => $this->quantity, // 'double'
            "{$type}Price"    => $this->price, // 'double(minDecimals=2)'
        );

        return $rec;
    }
    
    
    /**
     * Връща зададената или изчислена цена
     * 
     * @return float NULL, ако цената нито е зададена, нито може да бъде изчислена
     */
    protected function getPrice()
    {
        if (isset($this->price)) {
            return $this->price;
        }
        
        if (isset($this->amount) && isset($this->quantity)) {
            return $this->amount / $this->quantity;
        }
        
        return NULL;
    }
}
