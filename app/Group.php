<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
       /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

     /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';
    protected $guarded = [];
}
