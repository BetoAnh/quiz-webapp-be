<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappUserQuizRecent extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_user_quiz_recent', function($table)
        {
            $table->increments('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('quiz_id')->unsigned();
            $table->timestamp('last_learned_at')->nullable();
            
            $table->unique(['user_id', 'quiz_id'], 'uq_user_quiz');
            $table->index(['user_id', 'last_learned_at'], 'idx_user_recent');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_user_quiz_recent');
    }
}
