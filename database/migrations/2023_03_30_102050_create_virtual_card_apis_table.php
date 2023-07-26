<?php

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
        Schema::create('virtual_card_apis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("admin_id");
            $table->text('secret_key')->nullable();
            $table->text('secret_hash')->nullable();
            $table->string('url')->nullable();
            $table->text('card_details')->nullable();
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('virtual_card_apis');
    }
};
