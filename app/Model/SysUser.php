<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $user_id 
 * @property string $user_no 
 * @property string $user_name 
 * @property string $pass 
 * @property string $area_no 
 * @property string $phone 
 * @property string $mail 
 * @property int $is_admin 
 * @property int $status 
 * @property string $create_date 
 * @property string $last_login_date 
 * @property string $last_login_ip 
 * @property int $olduserid 
 * @property int $train_company_id 
 * @property int $user_type 
 */
class SysUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_user';
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
    protected $casts = ['user_id' => 'integer', 'is_admin' => 'integer', 'status' => 'integer', 'olduserid' => 'integer', 'train_company_id' => 'integer', 'user_type' => 'integer'];
    protected $primaryKey = 'id';
    public $timestamps = false;
}