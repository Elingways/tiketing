<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('route_name');
            $table->string('icon')->default('grid');
            $table->string('color')->default('indigo');
            $table->string('group')->default('main'); // main, ticketing, management
            $table->string('badge_text')->nullable();
            $table->text('description')->nullable();
            $table->boolean('show_in_sidebar')->default(true);
            $table->boolean('show_in_launcher')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
