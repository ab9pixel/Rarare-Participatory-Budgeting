<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParticipatoryBudgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participatory_budgets', function (Blueprint $table) {
            $table->id();
            $table->longText("title")->nullable();
            $table->longText("description")->nullable();
            $table->longText("address")->nullable();
            $table->longText("latitude")->nullable();
            $table->longText("longitude")->nullable();
            $table->longText("budget")->nullable();
            $table->longText("proposed_summary")->nullable();
            $table->longText("budget_benefits")->nullable();
            $table->longText("vote_question")->nullable();
            $table->integer("audience")->nullable();
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->longText("start_time")->nullable();
            $table->longText("end_time")->nullable();
            $table->longText("participation")->nullable();
            $table->integer("status")->default("0");
            $table->integer("user_id")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('participatory_budgets');
    }
}
