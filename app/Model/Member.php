<?php

declare (strict_types=1);
namespace App\Model;

use App\Amqp\Producer\DemoProducer;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Model\Model;
use Hyperf\CircuitBreaker\Annotation\CircuitBreaker;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Di\Annotation\Inject;


/**
 */
class Member extends Model
{

//    /**
//     * 通过 `@Inject` 注解注入由 `@var` 注解声明的属性类型对象
//     *
//     * @Inject
//     * @var DemoProducer
//     */
//    protected $demProducer;
//    /**
//     * The table associated with the model.
//     *
//     * @var string
//     */
    protected $table = 'no3_member';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['username','email'];
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];
    protected $primaryKey = "user_id";
    public function bill()
    {
        return $this->hasOne(MemberBill::class, 'user_id', 'user_id');
    }

    /**
     * @CircuitBreaker(timeout=0.005, failCounter=1, successCounter=1, fallback="App\Model\Member::searchFallback")
     */
    function test(){
//           $ob =  $this->demProducer;
//       $producer_dem =  new DemoProducer(1);
//        $producer = ApplicationContext::getContainer()->get(Producer::class);
//        $result = $producer->produce($producer_dem);
    }
    static function info(){
//        $producer_dem =  new DemoProducer(1);
//        $producer = ApplicationContext::getContainer()->get(Producer::class);
//        $result = $producer->produce($producer_dem);
        return  $info = self::with(['bill'=>function($query){
           $query->select('user_id','money');
       }])->select("user_id")->find(12);
        $u = self::findOrFail(2);

//        $u = self::query()->where('user_id',16)->create(['email'=>'999912211@qq.com']);
        return $u->fill(['email' => 'Hyperf']);

        return self::query()->where('user_id',14)->update(['phone'=>15158801149]);

         $model = self::findOrFail(2);

        return  $object = self::query()->select("user_id","username",'email')->get();
        $objects = $object->reject(function ($o){
            if($o->user_id ==2){
                return true;
            }
        });
//        return $freshUser = $object->fresh();
//        $object = self::query()->find(2);
        return $objects;
       return $object->email = '1650221128@qq.com';
        $object->save();
        return $freshUser = $object->fresh();

//        return self::query()->find(2);
    }

    public function searchFallback()
    {
        return [1,2,3];
    }

//
//    function red(){
//        $rds = new \Redis();
//        try {
//            $ret = $rds->pconnect("127.0.0.1", 6390);
//            if ($ret == false) {
//                echo "Connect return false";
//                exit;
//            }
//            //设置超时时间为 0.1ms
//            $rds->setOption(3,0.0001);
//            $rds->get("aa");
//        } catch (\Exception $e) {
//            var_dump ($e);
//        }
//
//    }
}