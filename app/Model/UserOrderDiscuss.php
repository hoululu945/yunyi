<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property string $content 
 * @property int $user_id 
 * @property int $status 
 * @property string $user_name 
 */
class UserOrderDiscuss extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_order_discuss';
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
    protected $casts = ['id' => 'integer', 'order_id' => 'integer', 'user_id' => 'integer', 'status' => 'integer'];
    public $timestamps = false;
}