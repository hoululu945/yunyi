<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $company_id 
 * @property int $user_id 
 * @property int $isAdministrator 
 * @property int $status 
 * @property string $remark 
 * @property string $creation_time 
 * @property string $modiry_time 
 */
class CompanyUserRelate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company_user_relate';
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
    protected $casts = ['id' => 'integer', 'company_id' => 'integer', 'user_id' => 'integer', 'isAdministrator' => 'integer', 'status' => 'integer','from_user_id'=>'integer'];
    public $timestamps = false;


    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }
}