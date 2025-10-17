<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappLevels extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_levels', function($table)
        {
            $table->increments('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('name');
            $table->integer('parent_id')->nullable();
            $table->string('code');
            $table->integer('order_index');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_levels');
    }
}
