<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id 
 * @property string $order_no 
 * @property int $order_date 
 * @property float $total_money 
 * @property string $method 
 * @property string $status 
 */
class BandDui extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'band_dui';
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
    protected $casts = ['id' => 'integer', 'order_date' => 'integer', 'total_money' => 'float'];
    public $timestamps = false;
}