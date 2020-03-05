<?php
namespace CodeSmithTech\Amortize\Tests;

use CodeSmithTech\Amortize\Amortize;
use CodeSmithTech\Amortize\AmortizedMonth;
use CodeSmithTech\Amortize\Overpayment;
use PHPUnit\Framework\TestCase;

class AmortizeTest extends TestCase
{
    /**
     * @var Amortize
     */
    private $a;
    
    public function setUp(): void
    {
        $this->a = new Amortize();
    }
    
    public function testAmountOwedIsEqualToAmountBorrowedWhenZeroInterestRate()
    {
        $principal = 10000.0;
        
        $this->a->setInterestRate(0);
        $this->a->setPrincipal($principal);
        $this->a->setTerm(12);
    
        $this->assertSame($principal, $this->a->totalAmountDueOverTerm());
    }
    
    public function testAmountOwedIsBrokenIntoEqualPartsOverA12MonthDuration()
    {
        $principal = 36.0;
        $term = 12;
        $dueEachMonth = $principal / $term;
        
        $this->a->setInterestRate(0);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
        
        $months = $this->a->getBreakdownByMonth();
        $this->assertSame($term, count($months));
        
        foreach ($this->a->getBreakdownByMonth() as $month) {
            $this->assertInstanceOf(AmortizedMonth::class, $month);
            $this->assertSame($dueEachMonth, $month->getTotalAmountDue());
        }
    }
    
    public function testAmountOwedIncreasedWhenA50PercentInterestRateApplies()
    {
        $principal = 20.0;
        $term = 12;
        
        $this->a->setInterestRate(50.0);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
        
        $totalDue = $this->a->totalAmountDueOverTerm();
        
        $this->assertSame(25.84, $totalDue);
    }
    
    public function testInterestIsCalculatedCorrectlyForPartialYears()
    {
        $principal = 20.0;
        $term = 6;
    
        $this->a->setInterestRate(50.0);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
    
        $totalDue = $this->a->totalAmountDueOverTerm();
    
        $this->assertSame(23.01, $totalDue);
    }
    
    public function testInterestIsCalculatedCorrectlyForFullAndPartialYears()
    {
        $principal = 100.0;
        $term = 14;
    
        $this->a->setInterestRate(12.3);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
    
        $totalDue = $this->a->totalAmountDueOverTerm();
    
        $this->assertSame(107.88, $totalDue);
    }
    
    public function testLargeNumberOverLongPeriod()
    {
        $principal = 1000000;
        $term = 12 * 50; // 50 years
        
        $this->a->setInterestRate(3.5);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
        
        $totalDue = $this->a->totalAmountDueOverTerm();
        
        $this->assertSame(2119203.03, $totalDue);
    }
    
    public function testOverpaymentReducesTheAmountOfInterestDueOverTerm()
    {
        $principal = 1000000;
        $term = 12 * 5; // 5 years
        $withNoOverpayment = 1091504.72;
        
        $this->a->setInterestRate(3.5);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
    
        $this->assertSame($withNoOverpayment, $this->a->totalAmountDueOverTerm());
        
        // add an overpayment in month 12
        $this->a->addOverpayment(12, new Overpayment(5000.0, Overpayment::REDUCE_MONTHLY_PAYMENT));
        $this->assertLessThan($withNoOverpayment, $this->a->totalAmountDueOverTerm());
    }
    
    public function testOverpaymentReducesTheMonthlyPaymentDueForRemainingMonths()
    {
        $principal = 1000000;
        $term = 12 * 5; // 5 years
    
        $this->a->setInterestRate(3.5);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
        
        $this->a->addOverpayment(3, new Overpayment(10000.0, Overpayment::REDUCE_MONTHLY_PAYMENT));
        
        $months = $this->a->getBreakdownByMonth();
        
        $this->assertSame($term, count($months)); // ensure we still have a 5 year term
        $this->assertSame($months[0]->getTotalAmountDue(), $months[1]->getTotalAmountDue());
        $this->assertLessThan($months[1]->getTotalAmountDue(), $months[3]->getTotalAmountDue());
    }
    
    public function testOverpaymentReducesTheNumberOfMonthsRepaymentsContinueFor()
    {
        $principal = 1000000;
        $term = 12 * 5; // 5 years
    
        $this->a->setInterestRate(3.5);
        $this->a->setPrincipal($principal);
        $this->a->setTerm($term);
    
        $this->a->addOverpayment(3, new Overpayment(100000.0, Overpayment::REDUCE_LOAN_TERM));
    
        $months = $this->a->getBreakdownByMonth();
    
        $this->assertLessThan($term, count($months)); // we should be paying the loan back over a shorter term now
    }
}
