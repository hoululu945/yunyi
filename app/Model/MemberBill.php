<?php

declare (strict_types=1);

namespace App\Model;


use Hyperf\DbConnection\Model\Model;
class MemberBill extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'no3_member_bill';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];
}