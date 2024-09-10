<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalenderGoogle extends Model
{
    use HasFactory;
    protected $table = 'event_google';
    protected $fillable = [
      'start', 'end', 'title', 'description', 'is_all_day', 'user_id', 'event_id' 
    ];
}
