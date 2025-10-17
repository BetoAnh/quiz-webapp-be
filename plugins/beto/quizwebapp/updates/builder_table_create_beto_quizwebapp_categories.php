<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateBetoQuizwebappCategories extends Migration
{
    public function up()
    {
        Schema::create('beto_quizwebapp_categories', function($table)
        {
            $table->bigIncrements('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->text('description')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('beto_quizwebapp_categories');
    }
}
