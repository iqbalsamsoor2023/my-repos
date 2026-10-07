<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;

class ChatCategory extends Model
{
    protected $connection = 'mmbcnerp';

    protected $table = 'chat_categories';
}
