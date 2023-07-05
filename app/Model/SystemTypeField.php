<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $type 
 * @property string $type_name 
 * @property string $add_time 
 * @property string $name 
 * @property int $is_te 
 * @property int $system_type_id 
 */
class SystemTypeField extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_type_field';
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
    protected $casts = ['id' => 'integer', 'is_te' => 'integer', 'system_type_id' => 'integer'];
    public $timestamps = false;

}