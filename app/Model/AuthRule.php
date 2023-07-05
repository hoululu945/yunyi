<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property int $parent_id 
 * @property int $type 
 * @property int $status 
 * @property int $list_order 
 * @property string $name 
 * @property string $param 
 * @property string $title 
 * @property string $remark 
 * @property string $icon 
 */
class AuthRule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auth_rule';
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
    protected $casts = ['id' => 'integer', 'parent_id' => 'integer', 'type' => 'integer', 'status' => 'integer', 'list_order' => 'integer'];
    protected $primaryKey = 'id';
    public $timestamps = false;


   public function allMenu(){
       $menus = self::query()->orderBy('parent_id', 'asc')->orderBy('list_order', 'desc')->get();
       return $menus;
   }

    public function allRoleRule(){
//        $rule = AuthRoleRule::where(['role_id' => $id])->pluck('rule_id')->toArray();

        $menus = self::query()->orderBy('parent_id', 'asc')->orderBy('list_order', 'desc')->get();
        return $menus;
    }



}