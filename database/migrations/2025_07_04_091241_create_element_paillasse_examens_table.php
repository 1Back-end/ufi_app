 <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('element_paillasse_examens', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('unit')->nullable();
                $table->integer('numero_order')->nullable();
                $table->foreignId('category_element_result_id')->constrained('category_element_results')->onDelete('restrict');
                $table->foreignId('type_result_id')->constrained('type_results')->onDelete('restrict');
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('element_paillasse_examens');
        }
    };
