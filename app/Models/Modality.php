<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Modality extends Model
{
    use HasVisibility;

    protected $table = 'modalities';

    protected $fillable = [
        'name',
        'color',
    ];
}
