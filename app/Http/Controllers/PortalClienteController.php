<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ContaCorrente; 
use Carbon\Carbon;


class PortalClienteController extends Controller
{
	public function dashboard()
	{
		$user = Auth::user();

		// 1) Saldo correto (última transação)
		$saldo = 0;
		try {
			$ultima = \App\Models\ContaCorrente::where('user_id', $user->id)
				->orderByDesc('data_movimentacao')
				->orderByDesc('id')
				->first();

			$saldo = $ultima?->saldo_atual ?? 0;
		} catch (\Exception $e) {
			$saldo = 0;
		}

		// 2) Sacolinha (dados reais)
		$sacolinhaRow = DB::table('sacolinhas')
			->where('user_id', $user->id)
			->selectRaw('COUNT(*) as itens')
			->selectRaw('COALESCE(SUM(price),0) as valor')
			->selectRaw('MIN(add_at) as aberto_em')
			->first();

		$sacolinha = [
			'itens'  => (int) ($sacolinhaRow->itens ?? 0),
			'valor'  => (float) ($sacolinhaRow->valor ?? 0),
			'status' => 'aberto',
			'data'   => !empty($sacolinhaRow->aberto_em)
				? \Carbon\Carbon::parse($sacolinhaRow->aberto_em)->format('d/m/Y')
				: 'N/A',
		];

		// 3) Limite Sacolinha (cliente_limites)
		$limitesRow = DB::table('cliente_limites')
			->where('user_id', $user->id)
			->first();

		$valorLimite = (float) ($limitesRow->limite_credito ?? 0);
		$utilizado   = (float) ($limitesRow->limite_utilizado ?? 0);
		$valorPago   = (float) ($saldo ?? 0);

		$disponivel = $valorLimite + $valorPago - $utilizado;
		$disponivelUI = max(0, $disponivel);

		$base = $valorLimite + $valorPago;
		$percentual = $base > 0 ? ($utilizado / $base) * 100 : 0;

		$limite = [
			'valor_limite' => $valorLimite,
			'utilizado'    => $utilizado,
			'valor_pago'   => $valorPago,
			'disponivel'   => $disponivelUI,
			'percentual'   => min(100, max(0, $percentual)),
		];

		// 4) NOVO: Dados do Clube Mania (snapshot apenas leitura)
		$clubeIndicadores = DB::table('cliente_clube_indicadores')
			->where('user_id', $user->id)
			->first();

		// Se não existir registro, usamos valores padrão para não quebrar a view
		$clubeData = [
			'mensalidade_status'        => $clubeIndicadores->mensalidade_status ?? 'inativa',
			'mensalidades_sequencia'    => (int) ($clubeIndicadores->mensalidades_sequencia ?? 0),
			'pedidos_concluidos'        => (int) ($clubeIndicadores->pedidos_concluidos ?? 0),
			'taxa_cancel_devol_percent' => (float) ($clubeIndicadores->taxa_cancel_devol_percent ?? 0.00),
			'atualizado_em'             => $clubeIndicadores->atualizado_em ?? null,
		];

		// 5) Retorno ÚNICO para a view correta
		return view('portal.cliente.dashboard', compact(
			'user', 
			'saldo', 
			'sacolinha', 
			'limite', 
			'clubeData'
		));
	}
    

	public function perfil(Request $request)
	{
		$user = Auth::user();
        $returnTo = $request->query('return_to');
		return view('portal.cliente.perfil', compact('user', 'returnTo'));
	}

	public function perfilAtualizar(Request $request)
	{
		$user = Auth::user();

		$rules = [
			'name' => ['required', 'string', 'max:255'],
			'email' => [
				'required',
				'email',
				'max:255',
				Rule::unique('users', 'email')->ignore($user->id),
			],
            'apelido' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:9'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'numero_endereco' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp'],
		];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        $request->validate($rules);

		$user->name = $request->name;
		$user->email = $request->email;
        $user->apelido = $request->apelido;
        $user->whatsapp = $request->whatsapp;

        // Atualizar endereço
        $user->cep = $request->cep;
        $user->endereco = $request->endereco;
        $user->numero_endereco = $request->numero_endereco;
        $user->complemento = $request->complemento;
        $user->bairro = $request->bairro;
        $user->cidade = $request->cidade;
        $user->estado = $request->estado;

