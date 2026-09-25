<?php

namespace App\Services\Fiscal;

use App\Models\Brecho;
use App\Models\NotaFiscal;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use NFePHP\Common\Certificate;
use NFePHP\DA\NFe\Danfe;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use stdClass;

class NfeService
{
    public function __construct(private readonly CertificateLoader $certificateLoader)
    {
    }

    /**
     * Orquestra a emissão completa da NF-e para um Pedido.
     */
    public function emitirParaPedido(Pedido $pedido): NotaFiscal
    {
        $pedido->load(['user', 'brecho']);
        $brecho = $pedido->brecho ?? Brecho::find(1) ?? new Brecho();

        // 1. Determinar número e série da NF-e
        $serie = $brecho->nfe_serie ?: '1';
        $ultimoNumero = $brecho->nfe_ultimo_numero ?: 0;
        $proximoNumero = $ultimoNumero + 1;

        // 2. Criar ou obter o registro da Nota Fiscal local
        $notaFiscal = NotaFiscal::firstOrCreate(
            ['pedido_id' => $pedido->id, 'tipo' => 'nfe'],
            [
                'brecho_id'       => $pedido->brecho_id ?? 1,
                'modelo'          => 55,
                'serie'           => $serie,
                'numero'          => $proximoNumero,
                'status'          => 'pendente',
                'valor_total'     => $pedido->valor_total,
                'valor_produtos'  => $pedido->valor_total - ($pedido->valor_frete ?? 0) - ($pedido->valor_desconto ?? 0),
                'valor_frete'     => $pedido->valor_frete ?? 0,
                'valor_desconto'  => $pedido->valor_desconto ?? 0,
                'data_emissao'    => now(),
            ]
        );

        if ($notaFiscal->isAutorizada()) {
            return $notaFiscal;
        }

        try {
            // 3. Montar o XML da NF-e
            $nfe = $this->montarXml($pedido, $brecho, $notaFiscal->numero, $serie);
            $xmlDesassinado = $nfe->getXML();

            if (empty($xmlDesassinado)) {
                $erros = implode('; ', $nfe->getErrors());
                throw new \Exception("Erro na validação da estrutura do XML da NF-e: {$erros}");
            }

            // 4. Carregar Ferramentas (Tools) e Certificado Digital
            $tools = $this->getTools($brecho);

            // 5. Assinar digitalmente o XML
            $xmlAssinado = $tools->signNFe($xmlDesassinado);
            $chave = $nfe->getChave();

            if (!preg_match('/^\d{44}$/', $chave)) {
                throw new \RuntimeException('O NFePHP não gerou uma chave de acesso válida para a NF-e.');
            }

            $notaFiscal->update([
                'chave_acesso' => $chave,
                'xml_enviado'  => $xmlAssinado,
                'status'       => 'assinada',
            ]);

            // 6. Transmitir para a SEFAZ (Envio Síncrono)
            $idLote = str_pad($notaFiscal->id, 15, '0', STR_PAD_LEFT);
            $respostaXml = $tools->sefazEnviaLote([$xmlAssinado], $idLote, 1);

            // 7. Processar retorno da SEFAZ
            $this->processarRetornoSefaz($notaFiscal, $tools, $xmlAssinado, $respostaXml, $brecho);

            return $notaFiscal->fresh();
        } catch (\Exception $e) {
            Log::error("Falha ao emitir NF-e para o pedido {$pedido->id}: " . $e->getMessage());

            $notaFiscal->update([
                'status'  => 'rejeitada',
                'xMotivo' => $e->getMessage(),
            ]);

            return $notaFiscal;
        }
    }

