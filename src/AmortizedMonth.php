<?php
namespace CodeSmithTech\Amortize;

use DateTime;

class AmortizedMonth
{
    /**
     * @var float
     */
    private $principalDue;
    
    /**
     * @var float
     */
    private $interestDue;
    
    /**
     * @var array|Overpayment[]
     */
    private $overpayments = [];
    
    /**
     * @var DateTime
     */
    private $date;
    
    /**
     * @var float
     */
    private $openingBalance;
    
    /**
     * @var float
     */
    private $closingBalance;
    
    /**
     * @param DateTime $date
     * @param float $principalDue
     * @param float $interestDue
     */
    public function __construct(DateTime $date, float $principalDue = 0.0, float $interestDue = 0.0)
    {
        $this->date = $date;
        $this->principalDue = $principalDue;
        $this->interestDue = $interestDue;
    }
    
    public function getTotalAmountDue(): float
    {
        return round($this->principalDue + $this->interestDue, 2);
    }
    
    /**
     * @param array $overpayments
     * @return AmortizedMonth
     */
    public function setOverpayments(array $overpayments): AmortizedMonth
    {
        $this->overpayments = [];
        array_walk($overpayments, [$this, 'addOverpayment']);
        return $this;
    }
    
    /**
     * @return float
     */
    public function getTotalOverpayments(): float
    {
        return array_reduce($this->overpayments, function(float $carry, Overpayment $overpayment) {
            return $carry + $overpayment->getAmount();
        }, 0.0);
    }
    
    /**
     * @param float $balance
     * @return AmortizedMonth
     */
    public function setOpeningBalance(float $balance): AmortizedMonth
    {
        $this->openingBalance = $balance;
        return $this;
    }
    
    /**
     * @param float $balance
     * @return AmortizedMonth
     */
    public function setClosingBalance(float $balance): AmortizedMonth
    {
        $this->closingBalance = $balance;
        return $this;
    }
    
    /**
     * @param Overpayment $overpayment
     */
    private function addOverpayment(Overpayment $overpayment)
    {
        $this->overpayments[] = $overpayment;
    }
}