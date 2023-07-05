<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $order_no 
 * @property int $userID 
 * @property string $title 
 * @property int $sample 
 * @property string $claims 
 * @property string $images 
 * @property string $parameter 
 * @property int $step 
 * @property int $status 
 * @property string $creation_time 
 * @property string $modiry_time 
 */
class UserOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_order';
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
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'sample' => 'integer', 'step' => 'integer', 'status' => 'integer'];
    public $timestamps = false;



}