<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInboxPermissionsToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('permission_inbox_lots')->default(true)->after('permission_edit_item_prices');
            $table->boolean('permission_inbox_credit')->default(true)->after('permission_inbox_lots');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['permission_inbox_lots', 'permission_inbox_credit']);
        });
    }
}
