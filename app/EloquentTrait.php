<?php

namespace Appkr;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

trait EloquentTrait
{
    /**
     * Bootup sqlite database and instantiate Eloquent
     */
    public function bootDatabase()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => __DIR__ . '/../database/database.sqlite',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    /**
     * Create a database and histories table.
     *
     * @return bool|\Illuminate\Database\Schema\Blueprint
     */
    public function checkDatabase()
    {
        $path = __DIR__ . '/../database/database.sqlite';

        if (! file_exists($path)) {
            file_put_contents($path, null);
        }

        if (Capsule::schema()->hasTable('histories')) {
            return false;
        }

        return Capsule::schema()->create('histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pid')->nullable();
            $table->string('command')->nullable();
            $table->string('result')->nullable();
            $table->integer('speed')->nullable();
            $table->string('unit')->nullable();
            $table->timestamp('tested_at');
        });
    }
}

