<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendWhatsAppVencidosPdf;  
use App\Jobs\SendWhatsAppVencidosTemplate;


class SacolinhaVencidaController extends Controller
{
    public function index(Request $request)
    {
        $hoje = Carbon::today()->toDateString();

        // filtros opcionais
        $busca = trim((string) $request->get('q'));

        $base = DB::table('sacolinhas as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->whereNotNull('s.add_at')
            ->where('u.role', 'client')
            ->where('s.status', '!=', 'pedido')
            ->where(function ($q) {
                $q->whereNull('s.obs')
                  ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
            })
            ->whereRaw('DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY)) <= ?', [$hoje]);

        // Se quiser excluir status (ajuste conforme seus valores reais):
        // $base->whereNotIn('s.status', ['cancelado', 'entregue', 'enviado']);

        if ($busca !== '') {
            $base->where(function ($q) use ($busca) {
                $q->where('u.name', 'like', "%{$busca}%")
                  ->orWhere('u.nome_cliente', 'like', "%{$busca}%")
                  ->orWhere('u.codigo_cliente', 'like', "%{$busca}%")
                  ->orWhere('u.cpf', 'like', "%{$busca}%")
                  ->orWhere('u.email', 'like', "%{$busca}%");
            });
        }

        // Agrupado por cliente (user)
        $clientes = (clone $base)
            ->selectRaw('
                u.id as user_id,
                COALESCE(NULLIF(u.nome_cliente, ""), u.name) as cliente_nome,
                u.codigo_cliente,
                u.cpf,
                u.whatsapp,
                COUNT(*) as total_linhas_vencidas,
                COALESCE(SUM(s.quantity),0) as total_itens_vencidos,
                COALESCE(SUM(s.quantity * s.price),0) as valor_total_vencido,
                MIN(DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY))) as primeiro_vencimento,
                MAX(DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY))) as ultimo_vencimento
            ')
            ->groupBy('u.id', 'u.nome_cliente', 'u.name', 'u.codigo_cliente', 'u.cpf', 'u.whatsapp')
            ->orderByDesc('valor_total_vencido')
            ->paginate(20)
            ->withQueryString();

        // Totais gerais (para cards no topo)
        $totais = (clone $base)
            ->selectRaw('
                COUNT(*) as total_linhas_vencidas,
                COALESCE(SUM(s.quantity),0) as total_itens_vencidos,
                COALESCE(SUM(s.quantity * s.price),0) as valor_total_vencido,
                COUNT(DISTINCT s.user_id) as total_clientes_com_vencidos
            ')
            ->first();

		$prazoDias = 90;
		$hoje = \Carbon\Carbon::today()->toDateString();

		$userIds = $clientes->pluck('user_id')->all();

		$itens = DB::table('sacolinhas as s')
			->leftJoin('items as i', 'i.id', '=', 's.item_id')
			->whereIn('s.user_id', $userIds)
			->whereNotNull('s.add_at')
			->where('s.status', '!=', 'pedido')
			->where(function ($q) {
				$q->whereNull('s.obs')
				  ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
			})
			->whereRaw("DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY)) <= ?", [$hoje])
			->select([
				's.user_id',
				's.id as sacolinha_id',
				's.item_id',
				's.live_id',
				's.quantity',
				's.price',
				's.status',
				's.obs',
				's.add_at',
				DB::raw("DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY)) as vencimento"),

				'i.codigo as item_sku',
				'i.nome_do_produto as item_name',
				'i.marca as item_brand',
				'i.cor as item_color',
				'i.tamanho as item_size',
				'i.estado as item_estado',
				'i.image as item_image',
			])
			->orderBy('vencimento', 'asc')
			->orderBy('s.id', 'desc')
			->get()
			->groupBy('user_id');
					
		return view('admin.vencimentos', compact('clientes', 'totais', 'itens', 'busca'));
    }
	
	public function show(Request $request, User $user)
	{
		$hoje = Carbon::today()->toDateString();

		// Segurança mínima: só permitir abrir clientes
		if ($user->role !== 'client') {
			abort(404);
		}

		$base = DB::table('sacolinhas as s')
			->where('s.user_id', $user->id)
			->whereNotNull('s.add_at')
			->whereRaw('DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY)) <= ?', [$hoje]);

		// (Opcional) se quiser excluir status específicos:
		// $base->whereNotIn('s.status', ['cancelado', 'entregue', 'enviado']);

		// Totais do cliente
		$totais = (clone $base)
			->selectRaw('
				COUNT(*) as total_linhas_vencidas,
				COALESCE(SUM(s.quantity),0) as total_itens_vencidos,
				COALESCE(SUM(s.quantity * s.price),0) as valor_total_vencido,
				MIN(DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY))) as primeiro_vencimento,
				MAX(DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY))) as ultimo_vencimento
			')
			->first();

		// Linhas detalhadas (com JOIN no item para mostrar nome/foto se existir)
		// Ajuste os campos de items conforme seu schema real.
		$linhas = (clone $base)
			->leftJoin('items as i', 'i.id', '=', 's.item_id')
			->select([
				's.id',
				's.item_id',
				's.live_id',
				's.quantity',
				's.price',
				's.status',
				's.obs',
				's.add_at',
				DB::raw('DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY)) as vencimento'),
				// Mapeamento correto com seu schema de items:
				'i.nome_do_produto as item_name',  // nome do produto
				'i.codigo as item_sku',            // código/SKU
				'i.marca as item_brand',           // marca
				'i.cor as item_color',             // cor
				'i.tamanho as item_size',          // tamanho
				'i.image as item_image',           // imagem
				'i.preco as item_preco',           // preço original do item (opcional, para referência)
				'i.estado as item_estado',         // estado (novo/usado)
			])
			->orderByRaw('DATE(DATE_ADD(s.add_at, INTERVAL 90 DAY)) asc')
			->orderBy('s.id', 'desc')
			->paginate(50)
			->withQueryString();

		$clienteNome = $user->nome_cliente ?: $user->name;

		return view('admin.relatorios.vencimentos_cliente', compact(
			'user',
			'clienteNome',
			'totais',
			'linhas'
		));
	}
	

	public function sendWhatsappTemplate(Request $request, User $user)
	{
		if ($user->role !== 'client') {
			abort(404);
		}

		$rawPhone = $user->whatsapp ?: ($user->telefone_principal ?: ($user->phone ?: ''));
		$digits = preg_replace('/\D+/', '', (string) $rawPhone);
		if ($digits === '') {
			return back()->with('error', 'Cliente sem WhatsApp válido.');
		}

		// 1) Primeiro nome ({{1}})
		$nome = (string) ($user->nome_cliente ?: $user->name);
		$primeiroNome = trim(explode(' ', trim($nome))[0] ?? '');
		if ($primeiroNome === '') $primeiroNome = 'amiga(o)';

		// 2) Dados de vencidos: vencimento mais antigo e soma total ({{2}} e {{3}})
		$prazoDias = 90;
		$hoje = Carbon::today()->toDateString();

		$resumo = DB::table('sacolinhas as s')
			->where('s.user_id', $user->id)
			->whereNotNull('s.add_at')
			->whereRaw("DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY)) <= ?", [$hoje])
			->selectRaw("
				MIN(DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY))) as vencimento_mais_antigo,
				COALESCE(SUM(s.quantity * s.price),0) as valor_total_vencido
			")
			->first();

		if (empty($resumo) || empty($resumo->vencimento_mais_antigo)) {
			return back()->with('error', 'Este cliente não possui itens vencidos.');
		}

		$vencimentoMaisAntigoFmt = Carbon::parse($resumo->vencimento_mais_antigo)->format('d/m/Y'); // {{2}}
		$valorTotalFmt = number_format((float) $resumo->valor_total_vencido, 2, ',', '.');          // {{3}}

		// 3) Dispara Job com template e variáveis
		// liveId: como não é live, use 0 (ou null se você adaptar a coluna)
		SendWhatsAppMessage::dispatch(
			phoneNumber: $rawPhone,
			liveId: 0,
			userId: (int) $user->id,
			messageType: 'vencimento',
			contentSidOverride: 'HX0c43ce3c6cf6c9863deee9915965ea77',
			contentVarsOverride: ['1' => $primeiroNome, '2' => $vencimentoMaisAntigoFmt, '3' => $valorTotalFmt],
		);

		return back()->with('success', "Mensagem enviada para {$nome}.");
	}
	


	public function sendWhatsappVencidos(User $user)
	{
		if ($user->role !== 'client') {
			abort(404);
		}

		$rawPhone = $user->whatsapp ?: ($user->telefone_principal ?: ($user->phone ?: ''));
		if (empty($rawPhone)) {
			return back()->with('error', 'Cliente sem WhatsApp cadastrado.');
		}

		$prazoDias = 90;
		$hoje = Carbon::today()->toDateString();

		$resumo = DB::table('sacolinhas as s')
			->where('s.user_id', $user->id)
			->whereNotNull('s.add_at')
			->whereRaw("DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY)) <= ?", [$hoje])
			->selectRaw("
				MIN(DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY))) as vencimento_mais_antigo,
				COALESCE(SUM(s.quantity * s.price),0) as valor_total_vencido
			")
			->first();

		if (empty($resumo) || empty($resumo->vencimento_mais_antigo)) {
			return back()->with('error', 'Este cliente não possui itens vencidos.');
		}

		$nomeBase = (string) ($user->nome_cliente ?: $user->name);
		$primeiroNome = trim(explode(' ', trim($nomeBase))[0] ?? '');
		if ($primeiroNome === '') $primeiroNome = 'amiga(o)';

		$vencimentoFmt = Carbon::parse($resumo->vencimento_mais_antigo)->format('d/m/Y');
		$valorFmt = number_format((float) $resumo->valor_total_vencido, 2, ',', '.');

		// ✅ ADICIONADO: contar itens vencidos para calcular custo armazenagem
		$itensCount = DB::table('sacolinhas as s')
			->where('s.user_id', $user->id)
			->whereNotNull('s.add_at')
			->whereRaw("DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY)) <= ?", [$hoje])
			->count();

		$custoArmazenagem = number_format($itensCount * 5.00, 2, ',', '.');  // Exemplo: R$ 5 por item
		$vencimentoDia = $vencimentoFmt;  // ✅ ADICIONADO: variável usada na mensagem

		// Gera PDF (igual ao seu método da live)
		try {
			$pdfData = $this->gerarPdfVencidosSalvar($user->id);
			$pdfUrl = $pdfData['url'];
		} catch (\Throwable $e) {
			return back()->with('error', 'Erro ao gerar PDF: ' . $e->getMessage());
		}

		// Salva em live_pdfs (com context='vencidos')
		// Antes do insert, verifica se já existe um PDF de vencidos para esse usuário
		$existingPdf = DB::table('live_pdfs')
			->where('user_id', $user->id)
			->where('live_id', 1)  // ou o ID da live sistema que você criou
			->where('context', 'vencidos')
			->first();

		if ($existingPdf) {
			// Atualiza o registro existente com o novo PDF
			DB::table('live_pdfs')
				->where('id', $existingPdf->id)
				->update([
					'pdf_path' => $pdfData['path'],
					'pdf_url' => $pdfUrl,
					'status' => 'ready',
					'updated_at' => now(),
				]);
		} else {
			// Insere novo registro
			DB::table('live_pdfs')->insert([
				'user_id' => $user->id,
				'live_id' => 1,
				'pdf_path' => $pdfData['path'],
				'pdf_url' => $pdfUrl,
				'status' => 'ready',
				'context' => 'vencidos',
				'created_at' => now(),
				'updated_at' => now(),
			]);
		}

		/* Monta mensagem
		$msg = "No dia {$vencimentoDia} os itens do anexo estarão vencendo 90 dias na sacolinha.\n\n"
			 . "Você pode:\n"
			 . "1. Fazer o envio total ou parcial da sacolinha. Condição: pagamento do que for enviado.\n"
			 . "2. Manter os itens armazenados. Condição: pagamento do custo de armazenagem por mais 30 dias no valor de R$ {$custoArmazenagem}\n"
			 . "3. Liberar os itens para venda.";

		// Dispara Job
		SendWhatsAppVencidosPdf::dispatch($user->id, $rawPhone, $pdfUrl, $msg);

		return back()->with('success', "PDF gerado e mensagem de vencidos enviada para {$nomeBase}.");
		*/
				
		SendWhatsAppVencidosTemplate::dispatch(
			(int) $user->id,
			(string) $rawPhone,
			$primeiroNome,
			$vencimentoFmt,
			$valorFmt
		);

		return back()->with('success', "Template de vencimento enviado para {$nomeBase}.");		
				
		
	}
	

	public function gerarPdfVencidosSalvar(int $clienteId): array
	{
		$cliente = User::findOrFail($clienteId);

		// ✅ APENAS itens vencidos (igual ao seu método, mas sem live_id)
		$prazoDias = 90;
		$hoje = now()->toDateString();

		$itensVencidos = DB::table('sacolinhas as s')
			->join('items as i', 's.item_id', '=', 'i.id')
			->where('s.user_id', $clienteId)
			->whereNotNull('s.add_at')
			->whereRaw("DATE(DATE_ADD(s.add_at, INTERVAL {$prazoDias} DAY)) <= ?", [$hoje])
			->select(
				'i.codigo',
				'i.nome_do_produto',
				's.price',
				'i.marca',
				'i.estado',
				'i.cor',
				'i.tamanho',
				's.add_at',
				's.obs'
			)
			->get();

		if ($itensVencidos->count() === 0) {
			throw new \RuntimeException('Nenhum item vencido encontrado');
		}

		$valorTotal = (float) $itensVencidos->sum('price');
		$totalItens = (int) $itensVencidos->count();

		// Logo (igual ao seu método)
		$logoPath = public_path('images/LogoColorida sem fundo.png');
		$logoDataUri = null;

		if (file_exists($logoPath)) {
			$logoMime = mime_content_type($logoPath) ?: 'image/png';
			$logoBase64 = base64_encode(file_get_contents($logoPath));
			$logoDataUri = "data:{$logoMime};base64,{$logoBase64}";
		}

		// ✅ HTML ADAPTADO para vencidos (igual ao seu, mas sem "Live" e com vencimento)
		$html = '
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title>Itens Vencidos - ' . htmlspecialchars($cliente->name) . '</title>
			<style>
			  body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
			  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
			  th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
			  th { background: #f0f0f0; font-weight: bold; }
			  .header { margin-bottom: 20px; }
			  .header-grid { width: 100%; border-collapse: collapse; }
			  .header-grid, .header-grid td, .header-grid th { border: none !important; }
			  .header-grid td { padding: 0 !important; }
			  .header-grid { width: 100%; }
			  .logo-cell { width: 120px; vertical-align: top; }
			  .logo { height: 60px; }
			  .title-cell { text-align: center; vertical-align: top; }
			  .title-cell h1 { margin: 0 0 6px 0; }
			  .title-cell p { margin: 3px 0; }
			  .total { background: #e8f4fd; font-weight: bold; }
			  .preco { text-align: right; }
			</style>
		</head>
		<body>
			<div class="header">
			  <table class="header-grid">
				<tr>
				  <td class="logo-cell">
					' . ($logoDataUri ? '<img class="logo" src="' . $logoDataUri . '" />' : '') . '
				  </td>
				  <td class="title-cell">
					<h1>Itens Vencidos - Sacolinha Mania</h1>
					<p><strong>Cliente:</strong> ' . htmlspecialchars($cliente->name) . '</p>
					<p><strong>Data de Geração:</strong> ' . date('d/m/Y H:i:s') . '</p>
					<p><strong>Total de Itens Vencidos:</strong> ' . $totalItens . '</p>
					<p><strong>Valor Total Vencido:</strong> R$ ' . number_format($valorTotal, 2, ',', '.') . '</p>
				  </td>
				  <td style="width:120px;"></td>
				</tr>
			  </table>
			</div>

			<table>
				<thead>
					<tr>
						<th>Código</th>
						<th>Produto</th>
						<th>Detalhes</th>
						<th class="preco">Preço</th>
						<th>Data de Adição</th>
					</tr>
				</thead>
				<tbody>';

		foreach ($itensVencidos as $item) {
			$detalhes = [];
			if (!empty($item->marca))  $detalhes[] = $item->marca;
			if (!empty($item->estado)) $detalhes[] = $item->estado;
			if (!empty($item->cor))    $detalhes[] = $item->cor;
			if (!empty($item->tamanho)) $detalhes[] = 'Tam: ' . $item->tamanho;

			// ✅ inclui OBS (se existir)
			if (!empty($item->obs)) {
				$detalhes[] = 'Obs: ' . $item->obs;
			}

			$html .= '<tr>
				<td>' . htmlspecialchars($item->codigo ?? 'N/A') . '</td>
				<td><strong>' . htmlspecialchars($item->nome_do_produto ?? '') . '</strong></td>
				<td>' . htmlspecialchars(implode(' • ', $detalhes)) . '</td>
				<td class="preco">R$ ' . number_format((float)$item->price, 2, ',', '.') . '</td>
				<td>' . \Carbon\Carbon::parse($item->add_at)->format('d/m/Y') . '</td>
			</tr>';
		}

		$html .= '
				</tbody>
				<tfoot>
					<tr class="total">
						<td colspan="4"><strong>TOTAL GERAL:</strong></td>
						<td class="preco"><strong>R$ ' . number_format($valorTotal, 2, ',', '.') . '</strong></td>
					</tr>
				</tfoot>
			</table>
		</body>
		</html>';

		$pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

		$relativePath = "vencidos/vencidos_cliente_{$clienteId}_" . now()->format('Ymd_His') . ".pdf";
		Storage::disk('public')->put($relativePath, $pdf->output());

		$url = asset("storage/{$relativePath}");

		return [
			'path' => $relativePath,
			'url' => $url,
		];
	}	
	
	
}