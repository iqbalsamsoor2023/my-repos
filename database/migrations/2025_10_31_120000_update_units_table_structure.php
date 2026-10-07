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
        Schema::table('units', function (Blueprint $table) {
            // Drop unit_size_type if exists
            if (Schema::hasColumn('units', 'unit_size_type')) {
                $table->dropColumn('unit_size_type');
            }

            // Add land_size if not exists
            if (! Schema::hasColumn('units', 'land_size')) {
                $table->decimal('land_size', 10, 2)->nullable()->after('unit_size');
            }

            // Rename charge_unit to charge_type if charge_unit exists
            if (Schema::hasColumn('units', 'charge_unit')) {
                $table->renameColumn('charge_unit', 'charge_type');
            } elseif (! Schema::hasColumn('units', 'charge_type')) {
                $table->enum('charge_type', [1, 2, 3])->nullable()->after('land_size')->comment('1-SQW, 2-SQM, 3-Chartered');
            }

            // Add maintenance_cycle if not exists
            if (! Schema::hasColumn('units', 'maintenance_cycle')) {
                $table->enum('maintenance_cycle', ['M', 'B', 'Q', 'H', 'Y'])->nullable()->after('charge_type')->comment('M-Monthly, B-Bi-Monthly, Q-Quarterly, H-Half-Yearly, Y-Yearly');
            }

            // Drop payment_frequency if exists
            if (Schema::hasColumn('units', 'payment_frequency')) {
                $table->dropColumn('payment_frequency');
            }

            // Ensure unit_size is decimal
            $table->decimal('unit_size', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Restore unit_size_type
            $table->enum('unit_size_type', ['1', '2'])->nullable()->after('unit_size');

            // Drop new columns
            if (Schema::hasColumn('units', 'maintenance_cycle')) {
                $table->dropColumn('maintenance_cycle');
            }

            // Rename charge_type back to charge_unit
            if (Schema::hasColumn('units', 'charge_type')) {
                $table->renameColumn('charge_type', 'charge_unit');
            }

            // Restore payment_frequency
            $table->string('payment_frequency')->nullable()->after('charge_unit');

            // Drop land_size
            if (Schema::hasColumn('units', 'land_size')) {
                $table->dropColumn('land_size');
            }

            // Restore unit_size to string
            $table->string('unit_size')->nullable()->change();
        });
    }
};
