<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateBetoQuizwebappQuizzes2 extends Migration
{
    public function up()
    {
        Schema::table('beto_quizwebapp_quizzes', function($table)
        {
            $table->integer('level_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('beto_quizwebapp_quizzes', function($table)
        {
            $table->dropColumn('level_id');
        });
    }
}
