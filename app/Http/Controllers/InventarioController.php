<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Sacolinha;
use Illuminate\Http\Request;


class InventarioController extends Controller
{
    /**
     * Exibe a página de inventário e processa a busca por QR Code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $searchCode = $request->input('search');
        $statusToApply = $request->input('status');
        $items = collect(); // Inicializa uma coleção vazia para os itens

        if ($searchCode) {
            // Busca o item pelo código e faz os joins necessários
            $foundItem = Item::where('codigo', $searchCode)
                             ->with(['sacolinha.user']) // Carrega o relacionamento 'sacolinha' e dentro dela o 'user'
                             ->first();

            if ($foundItem) {
                $items->push($foundItem); // Adiciona o item encontrado à coleção

                // Se houver um status para aplicar e o item estiver em uma sacolinha
                if ($statusToApply && $foundItem->sacolinha) {
                    $foundItem->status = $statusToApply;
                    $foundItem->save(); // Salva a atualização do status

                    // Prepara a mensagem de sucesso com o nome do cliente
                    $userName = $foundItem->sacolinha->user ? $foundItem->sacolinha->user->name : 'Desconhecido';
                    session()->flash('success', "Item `{$foundItem->nome_do_produto}` atualizado para '{$statusToApply}' na sacolinha de **{$userName}**.");
                } elseif ($foundItem->sacolinha) {
                    // Se apenas buscar, mostra de qual sacolinha é
                    $userName = $foundItem->sacolinha->user ? $foundItem->sacolinha->user->name : 'Desconhecido';
                    session()->flash('info', "O item `{$foundItem->nome_do_produto}` pertence à sacolinha de **{$userName}**.");
                } else {
                    // Item encontrado, mas não está em nenhuma sacolinha
                    session()->flash('info', "O item `{$foundItem->nome_do_produto}` foi encontrado, mas não está associado a nenhuma sacolinha.");
                }
            } else {
                session()->flash('warning', "Nenhum item encontrado com o código: `{$searchCode}`.");
            }
        }
        // Se não houver busca, você pode carregar todos os itens ou deixar vazio
        // Para fins de demonstração, vamos carregar todos se não houver busca específica
        else {
             $items = Item::paginate(10); // Pagina todos os itens se não houver busca
        }

        // ATUALIZAÇÃO AQUI: Renderiza a view no caminho especificado
        return view('admin.sacolinhas.qecode-sacolinha', compact('items'));
    }
}