<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $user_type 
 * @property string $text 
 * @property string $add_time 
 * @property string $update_time 
 * @property int $order_id 
 */
class UserOrderInfo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_order_info';
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
    protected $casts = ['id' => 'integer', 'user_type' => 'integer', 'order_id' => 'integer'];
    public $timestamps = false;
}