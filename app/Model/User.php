<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $userName 
 * @property string $nickName 
 * @property string $pwd 
 * @property string $rule 
 * @property string $status 
 * @property string $remark 
 * @property string $creation_time 
 * @property string $modiry_time 
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';
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
    protected $primaryKey = 'id';
    public $timestamps = false;





}