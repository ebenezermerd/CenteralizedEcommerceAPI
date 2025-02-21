use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeIdTypeInProductReviewsTable extends Migration
{
    public function up()
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropPrimary('id'); // Drop the primary key constraint
            $table->uuid('id')->primary()->change(); // Change the column type to UUID
        });
    }

    public function down()
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropPrimary('id'); // Drop the primary key constraint
            $table->integer('id')->primary()->change(); // Change the column type back to integer
        });
    }
}
