# Amortize

Amortize calculates the amortization of loans over their term, taking care of the math for you.

* Calculates principal and interest for each month over the loan term
* Calculate total repayable over the term of the loan
* Add overpayments part way through the term and have the loan term shortened, or the monthly payment recalculated and reduced for the remainder of the term

Install

```
composer require codesmithtech/amortize
```

Usage

```
<?php
$amortize = new Amortize();
$amortize->setInterestRate(3.5);
$amortize->setPrincipal(15000.0);
$amortize->setTerm(36); // months

$amortize->totalAmountDueOverTerm(); // returns a float with a value equal to the principal + interest owed over the term of the loan

$amortize->getBreakdownByMonth(); //  returns an array of AmortizedMonth objects, each containing the amount of principal and interest due that month
```
