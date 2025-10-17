<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateBetoQuizwebappQuizzes extends Migration
{
    public function up()
    {
        Schema::table('beto_quizwebapp_quizzes', function($table)
        {
            $table->bigInteger('category_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('beto_quizwebapp_quizzes', function($table)
        {
            $table->dropColumn('category_id');
        });
    }
}
