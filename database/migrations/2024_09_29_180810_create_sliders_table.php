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
        Schema::create('sliders', function (Blueprint $table) {
            $table->id(); // bigint (PK, AI)
            $table->string('title'); // Tiêu đề banner
            $table->string('short_title'); // Tiêu đề ngắn của banner
            $table->string('image_path'); // Đường dẫn hình ảnh của banner
            $table->string('link_url'); // Đường dẫn khi người dùng bấm vào banner
            $table->integer('position')->unsigned()->unique();
            $table->boolean('is_active')->default(true); // Trạng thái hoạt động của banner (mặc định true)
            $table->timestamps(); // created_at và updated_at tự động
            $table->softDeletes(); // Cột deleted_at để hỗ trợ xóa mềm
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