    /**
     * Constrói o documento XML usando a classe Make do NFePHP.
     */
    public function montarXml(Pedido $pedido, Brecho $brecho, int $numeroNota, string $serie): Make
    {
        $nfe = new Make();
        $ambiente = (int) ($brecho->nfe_ambiente ?: config('fiscal.ambiente', 2)); // 1=Prod, 2=Homolog
        $ufEmitente = strtoupper($brecho->uf ?: config('fiscal.uf', 'SC'));
        $cUfEmitente = IbgeHelper::getUfCode($ufEmitente);
        $cMunEmitente = $brecho->codigo_municipio ?: IbgeHelper::getCodigoMunicipio($brecho->cep, $brecho->municipio, $ufEmitente);

        $ufDestinatario = strtoupper($pedido->estado_entrega ?: ($pedido->user->estado ?? 'SC'));
        $isInterestadual = ($ufEmitente !== $ufDestinatario);

        // 1. Tag infNFe
        $std = new stdClass();
        $std->versao = '4.00';
        $nfe->taginfNFe($std);

        // 2. Tag ide (Identificação da NF-e)
        $std = new stdClass();
        $std->cUF = (int) $cUfEmitente;
        $std->cNF = str_pad((string) mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp = 'VENDA DE MERCADORIA';
        $std->mod = 55; // NF-e
        $std->serie = (int) $serie;
        $std->nNF = $numeroNota;
        $std->dhEmi = now()->format('Y-m-d\TH:i:sP');
        $std->tpNF = 1; // 1 = Saída
        $std->idDest = $isInterestadual ? 2 : 1; // 1 = Interna, 2 = Interestadual
        $std->cMunFG = (int) $cMunEmitente;
        $std->tpImp = 1; // Retrato
        $std->tpEmis = 1; // Normal
        $std->tpAmb = $ambiente;
        $std->finNFe = 1; // Normal
        $std->indFinal = 1; // Consumidor final
        $std->indPres = 2; // Operação pela internet
        $std->indIntermed = 0; // Operação sem intermediador/marketplace
        $std->procEmi = 0; // Aplicativo do contribuinte
        $std->verProc = 'MinhaMania_1.0';
        $nfe->tagide($std);

        // 3. Tag emit (Emitente / Brechó)
        $std = new stdClass();
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $brecho->documento ?: env('STORE_DOCUMENT', '12345678000199'));
        $std->CNPJ = $cnpjLimpo;
        $std->xNome = mb_substr($brecho->razao_social ?: $brecho->nome ?: 'BRECHO MINHA MANIA LTDA', 0, 60);
        $std->xFant = mb_substr($brecho->nome ?: 'MINHA MANIA', 0, 60);
        $std->IE = preg_replace('/[^0-9]/', '', $brecho->inscricao_estadual ?: 'ISENTO');
        $std->CRT = (int) ($brecho->regime_tributario ?: 1); // 1 = Simples Nacional
        $nfe->tagemit($std);

        // 4. Tag enderEmit (Endereço do Emitente)
        $std = new stdClass();
        $std->xLgr = mb_substr($brecho->logradouro ?: env('STORE_ADDRESS', 'Rua Principal'), 0, 60);
        $std->nro = mb_substr($brecho->numero_endereco ?: env('STORE_NUMBER', '100'), 0, 60);
        $std->xCpl = mb_substr($brecho->complemento ?: '', 0, 60);
        $std->xBairro = mb_substr($brecho->bairro ?: env('STORE_DISTRICT', 'Centro'), 0, 60);
        $std->cMun = (int) $cMunEmitente;
        $std->xMun = mb_substr($brecho->municipio ?: env('STORE_CITY', 'Biguaçu'), 0, 60);
        $std->UF = $ufEmitente;
        $std->CEP = preg_replace('/[^0-9]/', '', $brecho->cep ?: env('STORE_CEP', '88160000'));
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $std->fone = preg_replace('/[^0-9]/', '', $brecho->telefone ?: $brecho->whatsapp ?: '48999999999');
        $nfe->tagenderEmit($std);

        // 5. Tag dest (Destinatário / Cliente)
        $std = new stdClass();
        $docCliente = preg_replace('/[^0-9]/', '', $pedido->user->cpf ?? '');
        if (strlen($docCliente) === 14) {
            $std->CNPJ = $docCliente;
        } else {
            $std->CPF = !empty($docCliente) ? $docCliente : '00000000000';
        }

        // Em ambiente de homologação a SEFAZ exige nome padrão
        if ($ambiente === 2) {
            $std->xNome = 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
        } else {
            $std->xNome = mb_substr($pedido->user->name ?? 'CONSUMIDOR FINAL', 0, 60);
        }

        $std->indIEDest = 9; // Não Contribuinte
        $std->email = $pedido->user->email ?? null;
        $nfe->tagdest($std);

        // 6. Tag enderDest (Endereço de Entrega)
        $cepDestino = preg_replace('/[^0-9]/', '', $pedido->cep_entrega ?: ($pedido->user->cep ?? '88160000'));
        $cidadeDestino = $pedido->cidade_entrega ?: ($pedido->user->cidade ?? 'Florianópolis');
        $cMunDestino = IbgeHelper::getCodigoMunicipio($cepDestino, $cidadeDestino, $ufDestinatario);

        $std = new stdClass();
        $std->xLgr = mb_substr($pedido->endereco_entrega ?: ($pedido->user->endereco ?? 'Rua de Entrega'), 0, 60);
        $std->nro = mb_substr($pedido->user->numero_endereco ?? 'S/N', 0, 60);
        $std->xCpl = mb_substr($pedido->user->complemento ?? '', 0, 60);
        $std->xBairro = mb_substr($pedido->user->bairro ?? 'Centro', 0, 60);
        $std->cMun = (int) $cMunDestino;
        $std->xMun = mb_substr($cidadeDestino, 0, 60);
        $std->UF = $ufDestinatario;
        $std->CEP = $cepDestino;
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $std->fone = preg_replace('/[^0-9]/', '', $pedido->user->phone ?? $pedido->user->whatsapp ?? '');
        $nfe->tagenderDest($std);

        // 7. Produtos (Itens da Sacolinha / Pedido)
        $itemsPedido = DB::table('items_pedido')
            ->join('items', 'items.id', '=', 'items_pedido.item_id')
            ->where('items_pedido.pedido_id', $pedido->id)
            ->where('items_pedido.status_item', 'ativo')
            ->select('items.*', 'items_pedido.quantidade', 'items_pedido.preco_unitario')
            ->get();

        $valorFrete = round((float) ($pedido->valor_frete ?? 0), 2);
        $valorDesconto = round((float) ($pedido->valor_desconto ?? 0), 2);

        // Se não houver itens cadastrados na tabela items_pedido, adiciona linha sintética com o total
        if ($itemsPedido->isEmpty()) {
            $itemsPedido = collect([(object) [
                'id'                 => 1,
                'codigo'             => 'P-01',
                'nome_do_produto'    => 'PECA DE VESTUARIO DIVERSAS',
                'quantidade'         => 1,
                'preco_unitario'     => round(
                    (float) $pedido->valor_total - $valorFrete + $valorDesconto,
                    2
                ),
                'ncm'                => config('fiscal.padroes.ncm_vestuario', '61091000'),
                'unidade_tributavel' => 'UN',
                'origem'             => 0,
            ]]);
        }

        $baseParaDistribuicao = round((float) $itemsPedido->sum(
            static fn ($item): float => round(
                (float) ($item->quantidade ?: 1) * (float) ($item->preco_unitario ?: 0),
                2
            )
        ), 2);

        if ($baseParaDistribuicao <= 0 && ($valorFrete > 0 || $valorDesconto > 0)) {
            throw new \RuntimeException('Não foi possível distribuir frete/desconto porque os itens do pedido não possuem valor.');
        }

        $totalProdutos = 0.0;
        $itemIndex = 0;
        $valorAcumuladoItens = 0.0;
        $freteDistribuido = 0.0;
        $descontoDistribuido = 0.0;

        foreach ($itemsPedido as $item) {
            $itemIndex++;
            $qtd = (float) ($item->quantidade ?: 1);
            $vUnit = (float) ($item->preco_unitario ?: 0);
            $vProd = round($qtd * $vUnit, 2);
            $totalProdutos += $vProd;

            if ($baseParaDistribuicao > 0 && ($valorFrete > 0 || $valorDesconto > 0)) {
                $valorAcumuladoItens += max(0, $vProd);
                $freteAcumulado = round(
                    $valorFrete * ($valorAcumuladoItens / $baseParaDistribuicao),
                    2
                );
                $descontoAcumulado = round(
                    $valorDesconto * ($valorAcumuladoItens / $baseParaDistribuicao),
                    2
                );
                $freteItem = round($freteAcumulado - $freteDistribuido, 2);
                $descontoItem = round($descontoAcumulado - $descontoDistribuido, 2);
                $freteDistribuido = $freteAcumulado;
                $descontoDistribuido = $descontoAcumulado;
            } else {
                $freteItem = 0.0;
                $descontoItem = 0.0;
            }

            $cfop = $isInterestadual
                ? ($item->cfop ?: config('fiscal.padroes.cfop_interestadual', '6102'))
                : ($item->cfop ?: config('fiscal.padroes.cfop_interno', '5102'));

            // Tag prod
            $std = new stdClass();
            $std->item = $itemIndex;
            $std->cProd = mb_substr($item->codigo ?: "IT-{$item->id}", 0, 60);
            $std->cEAN = 'SEM GTIN';
            $std->xProd = mb_substr($item->nome_do_produto ?: 'PECA DE VESTUARIO', 0, 120);
            $std->NCM = preg_replace('/[^0-9]/', '', $item->ncm ?: config('fiscal.padroes.ncm_vestuario', '61091000'));
            $std->CFOP = $cfop;
            $std->uCom = mb_substr($item->unidade_tributavel ?: 'UN', 0, 6);
            $std->qCom = $qtd;
            $std->vUnCom = $vUnit;
            $std->vProd = $vProd;
            $std->cEANTrib = 'SEM GTIN';
            $std->uTrib = mb_substr($item->unidade_tributavel ?: 'UN', 0, 6);
            $std->qTrib = $qtd;
            $std->vUnTrib = $vUnit;
            $std->vFrete = $freteItem > 0 ? $freteItem : null;
            $std->vDesc = $descontoItem > 0 ? $descontoItem : null;
            $std->indTot = 1;
            $nfe->tagprod($std);

            // Tag imposto
            $std = new stdClass();
            $std->item = $itemIndex;
            $nfe->tagimposto($std);

            // Tag ICMS para Simples Nacional (CSOSN 102 - Tributada pelo Simples sem permissão de crédito)
            $std = new stdClass();
            $std->item = $itemIndex;
            $std->orig = (int) ($item->origem ?? 0);
            $std->CSOSN = '102';
            $nfe->tagICMSSN($std);

            // Tag PIS (PISNT - Não Tributado)
            $std = new stdClass();
            $std->item = $itemIndex;
            $std->CST = '07'; // Operação isenta da contribuição
            $nfe->tagPIS($std);

            // Tag COFINS (COFINSNT - Não Tributado)
            $std = new stdClass();
            $std->item = $itemIndex;
            $std->CST = '07';
            $nfe->tagCOFINS($std);
        }

        $totalProdutos = round($totalProdutos, 2);
        $freteDistribuido = round($freteDistribuido, 2);
        $descontoDistribuido = round($descontoDistribuido, 2);

        if ($freteDistribuido !== $valorFrete || $descontoDistribuido !== $valorDesconto) {
            throw new \RuntimeException('O frete ou desconto do pedido não pôde ser distribuído corretamente entre os itens.');
        }

        // 8. Tag ICMSTot (Totais da Nota Fiscal)
        $valorTotalNf = round($totalProdutos + $valorFrete - $valorDesconto, 2);

        if (abs($valorTotalNf - (float) $pedido->valor_total) > 0.01) {
            throw new \RuntimeException(
                "O total calculado dos itens ({$valorTotalNf}) não corresponde ao valor total do pedido ({$pedido->valor_total})."
            );
        }

        $std = new stdClass();
        $std->vBC = 0.00;
        $std->vICMS = 0.00;
        $std->vICMSDeson = 0.00;
        $std->vFCPUFDest = 0.00;
        $std->vICMSUFDest = 0.00;
        $std->vICMSUFRemet = 0.00;
        $std->vFCP = 0.00;
        $std->vBCST = 0.00;
        $std->vST = 0.00;
        $std->vFCPST = 0.00;
        $std->vFCPSTRet = 0.00;
        $std->vProd = $totalProdutos;
        $std->vFrete = $valorFrete;
        $std->vSeg = 0.00;
        $std->vDesc = $valorDesconto;
        $std->vII = 0.00;
        $std->vIPI = 0.00;
        $std->vIPIDevol = 0.00;
        $std->vPIS = 0.00;
        $std->vCOFINS = 0.00;
        $std->vOutro = 0.00;
        $std->vNF = $valorTotalNf;
        $std->vTotTrib = 0.00;
        $nfe->tagICMSTot($std);

        // 9. Tag transp (Transporte e Frete)
        $std = new stdClass();
        $std->modFrete = $valorFrete > 0 ? 0 : 9; // 0 = Remetente (CIF), 9 = Sem Ocorrência de Transporte
        $nfe->tagtransp($std);

        // 10. Tag pag (Pagamento)
        $std = new stdClass();
        $std->vTroco = 0.00;
        $nfe->tagpag($std);

        // Detalhe do Pagamento
        $formaPgto = strtolower($pedido->forma_pagamento ?? 'pix');
        $tPag = '17'; // PIX por padrão
        if (str_contains($formaPgto, 'cart')) {
            $tPag = '03'; // Cartão de Crédito
        } elseif (str_contains($formaPgto, 'boleto')) {
            $tPag = '15';
        } elseif (str_contains($formaPgto, 'dinheiro')) {
            $tPag = '01';
        }

        $std = new stdClass();
        $std->indPag = 0; // Pagamento à vista
        $std->tPag = $tPag;
        $std->vPag = $valorTotalNf;
        $nfe->tagdetPag($std);

        // 11. Tag infAdic (Informações Complementares de Interesse do Fisco)
        $std = new stdClass();
        $std->infCpl = "DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. NAO GERA DIREITO A CREDITO FISCAL DE IPI. Pedido Ref: {$pedido->numero_pedido}";
        $nfe->taginfAdic($std);

        // 12. Responsável técnico pelo sistema emissor
        $this->adicionarResponsavelTecnico($nfe, $brecho, $cnpjLimpo, $ambiente);

        return $nfe;
    }

