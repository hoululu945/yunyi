<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $user_id 
 * @property int $status 
 * @property string $creation_time 
 * @property string $modiry_time 
 * @property int $type 
 * @property int $invite_status 
 * @property int $company_id 
 * @property int $user_type 
 */
class UserOrderGroup extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_order_group';
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
    protected $casts = ['id' => 'integer', 'order_id' => 'integer', 'user_id' => 'integer', 'status' => 'integer', 'type' => 'integer', 'invite_status' => 'integer', 'company_id' => 'integer', 'user_type' => 'integer'];
    public $timestamps = false;

    public function order(){
        return $this->belongsTo(UserOrder::class,'order_id','id');
    }
}