<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateBetoQuizwebappQuizTemp3 extends Migration
{
    public function up()
    {
        Schema::table('beto_quizwebapp_quiz_temp', function($table)
        {
            $table->text('data')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('beto_quizwebapp_quiz_temp', function($table)
        {
            $table->text('data')->nullable(false)->change();
        });
    }
}
