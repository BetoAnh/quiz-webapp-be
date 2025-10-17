<?php
namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappQuizzes extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_quizzes', function ($table) {
            $table->bigIncrements('id')->unsigned();
            $table->text('title');
            $table->text('description');
            $table->string('visibility')->default('public');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('author_id')->unsigned();
            $table->string('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_quizzes');
    }
}
