<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\WeatherStation;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weather_stations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('state');
            $table->string('bom_city_code');
            $table->string('bom_station_code');
        });

        // Insert stations
        $data =  array(
            [
                'name' => 'Brisbane - CBD',
                'state' => 'QLD',
                'bom_city_code' => 'IDQ60901',
                'bom_station_code' => '94576',
            ],
            [
                'name' => 'Brisbane - Airport',
                'state' => 'QLD',
                'bom_city_code' => 'IDQ60901',
                'bom_station_code' => '94578',
            ],
            [
                'name' => 'Sydney - CBD',
                'state' => 'NSW',
                'bom_city_code' => 'IDN60901',
                'bom_station_code' => '94768',
            ],
            [
                'name' => 'Sydney - Harbour',
                'state' => 'NSW',
                'bom_city_code' => 'IDN60901',
                'bom_station_code' => '95766',
            ],
        );
        foreach ($data as $datum){
            $category = new WeatherStation();
            $category->name = $datum['name'];
            $category->state = $datum['state'];
            $category->bom_city_code = $datum['bom_city_code'];
            $category->bom_station_code = $datum['bom_station_code'];
            $category->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('weather_stations');
    }
};
