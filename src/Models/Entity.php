<?php

namespace ApprovalSequence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    use SoftDeletes;

    protected $table = 'entities';
    protected $primaryKey  = 'entity_id';
    protected $fillable = ['entity_id','entity_type', 'approver_id', 'approver_type'];

    protected $dates = ['deleted_at'];


    public function entity()
    {
        return $this->morphTo();
    }

    public function approver()
    {
        return $this->morphTo();
    }

}
