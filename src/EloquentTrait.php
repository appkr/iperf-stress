<?php

namespace Appkr;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

trait EloquentTrait
{
    public $dbPath = __DIR__ . '/../database/database.sqlite';

    /**
     * Bootup sqlite database and instantiate Eloquent
     */
    public function bootDatabase()
    {
        if (! file_exists($this->dbPath)) {
            // Create database file, if not exists.
            file_put_contents($this->dbPath, null);
        }

        // Boot Eloquent
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => $this->dbPath,
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        if (! Capsule::schema()->hasTable('histories')) {
            // Create table, if not exists.
            $this->createTable();
        }
    }

    /**
     * Create histories table.
     *
     * @return bool|\Illuminate\Database\Schema\Blueprint
     */
    public function createTable()
    {
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

    /**
     * Get the list of table headings.
     *
     * @return array
     */
    public function getColumnListings()
    {
        return Capsule::schema()->getColumnListing('histories');
    }
}

