<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UsersUpdate2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('users', function (Blueprint $table) {
            $table->string('start_week')->default('SUNDAY');
            $table->boolean('use_device_timezoon')->default(false);
            $table->boolean('device_notification')->default(false);
            $table->boolean('email_reminder')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn('start_week');
            $table->dropColumn('use_device_timezoon');
            $table->dropColumn('device_notification');
            $table->dropColumn('email_reminder');
        });
    }
}
