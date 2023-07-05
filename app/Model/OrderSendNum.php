<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $user_id 
 * @property int $company_id 
 * @property string $company_name 
 * @property int $send_num 
 * @property int $type 
 * @property int $from_company_id 
 * @property string $from_company_name 
 * @property string $add_time 
 * @property string $update_time 
 */
class OrderSendNum extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_send_num';
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
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'company_id' => 'integer', 'send_num' => 'integer', 'type' => 'integer', 'from_company_id' => 'integer'];
    public $timestamps = false;
}