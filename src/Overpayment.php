<?php
namespace CodeSmithTech\Amortize;

use InvalidArgumentException;

class Overpayment implements \JsonSerializable
{
    const REDUCE_LOAN_TERM = 'REDUCE_LOAN_TERM';
    const REDUCE_MONTHLY_PAYMENT = 'REDUCE_MONTHLY_PAYMENT';
    
    /**
     * @var float
     */
    private $amount;
    
    /**
     * @var string
     */
    private $effect;
    
    /**
     * @param float $amount
     * @param string $effectOnLoan
     */
    public function __construct(float $amount, string $effectOnLoan)
    {
        if (! static::effectOnLoanIsValid($effectOnLoan)) {
            throw new InvalidArgumentException("Effect on loan is not supported [$effectOnLoan]");
        }
        
        $this->amount = $amount;
        $this->effect = $effectOnLoan;
    }
    
    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }
    
    /**
     * @return bool
     */
    public function reducesMonthlyPayment(): bool
    {
        return $this->effect === self::REDUCE_MONTHLY_PAYMENT;
    }
    
    /**
     * @param string $effectOnLoan
     * @return bool
     */
    public static function effectOnLoanIsValid(string $effectOnLoan)
    {
        return array_key_exists($effectOnLoan, self::effectsOnLoan());
    }
    
    /**
     * @return array
     */
    public static function effectsOnLoan(): array
    {
        return [
            self::REDUCE_LOAN_TERM => 'Reduce loan term',
            self::REDUCE_MONTHLY_PAYMENT => 'Reduce monthly payment',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'amount' => $this->amount,
            'effectOnLoan' => $this->effect,
        ];
    }
}