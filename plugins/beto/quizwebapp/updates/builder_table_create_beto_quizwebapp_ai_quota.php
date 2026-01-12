<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappAiQuota extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_ai_quota', function($table)
        {
            $table->increments('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->date('date');
            $table->integer('used')->default(0);
            $table->integer('limit')->default(2);
            $table->bigInteger('user_id')->unsigned();
            
            $table->unique(['user_id', 'date']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_ai_quota');
    }
}
