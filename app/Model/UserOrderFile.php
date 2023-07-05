<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $user_id 
 * @property string $user_name 
 * @property int $company_id 
 * @property string $company_name 
 * @property string $file_url 
 * @property string $remark 
 * @property string $upload_date 
 */
class UserOrderFile extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_order_file';
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
    protected $casts = ['id' => 'integer', 'order_id' => 'integer', 'user_id' => 'integer', 'company_id' => 'integer'];
    public $timestamps=false;
}