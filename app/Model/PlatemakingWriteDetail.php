<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $kuanshi_attr 
 * @property string $base_size 
 * @property string $platemaking_name 
 * @property string $te_code 
 * @property string $remark 
 * @property int $user_id 
 * @property string $add_time 
 * @property string $update_time 
 * @property int $status 
 */
class PlatemakingWriteDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'platemaking_write_detail';
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
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'status' => 'integer'];
    public $timestamps = false;
}