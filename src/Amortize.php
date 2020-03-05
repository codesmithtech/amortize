<?php
namespace CodeSmithTech\Amortize;

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
    
    public function addOverpayment(int $month, Overpayment $overpayment)
    {
        $this->overpayments[$month][] = $overpayment;
        return $this;
    }
    
    /**
     * The formula for calculating the compound interest used in this calculation is described at the following URL
     * @link https://www.vertex42.com/ExcelArticles/amortization-calculation.html
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
     */
    public function getBreakdownByMonth(): array
    {
        $months = [];
        $monthlyPayment = $this->calculateMonthlyPaymentAmount($this->principal, $this->months);
        $monthlyInterestRate = $this->calculateInterestRatePerMonth();
        $balance = $this->principal;
        
        $i = 0;
        
        while ($balance > 0.0) {
            $i++;
            $interest = round($balance * $monthlyInterestRate, 2);
            
            // on the final month, the monthly payment is for the remaining balance
            if ($i === $this->months || ($balance < $monthlyPayment - $interest)) {
                $principal = $balance;
            } else {
                $principal = $monthlyPayment - $interest;
            }
            
            $overpayments = $this->overpayments[$i] ?? [];
            
            $month = new AmortizedMonth($i, $principal, $interest);
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