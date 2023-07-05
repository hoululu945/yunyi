<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $sex_name 
 * @property string $style 
 * @property string $creation_time 
 * @property string $modiry_time 
 * @property int $sex 
 */
class StyleParam extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'style_param';
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
    protected $casts = ['id' => 'integer', 'sex' => 'integer'];
    public $timestamps = false;

}