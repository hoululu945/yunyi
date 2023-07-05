<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $kuan_num 
 * @property string $kuanshi 
 * @property string $write_date 
 * @property string $pinpaiorcompany 
 * @property string $yesr_dress 
 * @property string $season 
 * @property string $wave 
 * @property string $design_name 
 * @property string $remark 
 * @property string $add_time 
 * @property string $update_time 
 * @property int $status 
 * @property int $order_id 
 * @property int $user_id 
 */
class DesignWriteDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'design_write_detail';
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
    protected $casts = ['id' => 'integer', 'status' => 'integer', 'order_id' => 'integer', 'user_id' => 'integer'];
    public $timestamps = false;
}