<?php
namespace CodeSmithTech\Amortize;

use DateInterval;
use DateTime;
use Exception;

class Amortize
{
    /**
     * @var int
     */
    private $months = 0;
    
    /**
     * @var float
     */
    private $principal = 0;
    
    /**
     * @var float
     */
    private $interestRate = 0;
    
    /**
     * @var array
     */
    private $overpayments = [];
    
    /**
     * @var DateTime
     */
    private $startDate;
    
    public function __construct(DateTime $startDate = null)
    {
        $this->startDate = $startDate ?? new DateTime(date('Y-m-01'));
    }
    
    public function setInterestRate(float $interestRate): Amortize
    {
        $this->interestRate = $interestRate;
        return $this;
    }
    
    public function setPrincipal(float $principal): Amortize
    {
        $this->principal = $principal;
        return $this;
    }
    
    public function setTerm(int $months): Amortize
    {
        $this->months = $months;
        return $this;
    }
    
    public function addOverpayment(string $yearAndMonth, Overpayment $overpayment): Amortize
    {
        $this->overpayments[$yearAndMonth][] = $overpayment;
        return $this;
    }
    
    /**
     * Calculate the total amount due over the term of the loan
     * @return float
     * @throws Exception
     */
    public function totalAmountDueOverTerm(): float
    {
        if ($this->interestRate === 0.0) {
            return $this->principal;
        }
        
        $total = array_reduce($this->getBreakdownByMonth(), function($carry, AmortizedMonth $month) {
            return $carry + $month->getTotalAmountDue();
        }, 0.0);
        
        return round($total, 2);
    }
    
    /**
     * @return array|AmortizedMonth[]
     * @throws Exception
     */
    public function getBreakdownByMonth(): array
    {
        $months = [];
        $monthlyPayment = $this->calculateMonthlyPaymentAmount($this->principal, $this->months);
        $monthlyInterestRate = $this->calculateInterestRatePerMonth();
        $balance = $this->principal;
        
        $i = 0;
        $interval = new DateInterval('P1M');
        $currentMonth = clone $this->startDate;
        
        while ($balance > 0.0) {
            $i++;
            $interest = round($balance * $monthlyInterestRate, 2);
            
            // on the final month, the monthly payment is for the remaining balance
            if ($i === $this->months || ($balance < $monthlyPayment - $interest)) {
                $principal = $balance;
            } else {
                $principal = $monthlyPayment - $interest;
            }
    
            $monthDate = clone $currentMonth->add($interval);
//            echo $monthDate->format('Y-m') . PHP_EOL;
            $overpayments = $this->overpayments[$monthDate->format('Y-m')] ?? [];
            
            $month = new AmortizedMonth($monthDate, $principal, $interest);
            $month->setOpeningBalance($balance);
            $month->setOverpayments($overpayments);
            $months[] = $month;
    
            $balance -= $principal;
            
            if ($overpayments) {
                $balance -= $month->getTotalOverpayments();
                
                foreach ($overpayments as $overpayment) {
                    // if any of the overpayments changes the monthly payment amount, we recalculate it here so
                    // the payment amounts for subsequent months are reduced
                    if ($overpayment->reducesMonthlyPayment()) {
                        $monthlyPayment = $this->calculateMonthlyPaymentAmount($balance, $this->months - $i);
                        break;
                    }
                }
            }
            
            $month->setClosingBalance($balance);
        }
        
        return $months;
    }
    
    /**
     * The formula for calculating the compound interest used in this calculation is described at the following URL
     * @link https://www.vertex42.com/ExcelArticles/amortization-calculation.html
     * @param float $principal
     * @param int $months
     * @return float
     */
    private function calculateMonthlyPaymentAmount(float $principal, int $months): float
    {
        if ($this->interestRate === 0.0) {
            return round($principal / $months, 2);
        }
    
        $interestRatePerMonth = $this->calculateInterestRatePerMonth();
        $nominator = $interestRatePerMonth * ((1 + $interestRatePerMonth) ** $months);
        $denominator = ((1 + $interestRatePerMonth) ** $months) - 1;
        return round($principal * ($nominator / $denominator), 2);
    }
    
    /**
     * @return float
     */
    private function calculateInterestRatePerMonth(): float
    {
        return $this->interestRate / 12 / 100;
    }
}