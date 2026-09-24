<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brecho;
use App\Models\NotaFiscal;
use App\Models\Pedido;
use App\Services\Fiscal\NfeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminNotaFiscalController extends Controller
{
    protected NfeService $nfeService;

    public function __construct(NfeService $nfeService)
    {
        $this->nfeService = $nfeService;
    }

    /**
     * Emite a NF-e para um pedido.
     */
    public function emitir(Pedido $pedido)
    {
        if (auth()->check() && auth()->user()->isBrechoParceiro()) {
            if ($pedido->brecho_id != auth()->user()->brecho_id) {
                abort(403, 'Acesso não autorizado.');
            }
        }

        try {
            $notaFiscal = $this->nfeService->emitirParaPedido($pedido);

            if ($notaFiscal->isAutorizada()) {
                return redirect()->back()->with('success', "NF-e nº {$notaFiscal->numero} autorizada com sucesso pela SEFAZ!");
            }

            return redirect()->back()->with('error', "Erro na emissão da NF-e: " . ($notaFiscal->xMotivo ?: 'Rejeição na SEFAZ.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Falha ao emitir NF-e: " . $e->getMessage());
        }
    }

    /**
     * Exibe o PDF do DANFE no navegador.
     */
    public function danfe(NotaFiscal $notaFiscal)
    {
        if (auth()->check() && auth()->user()->isBrechoParceiro()) {
            if ($notaFiscal->brecho_id != auth()->user()->brecho_id) {
                abort(403, 'Acesso não autorizado.');
            }
        }

        try {
            $pdfContent = $this->nfeService->renderDanfePdf($notaFiscal);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"DANFE-{$notaFiscal->chave_acesso}.pdf\"",
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Erro ao gerar DANFE: " . $e->getMessage());
        }
    }

    /**
     * Faz o download do XML autorizado da NF-e.
     */
    public function xml(NotaFiscal $notaFiscal)
    {
        if (auth()->check() && auth()->user()->isBrechoParceiro()) {
            if ($notaFiscal->brecho_id != auth()->user()->brecho_id) {
                abort(403, 'Acesso não autorizado.');
            }
        }

        $xml = $notaFiscal->xml_autorizado ?: $notaFiscal->xml_enviado;
        if (empty($xml)) {
            return redirect()->back()->with('error', 'Conteúdo XML não encontrado para esta nota.');
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$notaFiscal->chave_acesso}-nfe.xml\"",
        ]);
    }

    /**
     * Cancela uma NF-e perante a SEFAZ.
     */
    public function cancelar(Request $request, NotaFiscal $notaFiscal)
    {
        if (auth()->check() && auth()->user()->isBrechoParceiro()) {
            if ($notaFiscal->brecho_id != auth()->user()->brecho_id) {
                abort(403, 'Acesso não autorizado.');
            }
        }

        $request->validate([
            'justificativa' => ['required', 'string', 'min:15', 'max:255'],
        ], [
            'justificativa.min' => 'A justificativa de cancelamento deve ter no mínimo 15 caracteres conforme exigido pela SEFAZ.',
        ]);

        try {
            $this->nfeService->cancelarNfe($notaFiscal, $request->input('justificativa'));
            return redirect()->back()->with('success', 'Nota Fiscal cancelada com sucesso junto à SEFAZ.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Falha ao cancelar NF-e: ' . $e->getMessage());
        }
    }

    /**
     * Exibe a tela de configurações fiscais do Brechó (Certificado A1, Série, etc.).
     */
    public function configuracoes()
    {
        $brechoId = (auth()->check() && auth()->user()->isBrechoParceiro())
            ? auth()->user()->brecho_id
            : 1;

        $brecho = Brecho::findOrNew($brechoId);

        return view('admin.fiscal.configuracoes', compact('brecho'));
    }

    /**
     * Salva as configurações fiscais e o Certificado A1 do Brechó.
     */
    public function salvarConfiguracoes(Request $request)
    {
        $brechoId = (auth()->check() && auth()->user()->isBrechoParceiro())
            ? auth()->user()->brecho_id
            : 1;

        $brecho = Brecho::findOrFail($brechoId);

        $validated = $request->validate([
            'razao_social'        => ['nullable', 'string', 'max:100'],
            'documento'           => ['nullable', 'string', 'max:20'],
            'inscricao_estadual'  => ['nullable', 'string', 'max:20'],
            'regime_tributario'   => ['required', 'integer'],
            'logradouro'          => ['nullable', 'string', 'max:100'],
            'numero_endereco'     => ['nullable', 'string', 'max:20'],
            'complemento'         => ['nullable', 'string', 'max:50'],
            'bairro'              => ['nullable', 'string', 'max:50'],
            'codigo_municipio'    => ['nullable', 'string', 'max:7'],
            'municipio'           => ['nullable', 'string', 'max:50'],
            'uf'                  => ['nullable', 'string', 'size:2'],
            'cep'                 => ['nullable', 'string', 'max:10'],
            'nfe_serie'           => ['required', 'string', 'max:3'],
            'nfe_ultimo_numero'   => ['required', 'integer', 'min:0'],
            'nfe_ambiente'        => ['required', 'integer', 'in:1,2'],
            'certificado_senha'   => ['nullable', 'string'],
            'certificado_file'    => ['nullable', 'file', 'mimes:pfx,bin,txt'],
        ]);

        if ($request->hasFile('certificado_file')) {
            $path = $request->file('certificado_file')->store('fiscal/certificados');
            $validated['certificado_path'] = $path;
        }

        $brecho->update(collect($validated)->except('certificado_file')->toArray());

        return redirect()->back()->with('success', 'Configurações fiscais salvas com sucesso!');
    }
}
