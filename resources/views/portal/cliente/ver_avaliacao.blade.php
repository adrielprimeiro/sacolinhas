@extends('layouts.portal-cliente')

@section('title', 'Lote #' . str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) . ' - Portal | Mania')

@section('content')
<style>
:root {
  --pink:#F5148C; --pink-dark:#C90E72; --pink-soft:#FDE7F3; --pink-soft-40:rgba(253,231,243,.45);
  --ink:#141414; --paper:#FBF9FA; --gray-500:#6b7280; --gray-600:#4b5563; --gray-700:#374151;
  --purple:#8A52E8; --purple-light:#B37FFB;
  --radius:24px;
  --shadow-card:0 4px 24px -6px rgba(20,20,20,.08);
  --shadow-mania:0 12px 40px -12px rgba(245,20,140,.25);
}

/* CARD BASE */
.card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow-card);border:1px solid #fce7f3}

/* LOTE HEADER */
.lote-head{padding:12px 16px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px}
.lote-id{display:flex;align-items:center;gap:16px}
.lote-icon{width:52px;height:52px;border-radius:16px;background:var(--pink-soft);color:var(--pink);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
h1{font-family:'Outfit',sans-serif;font-weight:800;font-size:22px;letter-spacing:-.3px;color:var(--ink);}
.lote-meta{display:flex;flex-wrap:wrap;gap:16px;margin-top:6px;font-size:13px;color:var(--gray-500)}
.lote-meta i{color:var(--pink);margin-right:5px}
.badges{display:flex;flex-wrap:wrap;gap:8px}
.badge{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:999px;font-size:12px;font-weight:700;font-family:'Outfit',sans-serif}
.badge-analise{background:#FEF9C3;color:#A16207}
.badge-pendente{background:#f3f4f6;color:var(--gray-600)}
.back-btn{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:var(--gray-600);background:#fff;border:1px solid #fce7f3;border-radius:999px;padding:10px 20px;transition:background .2s,color .2s}
.back-btn:hover{background:var(--pink-soft);color:var(--pink)}

/* SOBREPOSIÇÃO DE DISTANCIAMENTO */
main.container {
  margin-top: 12px !important;
  padding-top: 0px !important;
}

/* REPASSE */
.section-card{padding:12px 16px}
h3.section-label{font-family:'Outfit',sans-serif;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;color:var(--gray-500);margin-bottom:12px}
.repasse-grid{display:grid;grid-template-columns:1fr;gap:16px}
@media(min-width:640px){.repasse-grid{grid-template-columns:1fr 1fr}}
.repasse-option{position:relative;overflow:hidden;border-radius:12px;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.repasse-club{background:linear-gradient(135deg,#B37FFB 0%,#8A52E8 100%);color:#fff;box-shadow:0 6px 18px -6px rgba(138,82,232,.4)}
.repasse-club .blob-mini{position:absolute;top:-24px;right:-24px;width:96px;height:96px;border-radius:50%;background:rgba(255,255,255,.14)}
.repasse-pix{background:linear-gradient(135deg,#FFF0F7 0%,#FCDCEC 100%);border:1px solid #f9c6e0}
.option-label{font-size:11px;font-weight:600}
.repasse-club .option-label{color:rgba(255,255,255,.9)}
.repasse-pix .option-label{color:var(--gray-700)}
.option-name{font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;margin-top:2px}
.repasse-pix .option-name{color:var(--ink)}
.option-value{font-family:'Outfit',sans-serif;font-weight:800;font-size:18px;position:relative}
.repasse-pix .option-value{color:var(--pink)}
.repasse-note{margin-top:16px;display:flex;gap:8px;align-items:center;font-size:12px;color:var(--gray-600);line-height:1.4}
.repasse-note i{color:var(--pink)}

/* TABELA */
.table-card{overflow:hidden}
.table-head{padding:12px 16px;border-bottom:1px solid #fdf2f8;display:flex;align-items:center;gap:10px}
.table-head i{color:var(--pink)}
h2{font-family:'Outfit',sans-serif;font-weight:700;font-size:17px}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:560px}
thead th{background:var(--pink-soft-40);padding:8px 16px;font-family:'Outfit',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray-600);text-align:left}
thead th.center, td.center{text-align:center}
tbody td{padding:10px 16px;border-top:1px solid #fdf2f8;font-size:14px;color:var(--gray-700)}
tbody tr{transition:background .15s}
tbody tr:hover{background:var(--pink-soft-40)}
.item-name{font-weight:700;color:var(--ink)}
.item-cat{font-size:12px;color:var(--gray-500);margin-top:2px}
.conserva{font-family:'Outfit',sans-serif;font-weight:800;color:var(--pink)}

.fade-out{opacity:0;transition:opacity .4s ease}
@media(max-width:640px){.user-chip span.name{display:none}}
</style>

<div class="flex flex-col gap-4 w-full max-w-[1024px] mx-auto pb-8 pt-0">
  <!-- Cabeçalho do Lote -->
  <section class="card lote-head">
    <div class="lote-id">
      <div class="lote-icon"><i class="fas fa-layer-group"></i></div>
      <div>
        <h1>Lote #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}</h1>
        <div class="lote-meta">
          <span><i class="far fa-calendar-alt"></i>{{ \Carbon\Carbon::parse($avaliacao->data_avaliacao)->format('d/m/y') }}</span>
          <span><i class="fas fa-shirt"></i>{{ $avaliacao->items->count() }} {{ $avaliacao->items->count() === 1 ? 'peça' : 'peças' }}</span>
        </div>
      </div>
    </div>
    <div class="badges">
      @if($avaliacao->status === 'finalizada')
        <span class="badge" style="background:#DCFCE7; color:#15803D;"><i class="fas fa-check-circle"></i> Lote: Finalizado</span>
        @if($avaliacao->pagamento_escolhido === 'credito')
          <span class="badge" style="background:#F3E8FF; color:#6B21A8;"><i class="fas fa-gift"></i> Pagamento: Créditos</span>
        @else
          <span class="badge" style="background:#DBEAFE; color:#1E40AF;"><i class="fas fa-money-bill-wave"></i> Pagamento: Dinheiro/PIX</span>
        @endif
      @elseif($avaliacao->status === 'cancelada')
        <span class="badge" style="background:#FEE2E2; color:#B91C1C;"><i class="fas fa-times-circle"></i> Lote: Cancelado</span>
      @else
        <span class="badge badge-analise"><i class="fas fa-hourglass-half"></i> Lote: Em Análise</span>
        <span class="badge badge-pendente"><i class="fas fa-clock"></i> Pagamento: Pendente</span>
      @endif
    </div>
  </section>

  <!-- Total de Repasse -->
  <section class="card section-card">
    <h3 class="section-label">Total de Repasse Consolidado</h3>
    <div class="repasse-grid">
      @if($avaliacao->status === 'finalizada')
        @if($avaliacao->pagamento_escolhido === 'credito')
          <div class="repasse-option repasse-club" style="grid-column: 1 / -1;">
            <div class="blob-mini"></div>
            <div>
              <div class="option-label">Repasse Definido</div>
              <div class="option-name"><i class="fas fa-gift"></i> Crédito na Loja</div>
            </div>
            <div class="option-value">R$ {{ number_format($avaliacao->total_payout, 2, ',', '.') }}</div>
          </div>
        @else
          <div class="repasse-option repasse-pix" style="grid-column: 1 / -1; background: linear-gradient(135deg, #FFF0F7 0%, #FCDCEC 100%); border-color: #f9c6e0;">
            <div>
              <div class="option-label" style="color: var(--gray-700);">Repasse Definido</div>
              <div class="option-name" style="color: var(--ink);"><i class="fas fa-money-bill-wave"></i> Dinheiro / PIX</div>
            </div>
            <div class="option-value" style="color: var(--pink);">R$ {{ number_format($avaliacao->total_payout, 2, ',', '.') }}</div>
          </div>
        @endif
      @else
        <div class="repasse-option repasse-club">
          <div class="blob-mini"></div>
          <div>
            <div class="option-label">Opção 1</div>
            <div class="option-name"><i class="fas fa-gift"></i> Crédito na Loja</div>
          </div>
          <div class="option-value">R$ {{ number_format($avaliacao->items->sum('payout_credito'), 2, ',', '.') }}</div>
        </div>
        <div class="repasse-option repasse-pix">
          <div>
            <div class="option-label">Opção 2</div>
            <div class="option-name"><i class="fas fa-money-bill-wave"></i> Dinheiro / PIX</div>
          </div>
          <div class="option-value">R$ {{ number_format($avaliacao->items->sum('payout_dinheiro'), 2, ',', '.') }}</div>
        </div>
      @endif
    </div>
    @if($avaliacao->status !== 'finalizada')
      <div class="repasse-note">
        <i class="fas fa-circle-info"></i>
        <span>A escolha da forma de recebimento será definida com o atendimento.</span>
      </div>
    @endif
  </section>

  <!-- Relação de Peças -->
  <section class="card table-card">
    <div class="table-head">
      <i class="fas fa-shirt"></i>
      <h2>Relação de Peças</h2>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Peça / Categoria</th>
            <th class="center">Marca</th>
            <th class="center">Cor / Tam</th>
            <th class="center">Conservação</th>
          </tr>
        </thead>
        <tbody>
          @foreach($avaliacao->items as $item)
            <tr>
              <td>
                <div class="item-name">{{ $item->nome }}</div>
                <div class="item-cat">{{ $item->categoria->name ?? 'Sem categoria' }}</div>
              </td>
              <td class="center capitalize">
                {{ $item->marca === 'sem_marca' ? 'Sem Marca' : ($item->marca === 'de_marca' ? 'De Marca' : 'Farm') }}
              </td>
              <td class="center">
                {{ $item->cor ?: '-' }} / {{ $item->tamanho ?: '-' }}
              </td>
              <td class="center">
                <span class="conserva">{{ $item->estado }}/10</span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection
