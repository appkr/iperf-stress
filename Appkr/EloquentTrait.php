<?php namespace Appkr;

use Illuminate\Database\Capsule\Manager as Capsule;

trait EloquentTrait {

    /**
     * Bootup sqlite database and instantiate Eloquent
     */
    public function bootDatabase() {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => __DIR__.'/../database/database.sqlite',
            'prefix' => ''
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

}

