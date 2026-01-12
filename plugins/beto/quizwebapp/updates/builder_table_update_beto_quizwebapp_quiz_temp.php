<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateBetoQuizwebappQuizTemp extends Migration
{
    public function up()
    {
        Schema::table('beto_quizwebapp_quiz_temp', function($table)
        {
            $table->dropColumn('status');
        });
    }
    
    public function down()
    {
        Schema::table('beto_quizwebapp_quiz_temp', function($table)
        {
            $table->integer('status')->default(0);
        });
    }
}
