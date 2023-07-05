<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $sh_code 
 * @property string $trade_date 
 * @property string $trade_time 
 * @property string $order_no 
 * @property string $pay_method 
 * @property float $total_money 
 * @property float $qsuan_money 
 * @property float $shouxufei 
 * @property int $feilv 
 */
class BandRecord extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'band_record';
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
    protected $casts = ['id' => 'integer', 'total_money' => 'float', 'qsuan_money' => 'float', 'shouxufei' => 'float', 'feilv' => 'integer'];
    public $timestamps = false;
}