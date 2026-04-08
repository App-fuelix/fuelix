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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('fuel_card_id');
            $table->uuid('vehicle_id')->nullable();
            $table->dateTime('date');
            $table->decimal('amount', 10, 2);
            $table->decimal('quantity_liters', 10, 2)->nullable();
            $table->decimal('price_per_liter', 10, 2)->nullable();
            $table->string('station_name')->nullable();
            $table->double('average_consumption')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('fuel_card_id')->references('id')->on('fuel_cards')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
