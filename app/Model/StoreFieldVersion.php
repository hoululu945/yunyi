<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $store_order_id 
 * @property int $version_no 
 * @property int $type 
 * @property string $version_num 
 * @property int $status 
 * @property int $version_id 
 * @property int $company_id 
 */
class StoreFieldVersion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_field_version';
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
    protected $casts = ['id' => 'integer', 'store_order_id' => 'integer', 'version_no' => 'integer', 'type' => 'integer', 'status' => 'integer', 'version_id' => 'integer', 'company_id' => 'integer'];
    public $timestamps = false;
}