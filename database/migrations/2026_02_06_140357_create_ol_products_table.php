<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ol_products', function (Blueprint $table) {
            // Identitas Utama
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('image')->nullable();

            // Detail Produk
            $table->decimal('rating', 3, 2)->default(0.00); // Format 4.50
            $table->decimal('price', 10, 2)->default(0.00);
            $table->text('description')->nullable();

            // Pengelompokan & Status
            $table->enum('category', ['BAKPIA', 'ROTI', 'OTHER'])->default('OTHER');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();
            $table->softDeletes(); // Opsional: Untuk keamanan data jika produk dihapus

            // --- Indexing Strategy ---

            // Index untuk pencarian berdasarkan nama
            $table->index('name');

            // Composite Index untuk filtering di halaman katalog/list
            // Mempercepat query: SELECT * FROM ol_products WHERE category = '...' AND status = 'ACTIVE'
            $table->index(['category', 'status']);

            // Index untuk pengurutan berdasarkan rating/harga terbaru
            $table->index('rating');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ol_products');
    }
};