    private function adicionarResponsavelTecnico(
        Make $nfe,
        Brecho $brecho,
        string $cnpjEmitente,
        int $ambiente
    ): void {
        $config = (array) config('fiscal.responsavel_tecnico', []);

        $cnpjConfigurado = preg_replace('/\D/', '', (string) ($config['cnpj'] ?? ''));
        $cnpjResponsavel = $cnpjConfigurado ?: ($ambiente === 2 ? $cnpjEmitente : '');

        if (!preg_match('/^\d{14}$/', $cnpjResponsavel)) {
            throw new \RuntimeException(
                'Informe um CNPJ válido para o responsável técnico em NFE_RESP_TECNICO_CNPJ antes de emitir em produção.'
            );
        }

        $contato = trim((string) ($config['contato'] ?? ''))
            ?: 'Suporte Fiscal - ' . ($brecho->nome ?: 'Minha Mania');
        $email = trim((string) ($config['email'] ?? ''))
            ?: (string) config('mail.from.address');
        $fone = preg_replace(
            '/\D/',
            '',
            (string) ($config['fone'] ?? ($brecho->telefone ?: $brecho->whatsapp ?: ''))
        );

        $contato = mb_substr($contato, 0, 60);
        $email = mb_substr($email, 0, 60);

        if (mb_strlen($contato) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6,14}$/', $fone)) {
            throw new \RuntimeException(
                'Contato, e-mail e telefone do responsável técnico fiscal são obrigatórios e devem ser válidos.'
            );
        }

