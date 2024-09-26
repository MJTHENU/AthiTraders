<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDue extends Model
{
    use HasFactory;

    protected $table = 'loan_due'; // Specify the table name

    protected $fillable = [
        'loan_id',
        'user_id',
        'due_amount',
        'due_date',
        'paid_on',
        'collection_by',
        'updated_on',
    ];

}
