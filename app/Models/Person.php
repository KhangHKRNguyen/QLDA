<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $table = 'people';

    protected $primaryKey = 'person_id';

    public $timestamps = false;

    protected $fillable = [
        'full_name', 'phone_number', 'email', 'date_of_birth', 'gender', 'address', 'district',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'person_id', 'person_id');
    }
}
