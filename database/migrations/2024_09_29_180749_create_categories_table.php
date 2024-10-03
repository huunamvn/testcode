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
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // bigint (PK, AI)
            $table->string('name'); // Tên danh mục
            $table->string('slug')->unique(); // Đường dẫn thân thiện với người dùng (unique)
            $table->text('description')->nullable(); // Mô tả danh mục (nullable)
            $table->string('image_path')->nullable(); // Đường dẫn hình ảnh (nullable)
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade'); // FK tới chính bảng categories, nullable, xóa cascade
            $table->timestamps(); // created_at và updated_at tự động
            $table->softDeletes(); // Cột deleted_at để hỗ trợ xóa mềm
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
