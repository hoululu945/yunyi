<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $field_name 
 * @property string $field_name_py 
 * @property string $add_time 
 * @property string $update_time 
 * @property int $status 
 * @property int $company_id 
 * @property int $type 
 * @property string $version_num 
 */
class CompanyFieldSet extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company_field_set';
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
    protected $casts = ['id' => 'integer', 'status' => 'integer', 'company_id' => 'integer', 'type' => 'integer'];
    public $timestamps=false;


//    protected $primaryKey = "";
    public function versionField()
    {
        return $this->hasMany(VersionField::class, 'version_id', 'id');
    }

}