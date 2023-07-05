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
 */
class SystemType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_type';
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
    protected $casts = ['id' => 'integer'];
    public $timestamps = false;
    public function typeField(){
        return $this->hasMany(SystemTypeField::class, 'system_type_id', 'id');

    }

}