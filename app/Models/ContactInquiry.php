<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantModel;

class ContactInquiry extends Model
{
    use HasFactory, SoftDeletes, TenantModel;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_inquiry';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
    ];

}
