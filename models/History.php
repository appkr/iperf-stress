<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class History extends Eloquent {

    public $timestamps = false;

    protected $table = 'histories';

    protected $fillable = [
        'pid',
        'hostname',
        'ip_addr',
        'command',
        'result',
        'speed',
        'unit',
        'tested_at'
    ];

    public function getTestedAtAttribute($value) {
        $datetime = $this->asDateTime($value);

        return $datetime->timezone('Asia/Seoul');
    }

}