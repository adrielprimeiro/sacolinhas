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
        // 1. Configurações Fiscais no Brechó (Emitente)
        if (Schema::hasTable('brechos')) {
            Schema::table('brechos', function (Blueprint $table) {
                if (!Schema::hasColumn('brechos', 'razao_social')) {
                    $table->string('razao_social')->nullable()->after('nome');
                }
                if (!Schema::hasColumn('brechos', 'inscricao_estadual')) {
                    $table->string('inscricao_estadual', 20)->nullable()->after('documento');
                }
                if (!Schema::hasColumn('brechos', 'regime_tributario')) {
                    $table->tinyInteger('regime_tributario')->default(1)->comment('1=Simples Nacional, 2=Simples Sublimite, 3=Regime Normal, 4=MEI');
                }
                if (!Schema::hasColumn('brechos', 'logradouro')) {
                    $table->string('logradouro')->nullable();
                }
                if (!Schema::hasColumn('brechos', 'numero_endereco')) {
                    $table->string('numero_endereco', 20)->nullable();
                }
                if (!Schema::hasColumn('brechos', 'complemento')) {
                    $table->string('complemento')->nullable();
                }
                if (!Schema::hasColumn('brechos', 'bairro')) {
                    $table->string('bairro')->nullable();
                }
                if (!Schema::hasColumn('brechos', 'codigo_municipio')) {
                    $table->string('codigo_municipio', 7)->nullable()->comment('Código IBGE');
                }
                if (!Schema::hasColumn('brechos', 'municipio')) {
                    $table->string('municipio')->nullable();
                }
                if (!Schema::hasColumn('brechos', 'uf')) {
                    $table->string('uf', 2)->nullable();
                }
                if (!Schema::hasColumn('brechos', 'cep')) {
                    $table->string('cep', 8)->nullable();
                }
                if (!Schema::hasColumn('brechos', 'nfe_serie')) {
                    $table->string('nfe_serie', 3)->default('1');
                }
                if (!Schema::hasColumn('brechos', 'nfe_ultimo_numero')) {
                    $table->integer('nfe_ultimo_numero')->default(0);
                }
                if (!Schema::hasColumn('brechos', 'nfe_ambiente')) {
                    $table->tinyInteger('nfe_ambiente')->default(2)->comment('1=Producao, 2=Homologacao');
                }
                if (!Schema::hasColumn('brechos', 'certificado_path')) {
                    $table->string('certificado_path')->nullable();
                }
                if (!Schema::hasColumn('brechos', 'certificado_senha')) {
                    $table->text('certificado_senha')->nullable();
                }
                if (!Schema::hasColumn('brechos', 'csc')) {
                    $table->string('csc')->nullable();
                }
                if (!Schema::hasColumn('brechos', 'csc_id')) {
                    $table->string('csc_id')->nullable();
                }
            });
        }

        // 2. Dados Fiscais nos Produtos (Items)
        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                if (!Schema::hasColumn('items', 'ncm')) {
                    $table->string('ncm', 8)->default('61091000')->nullable()->comment('NCM vestuário');
                }
                if (!Schema::hasColumn('items', 'cfop')) {
                    $table->string('cfop', 4)->nullable();
                }
                if (!Schema::hasColumn('items', 'cest')) {
                    $table->string('cest', 7)->nullable();
                }
                if (!Schema::hasColumn('items', 'origem')) {
                    $table->tinyInteger('origem')->default(0)->comment('0=Nacional');
                }
                if (!Schema::hasColumn('items', 'unidade_tributavel')) {
                    $table->string('unidade_tributavel', 6)->default('UN');
                }
            });
        }

        // 3. Tabela de Notas Fiscais
        if (!Schema::hasTable('notas_fiscais')) {
            Schema::create('notas_fiscais', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brecho_id')->default(1)->constrained('brechos')->cascadeOnDelete();
                $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
                $table->enum('tipo', ['nfe', 'nfce'])->default('nfe');
                $table->smallInteger('modelo')->default(55); // 55 = NF-e, 65 = NFC-e
                $table->string('serie', 3)->default('1');
                $table->integer('numero');
                $table->string('chave_acesso', 44)->nullable()->index();
                $table->enum('status', ['pendente', 'assinada', 'autorizada', 'rejeitada', 'cancelada', 'denegada'])->default('pendente');
                $table->string('cStat', 10)->nullable();
                $table->text('xMotivo')->nullable();
                $table->string('protocolo', 50)->nullable();
                $table->longText('xml_enviado')->nullable();
                $table->longText('xml_autorizado')->nullable();
                $table->longText('xml_cancelamento')->nullable();
                $table->string('danfe_pdf_path')->nullable();
                $table->decimal('valor_total', 10, 2)->default(0);
                $table->decimal('valor_produtos', 10, 2)->default(0);
                $table->decimal('valor_frete', 10, 2)->default(0);
                $table->decimal('valor_desconto', 10, 2)->default(0);
                $table->dateTime('data_emissao')->nullable();
                $table->dateTime('data_autorizacao')->nullable();
                $table->dateTime('data_cancelamento')->nullable();
                $table->text('justificativa_cancelamento')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');

        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn(['ncm', 'cfop', 'cest', 'origem', 'unidade_tributavel']);
            });
        }

        if (Schema::hasTable('brechos')) {
            Schema::table('brechos', function (Blueprint $table) {
                $table->dropColumn([
                    'razao_social', 'inscricao_estadual', 'regime_tributario',
                    'logradouro', 'numero_endereco', 'complemento', 'bairro',
                    'codigo_municipio', 'municipio', 'uf', 'cep',
                    'nfe_serie', 'nfe_ultimo_numero', 'nfe_ambiente',
                    'certificado_path', 'certificado_senha', 'csc', 'csc_id'
                ]);
            });
        }
    }
};
