<?php

namespace Appkr;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Validation\Factory as ValidatorFactory;
use Symfony\Component\Translation\Translator;

class History extends Eloquent
{

    /**
     * Disable created_at and updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'histories';

    /**
     * Fillable fields for this model.
     *
     * @var array
     */
    protected $fillable = [
        'pid',
        'hostname',
        'ip_addr',
        'command',
        'result',
        'speed',
        'unit',
        'tested_at',
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    protected static $rules = [
        'count'   => ['numeric', 'max:999'],
        'sleep'   => ['numeric', 'max:10'],
        'client'  => ['regex:(^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$)'],
        'len'     => ['numeric', 'max:100000'],
        'port'    => ['numeric', 'max:65535'],
        'window'  => ['numeric'],
        'mss'     => ['numeric'],
        'limit'   => ['numeric', 'max:999'],
    ];

    protected static $messages = [
        'numeric' => 'The ":attribute" value must be a number.',
        'regex'   => 'The ":attribute" value format is invalid.',
        "max"     => "The :attribute may not be greater than :max.",
    ];

    /**
     * Create validator instance.
     *
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    public static function getValidator(array $data)
    {
        $factory = new ValidatorFactory(new Translator('en'));

        return $factory->make($data, static::$rules, static::$messages);
    }

    /**
     * Accessor for tested_at.
     *
     * @param $value
     * @return static
     */
    public function getTestedAtAttribute($value)
    {
        return $this->asDateTime($value)->timezone('Asia/Seoul');
    }

}