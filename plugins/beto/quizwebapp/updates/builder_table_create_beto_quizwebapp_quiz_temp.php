<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappQuizTemp extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_quiz_temp', function($table)
        {
            $table->increments('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->text('data');
            $table->integer('status')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_quiz_temp');
    }
}