		if (!empty($request->password)) {
			$user->password = Hash::make($request->password);
		}

        // Upload de foto de perfil
        if ($request->hasFile('photo')) {
            if ($user->photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->photo);
            }
            
            $croppedTempFile = false;
            // Verifica se a biblioteca GD está instalada no PHP do servidor
            if (extension_loaded('gd') && function_exists('imagecreatefromjpeg')) {
                // Crop and resize to 300x300
                $croppedTempFile = $this->cropAndResizeImage($request->file('photo'), 300, 300);
            }

            if ($croppedTempFile) {
                $filename = 'profiles/' . uniqid() . '.' . $request->file('photo')->getClientOriginalExtension();
                \Illuminate\Support\Facades\Storage::disk('public')->put($filename, file_get_contents($croppedTempFile));
                @unlink($croppedTempFile);
                $user->photo = $filename;
            } else {
                $path = $request->file('photo')->store('profiles', 'public');
                $user->photo = $path;
            }
        }

		$user->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso.',
                'photo_url' => $user->photo ? asset('storage/' . $user->photo) : null,
                'brand_name' => 'Mania de ' . ($user->apelido ?: explode(' ', $user->name)[0])
            ]);
        }

		return redirect($request->input('return_to', route('portal.dashboard')))
			->with('success', 'Perfil updated.');
	}
	
	
	public function pedidos()
	{
		$user = Auth::user();

		$pedidos = \App\Models\Pedido::where('user_id', $user->id)
			->orderBy('data_pedido', 'desc')
			->get();

		foreach ($pedidos as $pedido) {
			if ($pedido->codigo_rastreamento && !in_array(strtolower($pedido->status_pedido ?? ''), ['entregue', 'concluido', 'cancelado'])) {
				$pedido->checkAndSyncTracking();
			}
		}

		return view('portal.cliente.pedidos', compact('user', 'pedidos'));
	}	

	
	public function sacolinha()
	{
		$user = Auth::user();

		// Verificar se tem itens com status 'Em Analize'
		$temEmAnalise = DB::table('sacolinhas')
			->where('user_id', $user->id)
			->where('status', 'em analise')
			->exists();

		// Calcular total dos itens em análise
		$totalItensEmAnalise = DB::table('sacolinhas')
			->where('user_id', $user->id)
			->where('status', 'em analise')
			->sum('price');

		// Excedente = total dos itens em análise
		$excedente = $totalItensEmAnalise;

		// Buscar dados do limite
		$limitesRow = DB::table('cliente_limites')
			->where('user_id', $user->id)
			->first();

		$valorLimite = (float) ($limitesRow->limite_credito ?? 0);
		$utilizado   = (float) ($limitesRow->limite_utilizado ?? 0);
		
		// Buscar saldo
		$saldo = 0;
		try {
			$ultima = ContaCorrente::where('user_id', $user->id)
				->orderByDesc('data_movimentacao')
				->orderByDesc('id')
				->first();
			$saldo = $ultima?->saldo_atual ?? 0;
		} catch (\Exception $e) {
			$saldo = 0;
		}
		
		$valorPago   = (float) ($saldo ?? 0);
		$disponivel  = $valorLimite + $valorPago - $utilizado;
		$disponivelUI = max(0, $disponivel);

		// Buscar itens da sacolinha
		$itens = DB::table('sacolinhas as s')
			->join('items as i', 'i.id', '=', 's.item_id')
			->where('s.user_id', $user->id)
			->orderBy('s.add_at', $temEmAnalise ? 'desc' : 'asc') // Se tiver em análise, mais novo primeiro
			->select([
				's.id as sacolinha_id',
				's.item_id',
				's.price',
				's.add_at',
				's.status as sacolinha_status',
				's.obs',

				'i.codigo',
				'i.nome_do_produto',
				'i.estado',
				'i.cor',
				'i.tamanho',
				'i.image',
				'i.marca',
			])
			->get();

		$total = (float) $itens->sum('price');

		return view('portal.cliente.sacolinha', compact(
			'user', 
			'itens', 
			'total',
			'temEmAnalise',
			'totalItensEmAnalise',
			'excedente',
			'valorLimite',
			'utilizado',
			'valorPago',
			'disponivelUI'
		));
	}

    public function sacolinhaExcluir($id)
    {
        $user = Auth::user();

        DB::table('sacolinhas')
            ->where('id', $id)
            ->where('user_id', $user->id) // Segurança: só exclui do próprio cliente
            ->delete();

        return redirect()
            ->route('portal.sacolinha')
            ->with('success', 'Item removido da sacolinha.');
    }

	public function movimentacao()
	{
		$user = Auth::user();
		$movimentacoes = ContaCorrente::where('user_id', $user->id)
									  ->orderBy('data_movimentacao', 'desc')
									  ->get();
		
		return view('portal.cliente.movimentacao', compact('user', 'movimentacoes'));
	}

    public function desafios()
    {
        $user = Auth::user();

        // Desafios ativos e dentro do prazo
        $desafios = \App\Models\Desafio::where('status', 'ativo')
            ->where(function ($q) {
                $hoje = now()->toDateString();
                $q->whereNull('inicio_em')->orWhereDate('inicio_em', '<=', $hoje);
            })
            ->where(function ($q) {
                $hoje = now()->toDateString();
                $q->whereNull('fim_em')->orWhereDate('fim_em', '>=', $hoje);
            })
            ->orderBy('nome')
            ->get();

        // Histórico de pontos de desafio lançados para este cliente
        $historico = DB::table('pontos_desafios')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Total de pontos de desafios acumulados
        $totalDesafios = $historico->sum('pontos');

        return view('portal.cliente.desafios', compact('user', 'desafios', 'historico', 'totalDesafios'));
    }

    /**
     * Crop image to center square and resize it using native GD library.
     */
    private function cropAndResizeImage($file, $targetWidth = 300, $targetHeight = 300)
    {
        $info = getimagesize($file->getRealPath());
        if (!$info) {
            return false;
        }

        $width = $info[0];
        $height = $info[1];
        $type = $info[2];

        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($file->getRealPath());
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($file->getRealPath());
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($file->getRealPath());
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($file->getRealPath());
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Corrigir orientação EXIF se for JPEG e a função exif_read_data estiver disponível
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                $angle = 0;
                $flip = false;

                switch ($orientation) {
                    case 2:
                        $flip = 'horizontal';
                        break;
                    case 3:
                        $angle = 180;
                        break;
                    case 4:
                        $flip = 'vertical';
                        break;
                    case 5:
                        $angle = 270;
                        $flip = 'vertical';
                        break;
                    case 6:
                        $angle = 270; // 90 graus CW (rotacionar 270 no PHP que rotaciona CCW)
                        break;
                    case 7:
                        $angle = 90;
                        $flip = 'vertical';
                        break;
                    case 8:
                        $angle = 90; // 270 graus CW (rotacionar 90 no PHP que rotaciona CCW)
                        break;
                }

                if ($angle !== 0) {
                    $rotatedImage = imagerotate($sourceImage, $angle, 0);
                    if ($rotatedImage !== false) {
                        imagedestroy($sourceImage);
                        $sourceImage = $rotatedImage;
                        // Se rotacionado 90 ou 270 graus, a largura e altura da imagem de origem se invertem
                        if ($angle === 90 || $angle === 270) {
                            $temp = $width;
                            $width = $height;
                            $height = $temp;
                        }
                    }
                }

                if ($flip && function_exists('imageflip')) {
                    $flipMode = ($flip === 'horizontal') ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL;
                    imageflip($sourceImage, $flipMode);
                }
            }
        }

        $destImage = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefilledrectangle($destImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        $srcX = 0;
        $srcY = 0;
        $srcW = $width;
        $srcH = $height;

        if ($width > $height) {
            $srcW = $height;
            $srcX = (int)(($width - $height) / 2);
        } else {
            $srcH = $width;
            $srcY = (int)(($height - $width) / 2);
        }

        imagecopyresampled(
            $destImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $srcW,
            $srcH
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'profile_');
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($destImage, $tempPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($destImage, $tempPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($destImage, $tempPath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($destImage, $tempPath, 90);
                break;
            default:
                imagejpeg($destImage, $tempPath, 90);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($destImage);

        return $tempPath;
    }
}