<?php
namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappQuestions extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_questions', function ($table) {
            $table->bigIncrements('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('quiz_id')->unsigned();
            $table->text('text');
            $table->text('options');
            $table->integer('correct_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_questions');
    }
}
