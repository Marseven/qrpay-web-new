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
        Schema::create('menus', function (Blueprint $table) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string("titre");
                $table->boolean("disponible");
                $table->float("note");
                $table->string("restaurant");
                $table->timestamp("debut")->nullable();
                $table->timestamp("fin")->nullable();
                $table->timestamps();
            });
           Schema::table("plats",function (Blueprint $table){
            $table->foreignIdFor(Menu::class)->nullable()->constrained()->cascadeOnDelete();
           });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
        Schema::table("plats", function (Blueprint $table){
            $table->dropForeignIdFor(Menu::class);
        });
    }
};
