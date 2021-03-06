<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Model_Predict extends Model
{
    protected  $table = 'modelpredicts';
    
    protected $fillable = [
        'modelname',
        'model',
        'exetime',
    ];
    
    public function weather() {
        return $this->hasMany('App\Weather','model_id','id');
    }
}
