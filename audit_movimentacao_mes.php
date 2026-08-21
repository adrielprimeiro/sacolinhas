<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== Auditando Entradas e Saídas de Itens do Mês Atual (" . date('m/Y') . ") ===\n\n";

$inicioMes = Carbon::now()->startOfMonth()->toDateTimeString();
$fimMes    = Carbon::now()->endOfMonth()->toDateTimeString();

// 1. ENTRADAS PELA AVALIAÇÃO
// A. Na tabela avaliacao_items (created_at neste mês)
$entradasAvaliacaoItems = DB::table('avaliacao_items')
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->count();

// B. Na tabela avaliacoes (created_at ou data_avaliacao neste mês) e soma de itens
$entradasAvaliacoesLotes = DB::table('avaliacoes')
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->count();

$itensDeAvaliacoesLotesMes = DB::table('avaliacao_items')
    ->whereIn('avaliacao_id', function($q) use ($inicioMes, $fimMes) {
        $q->select('id')->from('avaliacoes')->whereBetween('created_at', [$inicioMes, $fimMes]);
    })
    ->count();

// C. Todos os itens criados no mês no cadastro de estoque (items)
$itensCriadosMes = DB::table('items')
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->count();

echo "--- 1. ENTRADAS DE ITENS NO MÊS --- \n";
echo "Itens em avaliacao_items criados este mês: {$entradasAvaliacaoItems} peças\n";
echo "Lotes de avaliação criados este mês: {$entradasAvaliacoesLotes} lotes ({$itensDeAvaliacoesLotesMes} peças associadas)\n";
echo "Total de novos itens cadastrados em 'items' este mês: {$itensCriadosMes} peças\n\n";

// 2. SAÍDAS COM OS PEDIDOS DO MÊS
// A. Itens vendidos em pedidos criados este mês (status do pedido não cancelado)
$saidasPedidoItens = DB::table('pedido_itens')
    ->whereIn('pedido_id', function($q) use ($inicioMes, $fimMes) {
        $q->select('id')->from('pedidos')
          ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
          ->whereBetween('created_at', [$inicioMes, $fimMes]);
    })
    ->sum('quantidade');

if (!$saidasPedidoItens) {
    $saidasPedidoItens = DB::table('pedido_itens')
        ->whereIn('pedido_id', function($q) use ($inicioMes, $fimMes) {
            $q->select('id')->from('pedidos')
              ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
              ->whereBetween('created_at', [$inicioMes, $fimMes]);
        })
        ->count();
}

// B. Sacolinhas convertidas/processadas em pedidos no mês
$saidasSacolinhasMes = DB::table('sacolinhas')
    ->whereIn('status', ['pedido', 'vendido', 'fechado'])
    ->whereBetween('updated_at', [$inicioMes, $fimMes])
    ->sum('quantity');

// C. Itens atualizados com status 'vendido' no mês
$itensVendidosStatusMes = DB::table('items')
    ->where('status', 'vendido')
    ->whereBetween('updated_at', [$inicioMes, $fimMes])
    ->count();

echo "--- 2. SAÍDAS DE ITENS NO MÊS --- \n";
echo "Itens em pedido_itens de pedidos do mês: {$saidasPedidoItens} peças\n";
echo "Sacolinhas com status pedido/vendido atualizadas este mês: {$saidasSacolinhasMes} peças\n";
echo "Itens marcados com status 'vendido' este mês: {$itensVendidosStatusMes} peças\n";
