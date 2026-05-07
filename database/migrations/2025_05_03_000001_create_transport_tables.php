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
        // Transport Routes
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('start_point');
            $table->string('end_point');
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->time('departure_time')->nullable();
            $table->time('return_time')->nullable();
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
            $table->index('name');
        });

        // Transport Vehicles
        Schema::create('transport_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number')->unique();
            $table->string('vehicle_type'); // bus, van, etc.
            $table->string('model')->nullable();
            $table->string('make')->nullable();
            $table->integer('capacity');
            $table->string('registration_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('fitness_certificate_expiry')->nullable();
            $table->string('status')->default('active'); // active, maintenance, retired
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
            $table->index('vehicle_number');
        });

        // Transport Drivers
        Schema::create('transport_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });

        // Route-Vehicle-Driver Assignments
        Schema::create('transport_route_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->enum('trip_type', ['morning', 'evening', 'both'])->default('both');
            $table->date('assignment_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['route_id', 'vehicle_id', 'driver_id']);
            $table->index('school_id');
        });

        // Transport Stops
        Schema::create('transport_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->time('morning_pickup_time')->nullable();
            $table->time('evening_drop_time')->nullable();
            $table->integer('stop_order');
            $table->decimal('distance_from_start', 8, 2)->nullable();
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index('route_id');
            $table->index('school_id');
        });

        // Transport Fees - Core table for fee integration
        Schema::create('transport_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('stop_id')->nullable(); // Optional: different fees for different stops
            $table->decimal('amount', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'term', 'yearly'])->default('monthly');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['route_id', 'stop_id']);
            $table->index('school_id');
            $table->index(['effective_from', 'effective_to']);
        });

        // Transport Allocations - Students assigned to routes
        Schema::create('transport_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('stop_id');
            $table->unsignedBigInteger('transport_fee_id')->nullable(); // Link to fee structure
            $table->date('allocation_date');
            $table->date('end_date')->nullable();
            $table->enum('trip_type', ['morning', 'evening', 'both'])->default('both');
            $table->string('status')->default('active'); // active, suspended, terminated
            $table->boolean('auto_charge')->default(true); // Enable/disable auto fee generation
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'route_id']);
            $table->index(['route_id', 'stop_id']);
            $table->index('school_id');
            $table->index('status');
        });

        // Transport Fee Charges - Individual fee records per student per period
        Schema::create('transport_fee_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('transport_fee_id');
            $table->decimal('amount', 10, 2);
            $table->string('period'); // e.g., "2025-05" for monthly, "2025-Term1" for term
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, paid, partial, cancelled
            $table->unsignedBigInteger('invoice_id')->nullable(); // Link to fees module invoice
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'period']);
            $table->index(['allocation_id', 'period']);
            $table->index(['route_id', 'period']);
            $table->index('school_id');
            $table->index('status');
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_fee_charges');
        Schema::dropIfExists('transport_allocations');
        Schema::dropIfExists('transport_fees');
        Schema::dropIfExists('transport_stops');
        Schema::dropIfExists('transport_route_assignments');
        Schema::dropIfExists('transport_drivers');
        Schema::dropIfExists('transport_vehicles');
        Schema::dropIfExists('transport_routes');
    }
};
