@extends('layouts.app')

@section('title', 'Teste Layout')

@section('content')
<div class="container">
    <h1>🧪 Teste com Layout Principal</h1>
    <p><strong>Status:</strong> ✅ Layout carregado!</p>
    <p><strong>Timestamp:</strong> {{ date('Y-m-d H:i:s') }}</p>
    
    <div class="alert alert-success">
        Se você vê esta mensagem, o layout principal está funcionando!
    </div>
    
    <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">← Voltar</a>
</div>
@endsection