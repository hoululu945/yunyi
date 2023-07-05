<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $user_id 
 * @property string $content 
 * @property int $user_type 
 * @property int $form_type 
 * @property int $order_id 
 * @property string $update_time 
 * @property string $add_time 
 * @property int $status 
 */
class OrderDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_detail';
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
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'user_type' => 'integer', 'form_type' => 'integer', 'order_id' => 'integer', 'status' => 'integer'];
    public $timestamps = false;
}