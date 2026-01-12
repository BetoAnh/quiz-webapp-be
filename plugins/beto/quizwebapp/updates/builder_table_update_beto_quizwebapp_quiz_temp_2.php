<?php namespace Beto\Quizwebapp\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateBetoQuizwebappQuizTemp2 extends Migration
{
    public function up()
    {
        Schema::table('beto_quizwebapp_quiz_temp', function($table)
        {
            $table->text('warning')->nullable();
            $table->string('status', 20)->default('pending');
        });
    }
    
    public function down()
    {
        Schema::table('beto_quizwebapp_quiz_temp', function($table)
        {
            $table->dropColumn('warning');
            $table->dropColumn('status');
        });
    }
}
