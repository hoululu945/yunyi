<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $position_name 
 * @property int $company_id 
 * @property string $company_name 
 * @property int $department_id 
 * @property string $department_name 
 * @property string $add_time 
 * @property string $update_time 
 * @property int $status 
 */
class Position extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'position';
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
    protected $casts = ['id' => 'integer', 'company_id' => 'integer', 'department_id' => 'integer', 'status' => 'integer'];
    public $timestamps = false;
}