<?php

namespace App\Enums;

enum PaymentMethodSlug: string
{
    case COD = 'cod';
    case BankTransfer = 'bank-transfer';
}
