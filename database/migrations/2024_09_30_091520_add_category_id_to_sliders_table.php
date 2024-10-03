<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
           // Thêm cột category_id và liên kết với bảng categories
           $table->unsignedBigInteger('category_id')->after('is_active'); // Cột category_id sau cột is_active
           $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade'); // Khóa ngoại
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            // Xóa khóa ngoại và cột category_id khi rollback migration
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
