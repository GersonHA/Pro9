<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddThemePerUserToConfigurations extends Migration
{
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'theme_per_user')) {
                $table->boolean('theme_per_user')->default(true)->after('visual');
            }
        });
    }

    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'theme_per_user')) {
                $table->dropColumn('theme_per_user');
            }
        });
    }
}
