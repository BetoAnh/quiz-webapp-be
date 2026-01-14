<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappQuizFavorites extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_quiz_favorites', function($table)
        {
            $table->increments('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('quiz_id')->unsigned();
            $table->unique(['user_id', 'quiz_id']); 
            $table->index('user_id');
            $table->index('quiz_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_quiz_favorites');
    }
}
