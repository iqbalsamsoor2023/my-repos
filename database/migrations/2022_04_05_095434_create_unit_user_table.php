3
<?php

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Unit::class);
            $table->foreignIdFor(User::class);
            $table->boolean('is_owner')->default(false);
            // $table->enum('type', ['Owner', 'Tenant']);
            $table->string('mmb_id')->nullable();
            $table->boolean('is_main_owner')->default(false);
            $table->boolean('is_main_tenant')->default(false);
            $table->integer('relationship')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_user');
    }
};
