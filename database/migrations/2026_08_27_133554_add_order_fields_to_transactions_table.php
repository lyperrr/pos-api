<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('order_number', 50)->nullable()->after('member_id');
            $table->string('table_number', 30)->nullable()->after('order_number');
            $table->integer('people_count')->default(1)->after('table_number');
            $table->string('order_type', 30)->default('dine_in')->after('people_count');
            $table->decimal('donation_amount', 12, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'order_number',
                'table_number',
                'people_count',
                'order_type',
                'donation_amount',
            ]);
        });
    }
};
