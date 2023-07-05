<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $user_id 
 * @property int $type 
 * @property string $content 
 * @property int $status 
 * @property string $creation_time 
 */
class UserOrderTalk extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_order_talk';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'order_id' => 'integer', 'user_id' => 'integer', 'type' => 'integer', 'status' => 'integer'];
    public $timestamps = false;
}