        $csrt = preg_replace('/\s+/', '', (string) ($config['csrt'] ?? ''));
        $idCsrt = preg_replace('/\D/', '', (string) ($config['id_csrt'] ?? ''));

        if (($csrt === '') !== ($idCsrt === '')) {
            throw new \RuntimeException('Informe o CSRT e o ID do CSRT juntos, ou deixe ambos vazios.');
        }

        if ($idCsrt !== '' && !preg_match('/^\d{2}$/', $idCsrt)) {
            throw new \RuntimeException('O ID do CSRT do responsável técnico deve conter exatamente 2 dígitos.');
        }

        $std = new stdClass();
        $std->CNPJ = $cnpjResponsavel;
        $std->xContato = $contato;
        $std->email = $email;
        $std->fone = $fone;

        if ($csrt !== '') {
            $std->CSRT = $csrt;
            $std->idCSRT = $idCsrt;
        }

        $nfe->taginfRespTec($std);
    }

    /**
     * Processa a resposta do lote enviada pela SEFAZ.
     */
    protected function processarRetornoSefaz(NotaFiscal $notaFiscal, Tools $tools, string $xmlAssinado, string $respostaXml, Brecho $brecho): void
    {
        // Interpreta o retorno SOAP da SEFAZ
        $dom = new \DOMDocument();
        $dom->loadXML($respostaXml);

        $cStat = $dom->getElementsByTagName('cStat')->item(0)?->nodeValue;
        $xMotivo = $dom->getElementsByTagName('xMotivo')->item(0)?->nodeValue;
        $nProt = $dom->getElementsByTagName('nProt')->item(0)?->nodeValue;

        // Se autorizado no lote (cStat 100 ou lote processado 104)
        if ($cStat == '100' || $cStat == '104') {
            // Em caso de lote 104, pega o cStat interno do infProt
            $infProt = $dom->getElementsByTagName('infProt')->item(0);
            if ($infProt) {
                $cStatProt = $infProt->getElementsByTagName('cStat')->item(0)?->nodeValue;
                $xMotivoProt = $infProt->getElementsByTagName('xMotivo')->item(0)?->nodeValue;
                $nProt = $infProt->getElementsByTagName('nProt')->item(0)?->nodeValue ?: $nProt;

                if ($cStatProt == '100') {
                    $cStat = '100';
                    $xMotivo = $xMotivoProt;
                } else {
                    $cStat = $cStatProt;
                    $xMotivo = $xMotivoProt;
                }
            }
        }

        if ($cStat == '100') {
            // Adiciona o protocolo ao XML assinado gerando o XML de Distribuição
            $xmlAutorizado = Complements::toAuthorize($xmlAssinado, $respostaXml);

            // Salva arquivos no Storage
            $chave = $notaFiscal->chave_acesso;
            $pastaXml = config('fiscal.storage.xmls', 'fiscal/xmls');
            $pastaDanfe = config('fiscal.storage.danfes', 'fiscal/danfes');

            $caminhoXml = "{$pastaXml}/{$chave}.xml";
            Storage::put($caminhoXml, $xmlAutorizado);

            // Gerar DANFE (PDF)
            $caminhoPdf = null;
            try {
                $danfe = new Danfe($xmlAutorizado, 'P', 'A4', '', 'I', '');
                $pdfRenderizado = $danfe->render();
                $caminhoPdf = "{$pastaDanfe}/{$chave}.pdf";
                Storage::put($caminhoPdf, $pdfRenderizado);
            } catch (\Exception $e) {
                Log::warning("DANFE PDF não pôde ser gerado imediatamente: " . $e->getMessage());
            }

            // Atualiza status da nota e incrementa contador do brechó
            $notaFiscal->update([
                'status'            => 'autorizada',
                'cStat'             => $cStat,
                'xMotivo'           => $xMotivo,
                'protocolo'         => $nProt,
                'xml_autorizado'    => $xmlAutorizado,
                'danfe_pdf_path'    => $caminhoPdf,
                'data_autorizacao'  => now(),
            ]);

            // Atualiza último número de NF do brechó
            if ($brecho->id) {
                $brecho->update(['nfe_ultimo_numero' => $notaFiscal->numero]);
            }
        } else {
            $notaFiscal->update([
                'status'  => 'rejeitada',
                'cStat'   => $cStat,
                'xMotivo' => $xMotivo ?: 'Nota Fiscal Rejeitada pela SEFAZ.',
            ]);
        }
    }

    /**
     * Retorna a instância configurada do NFePHP Tools.
     */
    public function getTools(Brecho $brecho): Tools
    {
        $ambiente = (int) ($brecho->nfe_ambiente ?: config('fiscal.ambiente', 2));
        $uf = strtoupper($brecho->uf ?: config('fiscal.uf', 'SC'));
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $brecho->documento ?: env('STORE_DOCUMENT', '12345678000199'));

        $config = [
            'atualizacao' => now()->format('Y-m-d H:i:s'),
            'tpAmb'       => $ambiente,
            'razaosocial' => $brecho->razao_social ?: $brecho->nome ?: 'BRECHO MINHA MANIA LTDA',
            'siglaUF'     => $uf,
            'cnpj'        => $cnpjLimpo,
            'schemes'     => 'PL_009_V4',
            'versao'      => '4.00',
            'tokenIBPT'   => '',
            'CSC'         => $brecho->csc ?: '',
            'CSCid'       => $brecho->csc_id ?: '',
        ];

        $configJson = json_encode($config);

        // Carrega o Certificado A1
        $certificate = $this->carregarCertificado($brecho);

        $tools = new Tools($configJson, $certificate);
        $tools->model(55); // Modelo 55 = NF-e

        return $tools;
    }

    /**
     * Carrega o certificado digital A1 configurado para o brechó.
     */
    protected function carregarCertificado(Brecho $brecho): Certificate
    {
        $certPath = $brecho->certificado_path;
        $senha = $brecho->certificado_senha ?: '';

        if (!empty($certPath) && Storage::exists($certPath)) {
            $pfxContent = Storage::get($certPath);
            return $this->certificateLoader->load($pfxContent, $senha);
        }

        if (!empty($certPath) && file_exists($certPath)) {
            $pfxContent = file_get_contents($certPath);
            return $this->certificateLoader->load($pfxContent, $senha);
        }

        // Verifica se há certificado padrão na pasta storage/app/fiscal/certificados/
        $defaultCert = storage_path('app/fiscal/certificados/certificado.pfx');
        if (file_exists($defaultCert)) {
            $pfxContent = file_get_contents($defaultCert);
            return $this->certificateLoader->load($pfxContent, env('FISCAL_CERT_PASSWORD', ''));
        }

        throw new \Exception("Certificado Digital A1 (.pfx) não encontrado para o Brechó '{$brecho->nome}'. Faça o upload nas configurações fiscais.");
    }

    /**
     * Cancela uma NF-e autorizada perante a SEFAZ.
     */
    public function cancelarNfe(NotaFiscal $notaFiscal, string $justificativa): bool
    {
        if (!$notaFiscal->isAutorizada()) {
            throw new \Exception("Apenas notas fiscais autorizadas podem ser canceladas.");
        }

        if (strlen(trim($justificativa)) < 15) {
            throw new \Exception("A justificativa de cancelamento deve ter pelo menos 15 caracteres.");
        }

        $brecho = $notaFiscal->brecho ?? Brecho::find(1);
        $tools = $this->getTools($brecho);

        $respostaXml = $tools->sefazCancela(
            $notaFiscal->chave_acesso,
            $justificativa,
            $notaFiscal->protocolo
        );

        $dom = new \DOMDocument();
        $dom->loadXML($respostaXml);

        $cStat = $dom->getElementsByTagName('cStat')->item(0)?->nodeValue;
        $xMotivo = $dom->getElementsByTagName('xMotivo')->item(0)?->nodeValue;

        if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
            $notaFiscal->update([
                'status'                    => 'cancelada',
                'cStat'                     => $cStat,
                'xMotivo'                   => $xMotivo,
                'xml_cancelamento'          => $respostaXml,
                'data_cancelamento'         => now(),
                'justificativa_cancelamento'=> $justificativa,
            ]);
            return true;
        }

        throw new \Exception("Erro ao cancelar NF-e: [{$cStat}] {$xMotivo}");
    }

    /**
     * Gera ou renderiza o PDF do DANFE para download ou exibição.
     */
    public function renderDanfePdf(NotaFiscal $notaFiscal): string
    {
        if (!empty($notaFiscal->danfe_pdf_path) && Storage::exists($notaFiscal->danfe_pdf_path)) {
            return Storage::get($notaFiscal->danfe_pdf_path);
        }

        if (empty($notaFiscal->xml_autorizado)) {
            throw new \Exception("XML autorizado não disponível para geração do DANFE.");
        }

        $danfe = new Danfe($notaFiscal->xml_autorizado, 'P', 'A4', '', 'I', '');
        $pdfContent = $danfe->render();

        $caminhoPdf = config('fiscal.storage.danfes', 'fiscal/danfes') . "/{$notaFiscal->chave_acesso}.pdf";
        Storage::put($caminhoPdf, $pdfContent);

        $notaFiscal->update(['danfe_pdf_path' => $caminhoPdf]);

        return $pdfContent;
    }
}
